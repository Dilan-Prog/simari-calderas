<?php

namespace App\Services\Statistics;

use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use App\Models\SupplierProduct;
use Illuminate\Support\Collection;

/**
 * Compras / Proveedores: PurchaseOrder no tiene columna `received_at` — el
 * lead time real se deriva del InventoryMovement tipo `entrada` que
 * PurchaseOrderService::applyStockReceipt() crea en el momento exacto en
 * que la orden pasa a `accepted` (reference_type=PurchaseOrder::class),
 * sin necesidad de una columna nueva.
 */
class ComprasStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $orders = PurchaseOrder::with('supplier')->whereBetween('created_at', [$from, $to])->get();
        $accepted = $orders->where('status', 'accepted');

        $gastoTotal = (float) $accepted->sum('total');
        $proveedoresActivos = $accepted->pluck('supplier_id')->unique()->filter()->count();

        $leadTimeDays = $this->leadTimeDaysPorOrden($accepted);
        $leadTimes = $this->leadTimesPorProveedor($accepted, $leadTimeDays);
        // Promedio ponderado por orden, no promedio de los promedios por
        // proveedor (eso sesgaría el número hacia proveedores con pocas
        // órdenes cuando el volumen varía entre proveedores).
        $leadTimeRealPromedio = $leadTimeDays->isNotEmpty() ? round($leadTimeDays->avg(), 1) : 0.0;

        $kpis = [
            [
                'label' => 'Gasto total', 'value' => StatisticsFormatter::compactMoney($gastoTotal),
                'trend' => $accepted->count() . ' órdenes aceptadas', 'trendUp' => true,
                'hint' => $orders->count() . ' órdenes de compra', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 9H8L6 6zM6 6L5 3H2"/></svg>',
            ],
            [
                'label' => 'Órdenes recibidas', 'value' => (string) $accepted->count(),
                'trend' => $orders->count() > 0 ? round($accepted->count() / $orders->count() * 100) . '% del total' : '0%',
                'trendUp' => true, 'hint' => 'del periodo', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 6.5"/></svg>',
            ],
            [
                'label' => 'Lead time real', 'value' => $leadTimeRealPromedio . ' d',
                'trend' => 'orden → recepción', 'trendUp' => true,
                'hint' => 'promedio de orden a recepción', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 7v5l3.5 2"/><circle cx="12" cy="12" r="9"/></svg>',
            ],
            [
                'label' => 'Proveedores activos', 'value' => (string) $proveedoresActivos,
                'trend' => 'con al menos una orden', 'trendUp' => true,
                'hint' => 'con al menos una orden recibida', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M5 20a7 7 0 0114 0"/></svg>',
            ],
        ];

        $panels = [
            $this->gastoPorProveedorPanel($accepted),
            $this->ordenesPorEstatusPanel($orders),
            $this->leadTimePanel($leadTimes, $leadTimeRealPromedio),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function gastoPorProveedorPanel(Collection $accepted): array
    {
        $rows = $accepted->groupBy('supplier_id')
            ->map(fn (Collection $g) => [$g->first()->supplier->company_name ?? 'Sin proveedor', (float) $g->sum('total'), 'brand'])
            ->sortByDesc(fn ($r) => $r[1])->values()->take(20)->all();

        return [
            'id' => 'c1', 'title' => 'Gasto por proveedor', 'subtitle' => 'Órdenes de compra aceptadas del periodo',
            'modes' => ['bars', 'table', 'donut'], 'span' => true, 'money' => true, 'noPct' => true,
            'colLabel' => 'Proveedor', 'colValue' => 'Gasto', 'totalLabel' => 'MXN',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function ordenesPorEstatusPanel(Collection $orders): array
    {
        $labels = ['accepted' => 'Recibida', 'pending' => 'Pendiente', 'rejected' => 'Rechazada'];
        $colors = ['accepted' => 'ok', 'pending' => 'warn', 'rejected' => 'err'];
        $counts = $orders->countBy('status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, $counts->get($status, 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'c2', 'title' => 'Órdenes de compra por estatus', 'subtitle' => 'Ciclo de abastecimiento',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Órdenes', 'totalLabel' => 'órdenes',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    /**
     * Días reales por orden individual: (fecha del primer InventoryMovement
     * `entrada` referenciando la orden) − order_date. Base para el promedio
     * ponderado del KPI — nunca promediar esto agrupado por proveedor primero,
     * eso sesgaría el resultado hacia proveedores con pocas órdenes.
     */
    private function leadTimeDaysPorOrden(Collection $accepted): Collection
    {
        if ($accepted->isEmpty()) {
            return collect();
        }

        $movements = $this->entradaMovementsByOrder($accepted);

        return $accepted->map(function ($order) use ($movements) {
            $receivedAt = $movements->get($order->id);
            if (!$receivedAt || !$order->order_date) {
                return null;
            }
            return $order->order_date->diffInDays($receivedAt);
        })->filter(fn ($d) => $d !== null)->values();
    }

    private function entradaMovementsByOrder(Collection $accepted): Collection
    {
        return InventoryMovement::where('reference_type', PurchaseOrder::class)
            ->whereIn('reference_id', $accepted->pluck('id'))
            ->where('movement_type', 'entrada')
            ->get()
            ->groupBy('reference_id')
            ->map(fn (Collection $g) => $g->min('created_at'));
    }

    /**
     * Promedio por proveedor, para el desglose por fila del panel c3 (aquí sí
     * corresponde promediar dentro de cada proveedor — es lo que se muestra).
     */
    private function leadTimesPorProveedor(Collection $accepted, Collection $leadTimeDays): Collection
    {
        if ($accepted->isEmpty()) {
            return collect();
        }

        $movements = $this->entradaMovementsByOrder($accepted);

        $promised = SupplierProduct::whereNotNull('lead_time_days')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn (Collection $g) => $g->avg('lead_time_days'));

        return $accepted->groupBy('supplier_id')->map(function (Collection $group) use ($movements, $promised) {
            $first = $group->first();
            $realDays = $group->map(function ($order) use ($movements) {
                $receivedAt = $movements->get($order->id);
                if (!$receivedAt || !$order->order_date) {
                    return null;
                }
                return $order->order_date->diffInDays($receivedAt);
            })->filter(fn ($d) => $d !== null);

            return [
                'label' => $first->supplier->company_name ?? 'Sin proveedor',
                'real' => $realDays->isNotEmpty() ? round($realDays->avg(), 1) : null,
                // isset() ya excluye null; comparar con !== null aparte solo hace
                // falta abajo, donde 0 (entrega el mismo día, valor válido) no debe
                // tratarse como ausente.
                'promised' => isset($promised[$first->supplier_id]) ? round($promised[$first->supplier_id], 1) : null,
            ];
        })->filter(fn ($r) => $r['real'] !== null)->values();
    }

    private function leadTimePanel(Collection $leadTimes, float $avgReal): array
    {
        $rows = $leadTimes->map(function ($r) {
            // $r['promised'] !== null (no truthy check): 0 es "entrega el mismo
            // día", un valor válido y distinto de "sin dato prometido".
            $color = $r['promised'] !== null && $r['real'] > $r['promised'] * 1.15 ? 'err' : ($r['promised'] !== null && $r['real'] > $r['promised'] ? 'warn' : 'ok');
            return [$r['label'], $r['real'], $color];
        })->sortByDesc(fn ($r) => $r[1])->values()->all();

        $avgPromised = $leadTimes->filter(fn ($r) => $r['promised'] !== null)->avg('promised');
        $avgPromised = $avgPromised !== null ? round($avgPromised, 1) : null;

        return [
            'id' => 'c3', 'title' => 'Lead time prometido vs. real', 'subtitle' => 'Días promedio por proveedor',
            'modes' => ['bars', 'table'], 'noPct' => true, 'colLabel' => 'Proveedor', 'colValue' => 'Días real', 'totalLabel' => 'días',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => $avgPromised !== null
                ? "Prometido promedio {$avgPromised} días · real {$avgReal} días (ponderado por orden). Se calcula de fecha de orden a la primera entrada de inventario registrada."
                : 'Se calcula de fecha de orden a la primera entrada de inventario registrada (sin datos de lead time prometido en el catálogo de proveedores).',
        ];
    }
}
