<?php

namespace App\Services\Statistics;

use App\Models\Deal;
use App\Models\Products;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\ServiceReport;
use App\Models\StoreOrder;
use App\Models\TechnicalService;
use Illuminate\Support\Collection;

/**
 * Landing del módulo — a propósito NO depende de que los otros 7
 * *StatsService estén completos: hace sus propias queries breves para los
 * 4 paneles destacados + las 7 tarjetas de acceso a dominio, en vez de
 * componer sobre los servicios de cada sección.
 */
class ResumenStatsService
{
    private const CHIPS = [
        'brand'  => ['rgba(249,115,22,.1)', '#ea580c'],
        'info'   => ['rgba(59,130,246,.1)', '#2563eb'],
        'ok'     => ['rgba(34,197,94,.1)', '#16a34a'],
        'violet' => ['rgba(168,85,247,.1)', '#9333ea'],
    ];

    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $ingresoB2B = (float) Quote::where('status', 'accepted')->whereBetween('created_at', [$from, $to])->sum('total');
        $ingresoTienda = (float) StoreOrder::whereBetween('created_at', [$from, $to])->sum('total');
        $ingresoCombinado = $ingresoB2B + $ingresoTienda;

        $serviciosAtendidos = TechnicalService::whereBetween('created_at', [$from, $to])->count();
        $pedidosAbiertos = SalesOrder::whereIn('status', ['pendiente', 'en_preparacion'])->count();
        $lowStock = Products::whereNotNull('reorder_point')->whereColumn('stock', '<=', 'reorder_point')->count();

        $kpis = [
            [
                'label' => 'Ingreso combinado', 'value' => StatisticsFormatter::compactMoney($ingresoCombinado),
                'trend' => 'B2B + Tienda pública', 'trendUp' => true,
                'hint' => 'B2B ' . StatisticsFormatter::compactMoney($ingresoB2B) . ' · Tienda ' . StatisticsFormatter::compactMoney($ingresoTienda), 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 9H8L6 6zM6 6L5 3H2"/></svg>',
            ],
            [
                'label' => 'Servicios técnicos atendidos', 'value' => (string) $serviciosAtendidos,
                'trend' => 'en el rango seleccionado', 'trendUp' => true,
                'hint' => 'en el rango seleccionado', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4.5l5 5-9 9H5.5v-5z"/></svg>',
            ],
            [
                'label' => 'Pedidos abiertos', 'value' => (string) $pedidosAbiertos,
                'trend' => 'pendientes o en preparación', 'trendUp' => true,
                'hint' => 'total actual, no depende del rango', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.5l-8.5-5-8.5 5v7l8.5 5 8.5-5z"/></svg>',
            ],
            [
                'label' => 'Alertas de inventario', 'value' => (string) $lowStock,
                'trend' => $lowStock > 0 ? 'requieren reposición' : 'sin alertas activas', 'trendUp' => $lowStock === 0,
                'hint' => 'productos en o bajo su punto de reorden', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5M12 17h.01M10.3 3.9L2.5 18h19z"/></svg>',
            ],
        ];

        $panels = [
            $this->ingresoCombinadoPanel($ingresoB2B, $ingresoTienda),
            $this->pedidosPorEstatusPanel(),
            $this->serviciosPorEstatusPanel($from, $to),
            $this->alertasInventarioPanel(),
        ];

        return [
            'kpis' => $kpis,
            'panels' => $panels,
            'domainCards' => $this->domainCards($from, $to),
        ];
    }

    private function ingresoCombinadoPanel(float $b2b, float $tienda): array
    {
        $rows = array_values(array_filter([
            ['B2B (cotizaciones aceptadas)', $b2b, 'brand'],
            ['Tienda pública', $tienda, 'info'],
        ], fn ($r) => $r[1] > 0));

        return [
            'id' => 'r1', 'title' => 'Ingreso combinado', 'subtitle' => 'Cotizaciones B2B aceptadas + pedidos del checkout público',
            'modes' => ['stacked', 'donut', 'table'], 'money' => true, 'totalLabel' => 'Total',
            'colLabel' => 'Canal', 'colValue' => 'Ingreso',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Los Pedidos internos (SalesOrder) no se suman aparte: su monto ya vive en la cotización de origen.',
        ];
    }

    private function pedidosPorEstatusPanel(): array
    {
        $labels = ['entregado' => 'Entregado', 'en_preparacion' => 'En preparación', 'parcialmente_entregado' => 'Parcialmente entregado', 'pendiente' => 'Pendiente', 'cancelado' => 'Cancelado'];
        $colors = ['entregado' => 'ok', 'en_preparacion' => 'warn', 'parcialmente_entregado' => 'info', 'pendiente' => 'violet', 'cancelado' => 'err'];
        $counts = SalesOrder::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, (int) ($counts[$status] ?? 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'r2', 'title' => 'Pedidos por estatus', 'subtitle' => 'Todos los pedidos internos (Ventas B2B), no filtrado por rango',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Pedidos', 'totalLabel' => 'pedidos',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function serviciosPorEstatusPanel(string $from, string $to): array
    {
        $labels = ['completed' => 'Completado', 'in_progress' => 'En curso', 'scheduled' => 'Programado', 'draft' => 'Borrador', 'cancelled' => 'Cancelado'];
        $colors = ['completed' => 'ok', 'in_progress' => 'warn', 'scheduled' => 'info', 'draft' => 'gray', 'cancelled' => 'err'];
        $counts = TechnicalService::whereBetween('created_at', [$from, $to])->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, (int) ($counts[$status] ?? 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'r3', 'title' => 'Servicios técnicos por estatus', 'subtitle' => 'Del periodo seleccionado',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Servicios', 'totalLabel' => 'servicios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function alertasInventarioPanel(): array
    {
        $rows = Products::whereNotNull('reorder_point')->whereColumn('stock', '<=', 'reorder_point')
            ->orderBy('stock')->limit(10)->get()
            ->map(fn ($p) => [$p->name, (int) $p->stock, $p->stock <= 0 ? 'err' : 'warn'])->all();

        return [
            'id' => 'r4', 'title' => 'Alertas de inventario', 'subtitle' => 'Productos en o bajo su punto de reorden',
            'modes' => ['table', 'bars'], 'span' => true, 'noPct' => true,
            'colLabel' => 'Producto', 'colValue' => 'Stock actual', 'totalLabel' => 'unidades',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Configura el punto de reorden en Productos > Editar > Inventario para activar la alerta de un producto.',
        ];
    }

    private function domainCards(string $from, string $to): array
    {
        $ingresoAceptado = (float) Quote::where('status', 'accepted')->whereBetween('created_at', [$from, $to])->sum('total');
        $negociosAbiertos = Deal::where('status', 'open')->count();
        $gastoCompras = (float) PurchaseOrder::where('status', 'accepted')->whereBetween('created_at', [$from, $to])->sum('total');
        $servicios = TechnicalService::whereBetween('created_at', [$from, $to])->count();
        $reportes = ServiceReport::whereBetween('created_at', [$from, $to])->count();
        $lowStock = Products::whereNotNull('reorder_point')->whereColumn('stock', '<=', 'reorder_point')->count();
        $ingresoTienda = (float) StoreOrder::whereBetween('created_at', [$from, $to])->sum('total');

        $cards = [
            ['id' => 'ventas', 'title' => 'Ventas', 'text' => 'Cotizaciones, clientes y conversión', 'value' => StatisticsFormatter::compactMoney($ingresoAceptado), 'trend' => 'monto aceptado', 'color' => 'brand', 'icon' => $this->icon('trending-up')],
            ['id' => 'embudo', 'title' => 'Embudo de ventas', 'text' => 'Conversión etapa a etapa', 'value' => (string) $negociosAbiertos, 'trend' => 'negocios abiertos', 'color' => 'violet', 'icon' => $this->icon('filter')],
            ['id' => 'compras', 'title' => 'Compras', 'text' => 'Gasto y proveedores', 'value' => StatisticsFormatter::compactMoney($gastoCompras), 'trend' => 'gasto aceptado', 'color' => 'info', 'icon' => $this->icon('shopping-cart')],
            ['id' => 'servicios', 'title' => 'Servicios técnicos', 'text' => 'Órdenes y carga por técnico', 'value' => (string) $servicios, 'trend' => 'atendidos en el rango', 'color' => 'ok', 'icon' => $this->icon('wrench')],
            ['id' => 'reportes', 'title' => 'Reportes de servicio', 'text' => 'Mediciones y firma', 'value' => (string) $reportes, 'trend' => 'emitidos en el rango', 'color' => 'brand', 'icon' => $this->icon('file-text')],
            ['id' => 'inventario', 'title' => 'Inventario', 'text' => 'Movimientos y stock', 'value' => (string) $lowStock, 'trend' => 'alertas activas', 'color' => 'violet', 'icon' => $this->icon('package')],
            ['id' => 'tienda', 'title' => 'Tienda pública', 'text' => 'Pedidos web e ingreso', 'value' => StatisticsFormatter::compactMoney($ingresoTienda), 'trend' => 'ingreso en el rango', 'color' => 'info', 'icon' => $this->icon('store')],
        ];

        return array_map(function ($card) {
            [$chipBg, $chipColor] = self::CHIPS[$card['color']] ?? self::CHIPS['brand'];
            return $card + ['chipBg' => $chipBg, 'chipColor' => $chipColor];
        }, $cards);
    }

    private function icon(string $kind): string
    {
        $icons = [
            'trending-up' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8M15 7h6v6"/></svg>',
            'filter' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16l-6 8v6l-4-2v-4z"/></svg>',
            'shopping-cart' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 9H8L6 6zM6 6L5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>',
            'wrench' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4.5l5 5-9 9H5.5v-5z"/></svg>',
            'file-text' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9l4 4v13H6z"/></svg>',
            'package' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>',
            'store' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9l1-5h14l1 5M4 9v10h16V9M4 9a2 2 0 004 0 2 2 0 004 0 2 2 0 004 0 2 2 0 004 0"/></svg>',
        ];

        return $icons[$kind] ?? $icons['package'];
    }
}
