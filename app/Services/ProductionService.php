<?php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderLog;
use App\Models\Products;
use App\Models\SalesOrder;
use App\Models\StockReservation;
use App\Models\Task;
use App\Models\Warehouse;
use App\Services\Erp\DocumentFlow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ERP Fase 5 — Producción (fabricación propia).
 *
 * Ciclo: borrador → liberada (reserva) → en_proceso (consumo) → terminada (entrada del PT).
 * Todo cambio de existencias pasa por InventoryService::applyMovement(). Como
 * ese servicio mueve unidades enteras, los consumos y terminaciones se redondean
 * a unidades enteras (consumo hacia arriba).
 */
class ProductionService
{
    private const EPS = 0.00005;

    public function __construct(
        private ?BomService $bom = null,
        private ?MrpService $mrp = null,
        private ?InventoryService $inventory = null,
    ) {
        $this->bom ??= new BomService();
        $this->mrp ??= new MrpService($this->bom);
        $this->inventory ??= new InventoryService();
    }

    // ───────────────────────── Crear ─────────────────────────

    /**
     * @param array $data product_id, quantity, warehouse_id?, project_id?, sales_order_id?, due_date?, start_date?, notes?, user_id?
     */
    public function create(array $data): ProductionOrder
    {
        $product = Products::find((int) ($data['product_id'] ?? 0));
        if (!$product) {
            throw ValidationException::withMessages(['product_id' => 'Selecciona el producto a fabricar.']);
        }
        $qty = (float) ($data['quantity'] ?? 0);
        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad a fabricar debe ser mayor que cero.']);
        }

        $bom = $this->bom->activeBom($product);
        $components = $bom && $bom->lines->isNotEmpty() ? $this->bom->explode($product, $qty) : [];
        if (!$bom || !$components) {
            throw ValidationException::withMessages([
                'product_id' => "«{$product->name}» no tiene una lista de materiales (BOM) vigente con componentes. Crea o activa una BOM antes de producirlo.",
            ]);
        }

        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        if (!$warehouseId || !Warehouse::where('id', $warehouseId)->exists()) {
            $warehouseId = $this->inventory->defaultWarehouseId();
        }

        return DB::transaction(function () use ($data, $product, $qty, $bom, $components, $warehouseId) {
            $order = ProductionOrder::create([
                'number'             => ProductionOrder::generateNumber(),
                'product_id'         => $product->id,
                'bom_id'             => $bom->id,
                'quantity_planned'   => $qty,
                'quantity_produced'  => 0,
                'warehouse_id'       => $warehouseId,
                'project_id'         => $data['project_id'] ?? null,
                'sales_order_id'     => $data['sales_order_id'] ?? null,
                'due_date'           => $data['due_date'] ?? null,
                'start_date'         => $data['start_date'] ?? null,
                'status'             => 'borrador',
                'progress'           => 0,
                'standard_cost'      => round($this->bom->standardCost($product) * $qty, 4),
                'actual_cost'        => 0,
                'notes'              => $data['notes'] ?? null,
                'created_by_user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            foreach ($components as $c) {
                $order->components()->create([
                    'component_product_id' => $c['product_id'],
                    'required_quantity'    => round($c['quantity'], 4),
                    'unit'                 => $c['unit'] ?? null,
                    'unit_cost'            => round($this->bom->unitCost($c['product']), 4),
                ]);
            }

            if ($order->sales_order_id && ($so = SalesOrder::find($order->sales_order_id))) {
                DocumentFlow::link($so, $order);
            }
            if ($order->project_id && class_exists(\App\Models\Project::class) && ($pr = \App\Models\Project::find($order->project_id))) {
                DocumentFlow::link($pr, $order);
            }

            $this->log($order, 'creada', null, 'borrador', "Orden creada para {$qty} × {$product->name}.", $data['user_id'] ?? null);

            return $order->fresh('components');
        });
    }

    // ───────────────────────── Liberar ─────────────────────────

    /** Genera el requerimiento de materiales y reserva lo disponible. Idempotente. */
    public function release(ProductionOrder $order, ?int $userId = null): ProductionOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = ProductionOrder::lockForUpdate()->findOrFail($order->id);
            if (in_array($order->status, ['liberada', 'en_proceso'], true)) {
                return $this->refreshReserved($order);
            }
            if ($order->status !== 'borrador') {
                throw ValidationException::withMessages(['status' => 'Solo se puede liberar una orden en borrador.']);
            }

            $lines = $order->components->map(fn ($c) => [
                'product_id' => $c->component_product_id, 'quantity' => $c->required_quantity, 'unit' => $c->unit,
            ])->all();

            $customerId = $order->salesOrder?->customer_id;
            $req = $this->mrp->buildManual([
                'warehouse_id'   => $order->warehouse_id,
                'needed_by_date' => $order->due_date?->toDateString(),
                'project_id'     => $order->project_id,
                'customer_id'    => $customerId,
                'notes'          => 'Generado desde la orden de producción ' . $order->number,
                'user_id'        => $userId ?? auth()->id(),
            ], $lines);
            $req->update(['origin_type' => 'production_order', 'origin_id' => $order->id]);
            DocumentFlow::link($order, $req);

            $this->mrp->reserve($req);

            $order->update([
                'material_requirement_id' => $req->id,
                'status'                  => 'liberada',
                'start_date'              => $order->start_date ?? now()->toDateString(),
            ]);
            $this->refreshReserved($order);
            $this->log($order, 'liberada', 'borrador', 'liberada', "Requerimiento {$req->number} generado y componentes reservados.", $userId);

            return $order->fresh('components');
        });
    }

    /** Sincroniza reserved_quantity de cada componente con las reservas activas. */
    public function refreshReserved(ProductionOrder $order): ProductionOrder
    {
        $reserved = $order->material_requirement_id
            ? StockReservation::where('requirement_id', $order->material_requirement_id)->where('status', 'activa')
                ->groupBy('product_id')->selectRaw('product_id, SUM(quantity) q')->pluck('q', 'product_id')
            : collect();

        foreach ($order->components()->get() as $c) {
            $q = round((float) ($reserved[$c->component_product_id] ?? 0), 4);
            if (abs($q - $c->reserved_quantity) > self::EPS) {
                $c->update(['reserved_quantity' => $q]);
            }
        }

        return $order->fresh('components');
    }

    // ───────────────────────── Consumir ─────────────────────────

    /**
     * Consume componentes (salida de inventario). $quantities = [component_id => cantidad]
     * o null para consumir todo lo pendiente. Idempotente: sin pendiente no hace nada.
     */
    public function consume(ProductionOrder $order, ?array $quantities = null, ?int $userId = null, ?string $note = null): ProductionOrder
    {
        return DB::transaction(function () use ($order, $quantities, $userId, $note) {
            $order = ProductionOrder::lockForUpdate()->findOrFail($order->id);
            if (!in_array($order->status, ['liberada', 'en_proceso'], true)) {
                throw ValidationException::withMessages(['status' => 'La orden debe estar liberada o en proceso para consumir componentes.']);
            }

            $plan = [];
            foreach ($order->components()->get() as $c) {
                $want = $quantities === null ? $c->pending() : (float) ($quantities[$c->id] ?? 0);
                $want = min($want, $c->pending());
                if ($want > self::EPS) {
                    $plan[$c->id] = $want;
                }
            }

            $this->applyConsumption($order, $plan, $userId, $note);

            return $order->fresh('components');
        });
    }

    /** Aplica el plan [component_id => cantidad]; devuelve nº de componentes movidos. Debe llamarse en transacción. */
    private function applyConsumption(ProductionOrder $order, array $plan, ?int $userId, ?string $note): int
    {
        if (!$plan) {
            return 0;
        }

        $comps = $order->components()->get()->keyBy('id');
        $errors = [];
        $units = [];
        foreach ($plan as $cid => $want) {
            $c = $comps[$cid];
            $u = (int) ceil($want - self::EPS);
            if ($u <= 0) {
                continue;
            }
            $onHand = $this->mrp->onHand((int) $c->component_product_id, (int) $order->warehouse_id);
            if ($onHand + self::EPS < $u) {
                $errors['stock_' . $cid] = ($c->product->name ?? 'Componente') . ": se necesitan {$u} y solo hay " . rtrim(rtrim(number_format($onHand, 4, '.', ''), '0'), '.') . ' en el almacén.';
            }
            $units[$cid] = $u;
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $names = [];
        foreach ($units as $cid => $u) {
            $c = $comps[$cid];
            $this->inventory->applyMovement(
                (int) $order->warehouse_id, (int) $c->component_product_id, 'salida', $u,
                ProductionOrder::class, $order->id, "Consumo de la orden de producción {$order->number}"
            );
            $c->update(['consumed_quantity' => round($c->consumed_quantity + $u, 4)]);
            $this->consumeReservations($order, (int) $c->component_product_id, $u);
            $names[] = ($c->product->name ?? '#' . $c->component_product_id) . " ×{$u}";
        }

        $from = $order->status;
        $order->update([
            'status'      => 'en_proceso',
            'start_date'  => $order->start_date ?? now()->toDateString(),
            'actual_cost' => $this->actualCost($order),
        ]);
        $this->refreshReserved($order);
        $this->log($order, 'consumo', $from, 'en_proceso', trim(($note ? $note . ' · ' : '') . 'Consumo: ' . implode(', ', $names)), $userId);

        return count($units);
    }

    private function consumeReservations(ProductionOrder $order, int $productId, float $units): void
    {
        if (!$order->material_requirement_id) {
            return;
        }
        $rows = StockReservation::where('requirement_id', $order->material_requirement_id)
            ->where('product_id', $productId)->where('status', 'activa')->orderBy('id')->get();
        $left = $units;
        foreach ($rows as $r) {
            if ($left <= self::EPS) {
                break;
            }
            if ((float) $r->quantity <= $left + self::EPS) {
                $left -= (float) $r->quantity;
                $r->update(['status' => 'consumida']);
            } else {
                $r->update(['quantity' => round((float) $r->quantity - $left, 4)]);
                $left = 0;
            }
        }
    }

    private function actualCost(ProductionOrder $order): float
    {
        return round((float) $order->components()->get()->sum(fn ($c) => $c->consumed_quantity * $c->unit_cost), 4);
    }

    // ───────────────────────── Terminar ─────────────────────────

    /**
     * Registra producción terminada (entera) y da entrada al producto terminado.
     * Consume automáticamente la parte proporcional de componentes aún no consumida.
     * $token evita doble registro por doble clic / reintento.
     */
    public function finish(ProductionOrder $order, ?float $quantity = null, ?int $userId = null, ?string $note = null, ?string $token = null): ProductionOrder
    {
        if ($token && !Cache::add("production-finish:{$order->id}:{$token}", 1, 600)) {
            return $order->fresh('components');
        }

        try {
            return DB::transaction(function () use ($order, $quantity, $userId, $note) {
                $order = ProductionOrder::lockForUpdate()->findOrFail($order->id);
                if ($order->status === 'terminada') {
                    return $order->fresh('components');
                }
                if (!in_array($order->status, ['liberada', 'en_proceso'], true)) {
                    throw ValidationException::withMessages(['status' => 'La orden debe estar liberada o en proceso para terminarla.']);
                }

                $remaining = $order->quantity_planned - $order->quantity_produced;
                $qty = $quantity ?? $remaining;
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'La cantidad terminada debe ser mayor que cero.']);
                }
                if (abs($qty - round($qty)) > self::EPS) {
                    throw ValidationException::withMessages(['quantity' => 'La cantidad terminada debe ser un número entero (el inventario se mueve por unidades).']);
                }
                if ($qty > $remaining + self::EPS) {
                    throw ValidationException::withMessages(['quantity' => 'No puedes terminar más de lo pendiente (' . rtrim(rtrim(number_format($remaining, 4, '.', ''), '0'), '.') . ').']);
                }
                $qty = (float) round($qty);

                // Consumo proporcional de lo que falte.
                $fraction = min(1.0, ($order->quantity_produced + $qty) / $order->quantity_planned);
                $plan = [];
                foreach ($order->components()->get() as $c) {
                    $need = $c->required_quantity * $fraction - $c->consumed_quantity;
                    if ($need > self::EPS) {
                        $plan[$c->id] = $need;
                    }
                }
                $this->applyConsumption($order, $plan, $userId, 'Consumo automático al terminar');
                $order->refresh();

                $this->inventory->applyMovement(
                    (int) $order->warehouse_id, (int) $order->product_id, 'entrada', (int) $qty,
                    ProductionOrder::class, $order->id, "Producto terminado de la orden {$order->number}"
                );

                $produced = round($order->quantity_produced + $qty, 4);
                $done = $produced + self::EPS >= $order->quantity_planned;
                $from = $order->status;
                $order->update([
                    'quantity_produced' => $produced,
                    'progress'          => (int) min(100, round($produced / $order->quantity_planned * 100)),
                    'status'            => $done ? 'terminada' : 'en_proceso',
                    'actual_cost'       => $this->actualCost($order),
                ]);

                if ($done && $order->material_requirement_id) {
                    StockReservation::where('requirement_id', $order->material_requirement_id)->where('status', 'activa')
                        ->get()->each->update(['status' => 'liberada']);
                }
                $this->refreshReserved($order);

                $order->refresh();
                $variance = round($order->actual_cost - $order->standardCostProduced(), 2);
                $this->log($order, $done ? 'terminada' : 'avance', $from, $order->status,
                    trim(($note ? $note . ' · ' : '') . "Producidas {$qty}. Costo real " . number_format($order->actual_cost, 2)
                        . ' vs estándar ' . number_format($order->standardCostProduced(), 2) . ' (var. ' . number_format($variance, 2) . ').'),
                    $userId);

                return $order->fresh('components');
            });
        } catch (\Throwable $e) {
            if ($token) {
                Cache::forget("production-finish:{$order->id}:{$token}");
            }
            throw $e;
        }
    }

    // ───────────────────────── Cancelar ─────────────────────────

    /**
     * Cancela la orden y libera reservas. Con producción registrada se rechaza; con
     * consumo se rechaza salvo $returnConsumed = true (devuelve los componentes al almacén).
     */
    public function cancel(ProductionOrder $order, ?int $userId = null, bool $returnConsumed = false, ?string $note = null): ProductionOrder
    {
        return DB::transaction(function () use ($order, $userId, $returnConsumed, $note) {
            $order = ProductionOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'cancelada') {
                return $order->fresh('components');
            }
            if ($order->status === 'terminada' || $order->quantity_produced > self::EPS) {
                throw ValidationException::withMessages(['status' => 'La orden ya tiene producción registrada y no se puede cancelar.']);
            }

            $consumed = $order->components()->where('consumed_quantity', '>', 0)->get();
            if ($consumed->isNotEmpty() && !$returnConsumed) {
                throw ValidationException::withMessages([
                    'status' => 'La orden ya consumió componentes. Confirma la devolución al almacén para cancelarla.',
                ]);
            }

            foreach ($consumed as $c) {
                $u = (int) round($c->consumed_quantity);
                if ($u > 0) {
                    $this->inventory->applyMovement(
                        (int) $order->warehouse_id, (int) $c->component_product_id, 'entrada', $u,
                        ProductionOrder::class, $order->id, "Devolución por cancelación de la orden {$order->number}"
                    );
                }
                $c->update(['consumed_quantity' => 0]);
            }

            if ($order->material_requirement_id && ($req = $order->requirement)) {
                $this->mrp->cancel($req);
            }
            $order->components()->update(['reserved_quantity' => 0]);

            $from = $order->status;
            $order->update(['status' => 'cancelada', 'actual_cost' => 0]);
            $this->log($order, 'cancelada', $from, 'cancelada',
                trim(($note ? $note . ' · ' : '') . ($consumed->isNotEmpty() ? 'Componentes consumidos devueltos al almacén.' : 'Reservas liberadas.')), $userId);

            return $order->fresh('components');
        });
    }

    // ───────────────────────── Alertas ─────────────────────────

    /** Crea una Task por orden vencida y no terminada (una sola vez por orden). Devuelve nº de tareas creadas. */
    public function alertDelays(): int
    {
        $created = 0;
        foreach (ProductionOrder::overdue()->get() as $order) {
            $exists = Task::withTrashed()
                ->where('taskable_type', ProductionOrder::class)->where('taskable_id', $order->id)->exists();
            if ($exists) {
                continue;
            }
            $days = abs((int) $order->daysToDue());
            Task::create([
                'taskable_type' => ProductionOrder::class,
                'taskable_id'   => $order->id,
                'assigned_to'   => $order->created_by_user_id,
                'title'         => "Orden de producción {$order->number} vencida",
                'description'   => "Fecha límite {$order->due_date->format('d/m/Y')} ({$days} día(s) de retraso). Avance {$order->progress}%.",
                'due_at'        => now(),
                'status'        => 'open',
            ]);
            $created++;
        }

        return $created;
    }

    // ───────────────────────── Bitácora ─────────────────────────

    private function log(ProductionOrder $order, string $action, ?string $from, ?string $to, ?string $note, ?int $userId): void
    {
        ProductionOrderLog::create([
            'production_order_id' => $order->id,
            'user_id'             => $userId ?? auth()->id(),
            'action'              => $action,
            'from_status'         => $from,
            'to_status'           => $to,
            'progress'            => (int) $order->fresh()->progress,
            'note'                => $note,
        ]);
    }
}
