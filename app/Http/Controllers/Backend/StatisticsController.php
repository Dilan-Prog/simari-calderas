<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Statistics\ComprasStatsService;
use App\Services\Statistics\EmbudoStatsService;
use App\Services\Statistics\InventarioStatsService;
use App\Services\Statistics\ReportesStatsService;
use App\Services\Statistics\ResumenStatsService;
use App\Services\Statistics\ServiciosStatsService;
use App\Services\Statistics\StatisticsPeriodResolver;
use App\Services\Statistics\TiendaStatsService;
use App\Services\Statistics\VentasStatsService;
use Illuminate\Http\Request;

/**
 * Módulo "Estadísticas" — panel de BI cruzando Ventas, Compras, Servicios
 * Técnicos, Reportes de Servicio, Inventario, Tienda pública y el Embudo
 * de ventas (Pipeline/Deals del CRM). Independiente del motor de Reportes/
 * Dashboards del CRM (permission `crm-reports`) — no lo reemplaza ni
 * depende de él, salvo por la sección Embudo, que reutiliza por
 * composición los servicios ya existentes `PrebuiltReportService`/
 * `DealReportService` en vez de duplicar sus queries.
 *
 * Controller delgado a propósito: cada sección delega en un servicio de
 * dominio dedicado (`app/Services/Statistics/*StatsService`). Todos
 * devuelven el mismo shape:
 *   [
 *     'kpis'   => [ ['label','value','trend','trendUp','hint','color','icon'], ... 4 ],
 *     'panels' => [ ['id','title','subtitle','modes',...], ... ],
 *   ]
 * — ver StatisticsPanelData para la forma exacta de cada panel, consumida
 * 1:1 por resources/js/admin/statistics-panel.js.
 */
class StatisticsController extends Controller
{
    private const SECTIONS = [
        'resumen'    => ['title' => 'Resumen', 'subtitle' => 'Lo más relevante de los 6 dominios en el periodo seleccionado'],
        'ventas'     => ['title' => 'Ventas', 'subtitle' => 'Cotizaciones, conversión y pedidos B2B derivados'],
        'embudo'     => ['title' => 'Embudo de ventas', 'subtitle' => 'Conversión etapa a etapa, velocidad del ciclo y motivos de pérdida'],
        'compras'    => ['title' => 'Compras y proveedores', 'subtitle' => 'Gasto, órdenes de compra y cumplimiento de lead time'],
        'servicios'  => ['title' => 'Servicios técnicos', 'subtitle' => 'Órdenes de servicio, carga por técnico y cumplimiento'],
        'reportes'   => ['title' => 'Reportes de servicio', 'subtitle' => 'Mediciones fuera de rango y estatus de firma'],
        'inventario' => ['title' => 'Inventario', 'subtitle' => 'Movimientos por almacén, saldos y alertas de stock'],
        'tienda'     => ['title' => 'Tienda pública', 'subtitle' => 'Pedidos web PW y comparativa contra el canal B2B asistido'],
    ];

    public function index(Request $request)
    {
        return $this->render($request, 'resumen');
    }

    public function show(Request $request, string $section)
    {
        return $this->render($request, $section);
    }

    private function render(Request $request, string $section)
    {
        $period = StatisticsPeriodResolver::resolve(
            $request->query('period'),
            $request->boolean('compare'),
            $request->query('date_from'),
            $request->query('date_to')
        );

        $data = match ($section) {
            'ventas'     => app(VentasStatsService::class)->summary($period),
            'embudo'     => app(EmbudoStatsService::class)->summary($period),
            'compras'    => app(ComprasStatsService::class)->summary($period),
            'servicios'  => app(ServiciosStatsService::class)->summary($period),
            'reportes'   => app(ReportesStatsService::class)->summary($period),
            'inventario' => app(InventarioStatsService::class)->summary($period),
            'tienda'     => app(TiendaStatsService::class)->summary($period),
            default      => app(ResumenStatsService::class)->summary($period),
        };

        return view('admin.statistics.index', [
            'activeSection' => $section,
            'section'       => self::SECTIONS[$section] ?? self::SECTIONS['resumen'],
            'period'        => $period,
            'periods'       => StatisticsPeriodResolver::periods(),
            'kpis'          => $data['kpis'],
            'panels'        => $data['panels'],
            'domainCards'   => $section === 'resumen' ? ($data['domainCards'] ?? []) : [],
        ]);
    }
}
