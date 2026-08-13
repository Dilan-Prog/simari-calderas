<?php

namespace App\Services\Statistics;

use App\Models\InventoryMovement;
use App\Models\Products;
use App\Models\SalesOrderItem;
use Illuminate\Support\Collection;

class InventarioStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $movements = InventoryMovement::with('product')->whereBetween('created_at', [$from, $to])->get();
        $entradas = $movements->where('movement_type', 'entrada');
        $salidas = $movements->where('movement_type', 'salida');

        $lowStock = Products::whereNotNull('reorder_point')->whereColumn('stock', '<=', 'reorder_point')->get();

        $kpis = [
            [
                'label' => 'Movimientos del periodo', 'value' => (string) $movements->count(),
                'trend' => $entradas->count() . ' entradas · ' . $salidas->count() . ' salidas', 'trendUp' => true,
                'hint' => $entradas->count() . ' entradas · ' . $salidas->count() . ' salidas', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>',
            ],
            [
                'label' => 'Unidades ingresadas', 'value' => number_format((int) $entradas->sum('quantity')),
                'trend' => 'entradas de almacén', 'trendUp' => true,
                'hint' => 'unidades recibidas en el periodo', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>',
            ],
            [
                'label' => 'Unidades despachadas', 'value' => number_format((int) $salidas->sum('quantity')),
                'trend' => 'salidas de almacén', 'trendUp' => true,
                'hint' => 'unidades entregadas en el periodo', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg>',
            ],
            [
                'label' => 'Alertas de stock bajo', 'value' => (string) $lowStock->count(),
                'trend' => $lowStock->count() > 0 ? 'requieren reposición' : 'sin alertas activas', 'trendUp' => $lowStock->count() === 0,
                'hint' => 'productos en o bajo su punto de reorden', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5M12 17h.01M10.3 3.9L2.5 18h19z"/></svg>',
            ],
        ];

        $panels = [
            $this->movimientosPorTipoPanel($movements),
            $this->stockBajoPanel($lowStock),
            $this->productosMasPedidosPanel($from, $to),
            $this->productosMasMovidosPanel($movements),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function movimientosPorTipoPanel(Collection $movements): array
    {
        $labels = ['entrada' => 'Entradas', 'salida' => 'Salidas', 'ajuste' => 'Ajustes'];
        $colors = ['entrada' => 'ok', 'salida' => 'info', 'ajuste' => 'warn'];
        $counts = $movements->countBy('movement_type');

        $rows = collect($labels)->map(fn ($label, $type) => [$label, $counts->get($type, 0), $colors[$type]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'i1', 'title' => 'Movimientos por tipo', 'subtitle' => 'Entradas, salidas y ajustes del periodo',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Tipo', 'colValue' => 'Movimientos', 'totalLabel' => 'movimientos',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function stockBajoPanel(Collection $lowStock): array
    {
        $rows = $lowStock->map(fn ($p) => [$p->name, (int) $p->stock, $p->stock <= 0 ? 'err' : 'warn'])
            ->sortBy(fn ($r) => $r[1])->values()->take(20)->all();

        return [
            'id' => 'i2', 'title' => 'Alertas de stock bajo', 'subtitle' => 'Productos en o por debajo de su punto de reorden',
            'modes' => ['table', 'bars'], 'span' => true, 'noPct' => true,
            'colLabel' => 'Producto', 'colValue' => 'Stock actual', 'totalLabel' => 'unidades',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Solo considera productos con punto de reorden configurado en su ficha. Configúralo en Productos > Editar > Inventario para activar la alerta.',
        ];
    }

    private function productosMasPedidosPanel(string $from, string $to): array
    {
        $items = SalesOrderItem::whereHas('salesOrder', fn ($q) => $q->whereBetween('created_at', [$from, $to]))->get();

        $grouped = $items->groupBy(fn ($i) => $i->product_id ?: 'name:' . $i->product_name)
            // Tercer elemento vacío, no 'brand': en modo rank ese valor se
            // interpreta como etiqueta de segmento visible (renderRank()), no
            // como colorKey — mismo criterio que productosMasMovidosPanel().
            ->map(fn (Collection $g) => [$g->first()->product_name, (int) $g->sum('quantity_ordered'), '']);

        $rows = $grouped->sortByDesc(fn ($r) => $r[1])->values()->all();

        return [
            'id' => 'i3', 'title' => 'Productos más y menos pedidos', 'subtitle' => 'Cantidad pedida en Pedidos (línea de producto)',
            'modes' => ['rank', 'bars', 'table'], 'span' => true, 'rank' => true, 'noPct' => true,
            'colLabel' => 'Producto', 'colValue' => 'Unidades pedidas',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function productosMasMovidosPanel(Collection $movements): array
    {
        $withProduct = $movements->whereNotNull('product_id');
        $grouped = $withProduct->groupBy('product_id')->map(function (Collection $g) {
            $product = $g->first()->product;
            return [$product->name ?? 'Producto eliminado', (int) $g->sum(fn ($m) => abs($m->quantity)), ''];
        });

        $rows = $grouped->sortByDesc(fn ($r) => $r[1])->values()->all();

        return [
            'id' => 'i4', 'title' => 'Productos con más y menos movimiento', 'subtitle' => 'Volumen combinado de entradas, salidas y ajustes',
            'modes' => ['rank', 'bars', 'table'], 'span' => true, 'rank' => true, 'noPct' => true,
            'colLabel' => 'Producto', 'colValue' => 'Movimiento (unidades)',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }
}
