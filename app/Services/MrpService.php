<?php

namespace App\Services;

use App\Models\ChemicalProjection;
use App\Models\MaterialRequirement;
use App\Models\MaterialRequirementLine;
use App\Models\Products;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockReservation;
use App\Models\SupplierProduct;
use App\Models\TechnicalService;
use App\Models\WarehouseProductStock;
use App\Services\Erp\DocumentFlow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * ERP Fase 2 — MRP y reservas.
 *
 * Demanda (dos orígenes):
 *  - fabricación: líneas con producto de BOM vigente → componentes hoja (BomService::explode)
 *  - servicio/instalación/compra directa: ServiceMaterialPlanned, líneas sin BOM, químicos proyectados
 *
 * faltante = max(0, requerido − max(0, existencia − reservado_por_otros))
 */
class MrpService
{
    private const EPS = 0.00005;

    public function __construct(private ?BomService $bom = null)
    {
        $this->bom ??= new BomService();
    }

    // ───────────────────────── Construcción ─────────────────────────

    /**
     * Opciones comunes: needed_by_date, warehouse_id, include_chemicals, notes, user_id.
     * Idempotente por origen: si ya existe un requerimiento abierto (borrador/calculado)
     * del mismo origen, se recalcula y se devuelve en lugar de duplicarlo.
     */
    public function buildForSalesOrder(SalesOrder $order, array $opts = []): MaterialRequirement
    {
        return $this->buildFromOrigin('sales_order', $order, [
            'customer_id' => $order->customer_id,
            'project_id'  => $this->projectIdOf($order),
        ], $opts);
    }

    public function buildForProject(Model $project, array $opts = []): MaterialRequirement
    {
        return $this->buildFromOrigin('project', $project, [
            'customer_id'    => $project->customer_id ?? null,
            'project_id'     => $project->getKey(),
            'needed_by_date' => $project->due_date ?? null,
        ], $opts);
    }

    public function buildForTechnicalService(TechnicalService $service, array $opts = []): MaterialRequirement
    {
        return $this->buildFromOrigin('technical_service', $service, [
            'customer_id'    => $service->customer_id,
            'project_id'     => $this->projectIdOf($service),
            'needed_by_date' => $service->service_date,
        ], $opts);
    }

    /**
     * @param array $data  needed_by_date, warehouse_id, project_id, customer_id, notes, user_id
     * @param array $lines [['product_id','quantity','unit'?], ...]
     */
    public function buildManual(array $data, array $lines): MaterialRequirement
    {
        $req = DB::transaction(function () use ($data, $lines) {
            $req = $this->newRequirement('manual', null, $data, $data);
            $order = 0;
            $merged = [];
            foreach ($lines as $l) {
                $pid = (int) ($l['product_id'] ?? 0);
                $qty = (float) ($l['quantity'] ?? 0);
                if (!$pid || $qty <= 0) {
                    continue;
                }
                $merged[$pid]['product_id'] = $pid;
                $merged[$pid]['quantity'] = ($merged[$pid]['quantity'] ?? 0) + $qty;
                $merged[$pid]['unit'] = $merged[$pid]['unit'] ?? ($l['unit'] ?? null);
                $merged[$pid]['source'] = 'manual';
            }
            foreach ($merged as $row) {
                $req->lines()->create([
                    'product_id'        => $row['product_id'],
                    'required_quantity' => round($row['quantity'], 4),
                    'unit'              => $row['unit'],
                    'source'            => 'manual',
                    'sort_order'        => $order++,
                ]);
            }

            return $req;
        });

        return $this->recalculate($req);
    }

    private function buildFromOrigin(string $type, Model $origin, array $defaults, array $opts): MaterialRequirement
    {
        $existing = MaterialRequirement::where('origin_type', $type)->where('origin_id', $origin->getKey())
            ->whereIn('status', ['borrador', 'calculado', 'cubierto'])->latest('id')->first();
        if ($existing) {
            return $this->recalculate($existing);
        }

        $req = DB::transaction(function () use ($type, $origin, $defaults, $opts) {
            $req = $this->newRequirement($type, $origin->getKey(), $defaults, $opts);
            DocumentFlow::link($origin, $req);

            return $req;
        });

        return $this->recalculate($req);
    }

    private function newRequirement(string $type, ?int $originId, array $defaults, array $opts): MaterialRequirement
    {
        $needed = $opts['needed_by_date'] ?? $defaults['needed_by_date'] ?? null;

        return MaterialRequirement::create([
            'number'             => MaterialRequirement::generateNumber(),
            'origin_type'        => $type,
            'origin_id'          => $originId,
            'project_id'         => $opts['project_id'] ?? $defaults['project_id'] ?? null,
            'customer_id'        => $opts['customer_id'] ?? $defaults['customer_id'] ?? null,
            'warehouse_id'       => $opts['warehouse_id'] ?? null,
            'needed_by_date'     => $needed ?: null,
            'include_chemicals'  => (bool) ($opts['include_chemicals'] ?? false),
            'status'             => 'borrador',
            'notes'              => $opts['notes'] ?? null,
            'created_by_user_id' => $opts['user_id'] ?? auth()->id(),
        ]);
    }

    private function projectIdOf(Model $doc): ?int
    {
        if (!Schema::hasColumn($doc->getTable(), 'project_id')) {
            return null;
        }

        return $doc->project_id ? (int) $doc->project_id : null;
    }

    // ───────────────────────── Demanda ─────────────────────────

    /**
     * Demanda agregada por producto del origen del requerimiento.
     *
     * @return array<int, array{product_id:int, quantity:float, unit:?string, source:string}>
     */
    public function gatherDemand(MaterialRequirement $req): array
    {
        $acc = [];
        $origin = $req->origin;

        switch ($req->origin_type) {
            case 'sales_order':
                if ($origin) {
                    $this->demandFromSalesOrder($origin, $acc);
                }
                break;
            case 'project':
                if ($origin) {
                    $this->demandFromProject($origin, $acc);
                }
                break;
            case 'technical_service':
                if ($origin) {
                    $this->demandFromService($origin, $acc);
                }
                break;
        }

        if ($req->include_chemicals && $req->customer_id) {
            foreach (ChemicalProjection::where('customer_id', $req->customer_id)->get() as $proj) {
                $this->addDemand($acc, (int) $proj->product_id, (float) $proj->projected_quantity, null, 'quimicos');
            }
        }

        // Las líneas manuales capturadas por el usuario se conservan al recalcular.
        if ($req->origin_type !== 'manual') {
            foreach ($req->lines()->where('source', 'manual')->get() as $l) {
                $this->addDemand($acc, (int) $l->product_id, (float) $l->required_quantity, $l->unit, 'manual');
            }
        }

        return $acc;
    }

    private function demandFromSalesOrder(SalesOrder $order, array &$acc): void
    {
        foreach ($order->items()->get() as $item) {
            if (!$item->product_id) {
                continue;
            }
            $qty = max(0, (float) $item->quantity_ordered - (float) $item->quantity_delivered);
            if ($qty <= 0) {
                continue;
            }
            $product = Products::find($item->product_id);
            if (!$product) {
                continue;
            }

            $components = $this->bom->explode($product, $qty);
            if ($components) {
                foreach ($components as $c) {
                    $this->addDemand($acc, (int) $c['product_id'], (float) $c['quantity'], $c['unit'] ?? null, 'componente_bom');
                }
            } else {
                // Producto de compra directa (sin receta vigente).
                $this->addDemand($acc, (int) $product->id, $qty, $item->unit ?: ($product->stock_unit ?? null), 'linea_pedido');
            }
        }
    }

    private function demandFromProject(Model $project, array &$acc): void
    {
        if (Schema::hasColumn('sales_orders', 'project_id')) {
            foreach (SalesOrder::where('project_id', $project->getKey())->where('status', '!=', 'cancelado')->get() as $order) {
                $this->demandFromSalesOrder($order, $acc);
            }
        }
        if (Schema::hasColumn('services', 'project_id')) {
            foreach (TechnicalService::where('project_id', $project->getKey())->where('status', '!=', 'cancelled')->get() as $service) {
                $this->demandFromService($service, $acc);
            }
        }
    }

    private function demandFromService(TechnicalService $service, array &$acc): void
    {
        foreach ($service->plannedMaterials()->get() as $m) {
            $pid = $m->product_id;
            if (!$pid && $m->product_sku) {
                $pid = Products::where('sku', $m->product_sku)->value('id');
            }
            if (!$pid || (float) $m->quantity <= 0) {
                continue; // material libre sin producto de catálogo: no hay existencias que planear
            }
            $this->addDemand($acc, (int) $pid, (float) $m->quantity, $m->unit, 'servicio');
        }
    }

    private function addDemand(array &$acc, int $productId, float $qty, ?string $unit, string $source): void
    {
        if ($productId <= 0 || $qty <= 0) {
            return;
        }
        if (isset($acc[$productId])) {
            $acc[$productId]['quantity'] += $qty;
        } else {
            $acc[$productId] = ['product_id' => $productId, 'quantity' => $qty, 'unit' => $unit, 'source' => $source];
        }
    }

    // ───────────────────────── Cálculo ─────────────────────────

    /**
     * Vuelve a juntar la demanda del origen y recalcula existencias/reservas/faltantes.
     * Si el requerimiento tiene reservas activas, las ajusta (amplía/reduce).
     */
    public function recalculate(MaterialRequirement $req): MaterialRequirement
    {
        return DB::transaction(function () use ($req) {
            $req->refresh();
            if ($req->status === 'cancelado') {
                return $req;
            }

            $hadReservations = $req->reservations()->where('status', 'activa')->exists();

            if ($req->origin_type === 'manual') {
                $demand = [];
                foreach ($req->lines as $l) {
                    $demand[$l->product_id] = [
                        'product_id' => $l->product_id, 'quantity' => (float) $l->required_quantity,
                        'unit' => $l->unit, 'source' => 'manual',
                    ];
                }
            } else {
                $demand = $this->gatherDemand($req);
            }

            $this->syncLines($req, $demand);
            $this->evaluate($req);
            if ($hadReservations) {
                $this->syncReservations($req);
                $this->evaluate($req);
            }
            $this->refreshStatus($req);

            return $req->fresh('lines');
        });
    }

    private function syncLines(MaterialRequirement $req, array $demand): void
    {
        $existing = $req->lines()->get()->keyBy('product_id');
        $order = 0;

        foreach ($demand as $pid => $row) {
            $payload = [
                'required_quantity' => round($row['quantity'], 4),
                'unit'              => $row['unit'],
                'source'            => $row['source'],
                'sort_order'        => $order++,
            ];
            if ($existing->has($pid)) {
                $existing[$pid]->update($payload);
            } else {
                $req->lines()->create($payload + ['product_id' => $pid]);
            }
        }

        foreach ($existing as $pid => $line) {
            if (!isset($demand[$pid])) {
                if ($line->purchase_order_id) {
                    $line->update(['required_quantity' => 0]); // conserva el rastro de la OC
                } else {
                    $line->delete();
                }
            }
        }
    }

    /** Recalcula existencia, reservado de otros, faltante y sugerencia de proveedor por línea. */
    private function evaluate(MaterialRequirement $req): void
    {
        $lines = $req->lines()->get();
        foreach ($lines as $line) {
            $onHand = $this->onHand((int) $line->product_id, $req->warehouse_id);
            $others = $this->reservedByOthers((int) $line->product_id, $req);
            $available = max(0.0, $onHand - $others);
            $short = max(0.0, (float) $line->required_quantity - $available);
            $own = $this->ownReserved((int) $line->product_id, $req);

            $upd = [
                'on_hand'      => round($onHand, 4),
                'reserved'     => round($others, 4),
                'own_reserved' => round($own, 4),
                'shortfall'    => round($short, 4),
            ];

            if (!$line->purchase_order_id) {
                if ($short > self::EPS) {
                    $pick = $this->pickSupplier((int) $line->product_id);
                    $upd += [
                        'supplier_id'        => $pick['supplier_id'],
                        'suggested_price'    => $pick['price'],
                        'suggested_currency' => $pick['currency'],
                    ];
                } else {
                    $upd += ['supplier_id' => null, 'suggested_price' => null, 'suggested_currency' => null];
                }
            }
            $upd['status'] = $line->purchase_order_id ? 'en_compra' : ($short <= self::EPS ? 'cubierto' : 'pendiente');

            $line->update($upd);
        }
    }

    public function onHand(int $productId, ?int $warehouseId = null): float
    {
        if ($warehouseId) {
            return (float) WarehouseProductStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->sum('quantity');
        }

        return (float) (Products::where('id', $productId)->value('stock') ?? 0);
    }

    public function reservedByOthers(int $productId, MaterialRequirement $req): float
    {
        return (float) StockReservation::where('product_id', $productId)->where('status', 'activa')
            ->where(fn ($q) => $q->whereNull('requirement_id')->orWhere('requirement_id', '!=', $req->id))
            ->when($req->warehouse_id, fn ($q) => $q->where(fn ($w) => $w->whereNull('warehouse_id')->orWhere('warehouse_id', $req->warehouse_id)))
            ->sum('quantity');
    }

    private function ownReserved(int $productId, MaterialRequirement $req): float
    {
        return (float) StockReservation::where('product_id', $productId)->where('status', 'activa')
            ->where('requirement_id', $req->id)->sum('quantity');
    }

    /** Estado del requerimiento a partir de sus líneas (no toca 'cancelado'). */
    public function refreshStatus(MaterialRequirement $req): void
    {
        if ($req->status === 'cancelado') {
            return;
        }
        $lines = $req->lines()->get();
        $hasShort = $lines->contains(fn ($l) => $l->shortfall > self::EPS);
        $hasPo = $lines->contains(fn ($l) => $l->purchase_order_id);

        $status = $lines->isEmpty() ? 'borrador' : (!$hasShort ? 'cubierto' : ($hasPo ? 'en_compra' : 'calculado'));
        if ($req->status !== $status) {
            $req->update(['status' => $status]);
        }
    }

    // ───────────────────────── Reservas ─────────────────────────

    /** Reserva min(requerido, disponible) por línea. Idempotente; ajusta si ya había reservas. */
    public function reserve(MaterialRequirement $req): MaterialRequirement
    {
        if ($req->status === 'cancelado') {
            throw ValidationException::withMessages(['requirement' => 'El requerimiento está cancelado.']);
        }

        return DB::transaction(function () use ($req) {
            // Serializa reservas concurrentes sobre los mismos productos.
            Products::whereIn('id', $req->lines()->pluck('product_id'))->orderBy('id')->lockForUpdate()->get('id');

            $this->syncReservations($req);
            $this->evaluate($req);
            $this->refreshStatus($req);

            return $req->fresh('lines');
        });
    }

    private function syncReservations(MaterialRequirement $req): void
    {
        foreach ($req->lines()->get() as $line) {
            $pid = (int) $line->product_id;
            $available = max(0.0, $this->onHand($pid, $req->warehouse_id) - $this->reservedByOthers($pid, $req));
            $desired = round(min((float) $line->required_quantity, $available), 4);

            $rows = StockReservation::where('requirement_id', $req->id)->where('product_id', $pid)
                ->where('status', 'activa')->orderBy('id')->get();
            $keep = $rows->first();

            if ($desired > self::EPS) {
                if ($keep) {
                    if (abs((float) $keep->quantity - $desired) > self::EPS) {
                        $keep->update(['quantity' => $desired]);
                    }
                    $rows->slice(1)->each->update(['status' => 'liberada']);
                } else {
                    StockReservation::create([
                        'product_id'         => $pid,
                        'warehouse_id'       => $req->warehouse_id,
                        'quantity'           => $desired,
                        'project_id'         => $req->project_id,
                        'requirement_id'     => $req->id,
                        'source_type'        => 'material_requirement',
                        'source_id'          => $req->id,
                        'status'             => 'activa',
                        'created_by_user_id' => auth()->id(),
                    ]);
                }
            } else {
                $rows->each->update(['status' => 'liberada']);
            }
        }
    }

    /** Libera todas las reservas activas del requerimiento. */
    public function release(MaterialRequirement $req): MaterialRequirement
    {
        return DB::transaction(function () use ($req) {
            StockReservation::where('requirement_id', $req->id)->where('status', 'activa')->get()
                ->each->update(['status' => 'liberada']);
            $this->evaluate($req);
            $this->refreshStatus($req);

            return $req->fresh('lines');
        });
    }

    public function cancel(MaterialRequirement $req): MaterialRequirement
    {
        return DB::transaction(function () use ($req) {
            StockReservation::where('requirement_id', $req->id)->where('status', 'activa')->get()
                ->each->update(['status' => 'liberada']);
            $req->update(['status' => 'cancelado']);

            return $req->fresh('lines');
        });
    }

    // ───────────────────────── Compras ─────────────────────────

    /**
     * Proveedor sugerido: el principal; si no hay, el de menor costo vigente.
     *
     * @return array{supplier_id:?int, price:?float, currency:?string, lead_time_days:?int}
     */
    public function pickSupplier(int $productId): array
    {
        $rows = SupplierProduct::where('product_id', $productId)->get();
        $product = Products::find($productId);
        $currency = strtoupper($product->currency ?? 'MXN') ?: 'MXN';

        if ($rows->isEmpty()) {
            return ['supplier_id' => null, 'price' => null, 'currency' => $currency, 'lead_time_days' => null];
        }

        $pick = $rows->firstWhere('is_primary', true)
            ?? $rows->filter(fn ($r) => $r->cost !== null && (float) $r->cost > 0)->sortBy(fn ($r) => (float) $r->cost)->first()
            ?? $rows->first();

        $price = ($pick->cost !== null && (float) $pick->cost > 0) ? (float) $pick->cost
            : (($product && $product->cost !== null && (float) $product->cost > 0) ? (float) $product->cost : null);

        return [
            'supplier_id'    => (int) $pick->supplier_id,
            'price'          => $price,
            'currency'       => $currency,
            'lead_time_days' => $pick->lead_time_days,
        ];
    }

    /** Asigna manualmente proveedor (y precio opcional) a una línea con faltante. */
    public function assignSupplier(MaterialRequirementLine $line, int $supplierId, ?float $price = null): void
    {
        if ($line->purchase_order_id) {
            return;
        }
        $product = $line->product;
        $sp = SupplierProduct::where('product_id', $line->product_id)->where('supplier_id', $supplierId)->first();
        $cost = $price ?? ($sp && (float) $sp->cost > 0 ? (float) $sp->cost : ($product && (float) $product->cost > 0 ? (float) $product->cost : null));

        $line->update([
            'supplier_id'        => $supplierId,
            'suggested_price'    => $cost,
            'suggested_currency' => strtoupper($product->currency ?? 'MXN') ?: 'MXN',
        ]);
    }

    /**
     * Faltantes por comprar agrupados por proveedor y moneda.
     * Cada grupo: key, supplier_id, supplier, currency, lead_time_days, has_supplier, subtotal,
     * lines[] = {line, product, quantity, unit_price, line_total}. El grupo sin proveedor va al final.
     *
     * @return array<int, array>
     */
    public function suggestPurchases(MaterialRequirement $req): array
    {
        $groups = [];
        $lines = $req->lines()->with(['product', 'supplier'])->get();

        foreach ($lines as $line) {
            if ($line->shortfall <= self::EPS || $line->purchase_order_id) {
                continue;
            }
            $sid = $line->supplier_id ? (int) $line->supplier_id : null;
            $cur = $line->suggested_currency ?: 'MXN';
            $key = $sid ? "{$sid}|{$cur}" : 'sin-proveedor';

            $price = $line->suggested_price;
            if ($price === null && $line->product && $line->product->cost !== null) {
                $price = (float) $line->product->cost;
            }
            $price = (float) ($price ?? 0);
            $qty = (int) ceil($line->shortfall - self::EPS);
            $qty = max(1, $qty);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key'            => $key,
                    'supplier_id'    => $sid,
                    'supplier'       => $line->supplier,
                    'currency'       => $cur,
                    'lead_time_days' => null,
                    'has_supplier'   => (bool) $sid,
                    'subtotal'       => 0.0,
                    'lines'          => [],
                ];
            }
            $total = round($qty * $price, 2);
            $groups[$key]['lines'][] = [
                'line' => $line, 'product' => $line->product, 'quantity' => $qty,
                'unit_price' => $price, 'line_total' => $total,
            ];
            $groups[$key]['subtotal'] = round($groups[$key]['subtotal'] + $total, 2);

            if ($sid) {
                $lt = SupplierProduct::where('product_id', $line->product_id)->where('supplier_id', $sid)->value('lead_time_days');
                if ($lt !== null) {
                    $groups[$key]['lead_time_days'] = max((int) $lt, (int) ($groups[$key]['lead_time_days'] ?? 0));
                }
            }
        }

        uasort($groups, fn ($a, $b) => [!$a['has_supplier'], $a['key']] <=> [!$b['has_supplier'], $b['key']]);

        return array_values($groups);
    }

    /**
     * Crea una OC (estado "pending") por proveedor/moneda con los faltantes seleccionados.
     *
     * @param array $selection        ids de línea (o llaves de grupo "supplier|MONEDA"); vacío = todas las líneas con proveedor
     * @param array $supplierOverrides [lineId => supplierId] para líneas sin proveedor sugerido
     * @return Collection<int, PurchaseOrder>
     */
    public function generatePurchaseOrders(MaterialRequirement $req, array $selection = [], array $supplierOverrides = []): Collection
    {
        if ($req->status === 'cancelado') {
            throw ValidationException::withMessages(['requirement' => 'El requerimiento está cancelado.']);
        }

        return DB::transaction(function () use ($req, $selection, $supplierOverrides) {
            foreach ($supplierOverrides as $lineId => $supplierId) {
                if ($supplierId) {
                    $line = $req->lines()->find($lineId);
                    if ($line) {
                        $this->assignSupplier($line, (int) $supplierId);
                    }
                }
            }

            $lineIds = [];
            $groupKeys = [];
            foreach ($selection as $s) {
                if (is_numeric($s)) {
                    $lineIds[] = (int) $s;
                } else {
                    $groupKeys[] = (string) $s;
                }
            }

            $created = collect();
            foreach ($this->suggestPurchases($req) as $group) {
                if (!$group['has_supplier']) {
                    continue;
                }
                $items = collect($group['lines']);
                if ($lineIds || $groupKeys) {
                    $items = $items->filter(fn ($i) => in_array($i['line']->id, $lineIds, true) || in_array($group['key'], $groupKeys, true));
                }
                if ($items->isEmpty()) {
                    continue;
                }
                $created->push($this->createPurchaseOrder($req, $group, $items->values()->all()));
            }

            if ($created->isEmpty()) {
                throw ValidationException::withMessages([
                    'selection' => 'No hay faltantes con proveedor para generar órdenes de compra.',
                ]);
            }

            $this->refreshStatus($req);

            return $created;
        });
    }

    private function createPurchaseOrder(MaterialRequirement $req, array $group, array $items): PurchaseOrder
    {
        $currency = $group['currency'] ?: 'MXN';
        $taxRate = 16.0;
        $subtotal = round(array_sum(array_column($items, 'line_total')), 2);
        $taxTotal = round($subtotal * $taxRate / 100, 2);

        $expected = $req->needed_by_date
            ? $req->needed_by_date->copy()
            : ($group['lead_time_days'] ? now()->addDays((int) $group['lead_time_days']) : null);

        $po = PurchaseOrder::create([
            'po_number'              => PurchaseOrder::generatePoNumber(),
            'supplier_id'            => $group['supplier_id'],
            'created_by_user_id'     => auth()->id() ?? $req->created_by_user_id,
            'status'                 => 'pending',
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => $expected?->toDateString(),
            'internal_reference'     => PurchaseOrder::generateInternalReference(),
            'notes'                  => "Generada desde MRP {$req->number}",
            'currency'               => $currency,
            'exchange_rate'          => $currency === 'USD' ? Products::exchangeRate() : 1,
            'subtotal'               => $subtotal,
            'discount_total'         => 0,
            'tax_rate'               => $taxRate,
            'tax_total'              => $taxTotal,
            'total'                  => round($subtotal + $taxTotal, 2),
        ]);

        foreach ($items as $i => $it) {
            /** @var MaterialRequirementLine $line */
            $line = $it['line'];
            $product = $it['product'];
            $po->items()->create([
                'product_id'       => $line->product_id,
                'product_name'     => $product->name ?? ('Producto #' . $line->product_id),
                'product_sku'      => $product->sku ?? null,
                'comment'          => $req->number,
                'quantity'         => $it['quantity'],
                'unit_price'       => $it['unit_price'],
                'discount_percent' => 0,
                'line_total'       => $it['line_total'],
                'sort_order'       => $i,
            ]);
            $line->update(['purchase_order_id' => $po->id, 'status' => 'en_compra']);
        }

        DocumentFlow::link($req, $po);
        if (in_array($req->status, ['borrador', 'calculado', 'cubierto'], true)) {
            $req->update(['status' => 'en_compra']);
        }

        return $po;
    }

    // ───────────────────────── Proceso (chevrons) ─────────────────────────

    public const PROCESS_STEPS = [
        ['key' => 'planeacion', 'label' => 'Planeación'],
        ['key' => 'compras',    'label' => 'Compras'],
        ['key' => 'finanzas',   'label' => 'Finanzas'],
        ['key' => 'logistica',  'label' => 'Logística'],
    ];

    /**
     * Paso actual de la cadena Planeación → Compras → Finanzas → Logística.
     * Devuelve la llave del paso, o 'fin' cuando todo está entregado (todos hechos), o null si está cancelado.
     */
    public function processStep(MaterialRequirement $req): ?string
    {
        if ($req->status === 'cancelado') {
            return null;
        }
        if (in_array($req->status, ['borrador', 'calculado'], true)) {
            return 'planeacion';
        }

        $nodes = collect(DocumentFlow::flow($req));
        $done = fn (?string $s) => in_array(strtolower((string) $s), ['paid', 'pagado', 'delivered', 'entregado', 'completed', 'completado', 'received', 'recibido', 'applied', 'aplicado'], true);

        $shipments = $nodes->where('type', 'shipment');
        if ($shipments->isNotEmpty()) {
            return $shipments->every(fn ($n) => $done($n['status'])) ? 'fin' : 'logistica';
        }

        $pos = $nodes->where('type', 'purchase_order');
        if ($pos->isNotEmpty()) {
            if ($pos->contains(fn ($n) => strtolower((string) $n['status']) === 'pending')) {
                return 'compras';
            }
            $payables = $nodes->where('type', 'payable');
            $financeExists = class_exists(\App\Models\Payable::class) && Schema::hasTable('payables');
            if ($payables->isNotEmpty()) {
                return $payables->every(fn ($n) => $done($n['status'])) ? 'logistica' : 'finanzas';
            }
            if ($financeExists) {
                return 'finanzas';
            }
        }

        return 'logistica';
    }
}
