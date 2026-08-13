<?php

namespace App\Services\Statistics;

use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use Illuminate\Support\Collection;

class TiendaStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $orders = StoreOrder::whereBetween('created_at', [$from, $to])->get();
        $ingresoWeb = (float) $orders->sum('total');
        $ticketPromedio = $orders->count() > 0 ? $ingresoWeb / $orders->count() : 0.0;
        $conFactura = $orders->where('requires_invoice', true)->count();

        $kpis = [
            [
                'label' => 'Ingreso de tienda', 'value' => StatisticsFormatter::compactMoney($ingresoWeb),
                'trend' => $orders->count() . ' pedidos web', 'trendUp' => true,
                'hint' => 'del checkout público en el rango', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16l-1.5 10h-13L4 6zM4 6L3.5 3H1"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>',
            ],
            [
                'label' => 'Pedidos web', 'value' => (string) $orders->count(),
                'trend' => 'checkout público', 'trendUp' => true,
                'hint' => 'generados en el rango', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4.5l5 5-9 9H5.5v-5z"/></svg>',
            ],
            [
                'label' => 'Ticket promedio', 'value' => StatisticsFormatter::compactMoney($ticketPromedio),
                'trend' => 'ingreso / pedidos', 'trendUp' => true,
                'hint' => 'ingreso entre número de pedidos', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 7v5l3.5 2"/><circle cx="12" cy="12" r="9"/></svg>',
            ],
            [
                'label' => 'Con factura', 'value' => $orders->count() > 0 ? StatisticsFormatter::pct($conFactura / $orders->count() * 100) : '0%',
                'trend' => $conFactura . ' solicitaron CFDI', 'trendUp' => true,
                'hint' => $conFactura . ' de ' . $orders->count() . ' pedidos', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9l4 4v13H6z"/></svg>',
            ],
        ];

        $panels = [
            $this->tendenciaIngresoPanel(),
            $this->pedidosPorEstatusPanel($orders),
            $this->productosMasVendidosPanel($orders),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function tendenciaIngresoPanel(): array
    {
        $months = StatisticsPeriodResolver::trailingMonths(6);
        $series = [];

        foreach ($months as $m) {
            $series[] = (float) StoreOrder::whereBetween('created_at', [$m['from'], $m['to']])->sum('total');
        }

        return [
            'id' => 't1', 'title' => 'Ingreso de tienda por mes', 'subtitle' => 'Últimos 6 meses',
            'modes' => ['line', 'columns', 'table'], 'money' => true, 'noPct' => true,
            'axis' => array_column($months, 'label'), 'series' => $series,
            'colLabel' => 'Mes', 'colValue' => 'Ingreso',
            'rows' => array_map(fn ($lbl, $v) => [$lbl, $v, 'brand'], array_column($months, 'label'), $series),
        ];
    }

    private function pedidosPorEstatusPanel(Collection $orders): array
    {
        $labels = ['pendiente_pago' => 'Pendiente de pago', 'pagado' => 'Pagado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
        $colors = ['pendiente_pago' => 'warn', 'pagado' => 'ok', 'enviado' => 'info', 'entregado' => 'ok', 'cancelado' => 'err'];
        $counts = $orders->countBy('status');

        $rows = $counts->map(fn ($count, $status) => [$labels[$status] ?? ucfirst($status), $count, $colors[$status] ?? 'gray'])
            ->values()->all();

        return [
            'id' => 't2', 'title' => 'Pedidos web por estatus', 'subtitle' => 'Ciclo del pedido en el checkout público',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Pedidos', 'totalLabel' => 'pedidos',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function productosMasVendidosPanel(Collection $orders): array
    {
        $items = StoreOrderItem::whereIn('store_order_id', $orders->pluck('id'))->get();

        $rows = $items->groupBy('product_name')
            ->map(fn (Collection $g) => [$g->first()->product_name, (int) $g->sum('quantity'), 'brand'])
            ->sortByDesc(fn ($r) => $r[1])->values()->all();

        return [
            'id' => 't3', 'title' => 'Productos más y menos vendidos en tienda', 'subtitle' => 'Unidades vendidas vía checkout público',
            'modes' => ['rank', 'bars', 'table'], 'span' => true, 'rank' => true, 'noPct' => true,
            'colLabel' => 'Producto', 'colValue' => 'Unidades vendidas',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }
}
