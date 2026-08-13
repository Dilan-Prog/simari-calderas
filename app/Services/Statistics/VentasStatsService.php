<?php

namespace App\Services\Statistics;

use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\StoreOrder;
use Illuminate\Support\Collection;

/**
 * Ventas: Cotizaciones (Quote) + Pedidos B2B derivados (SalesOrder) +
 * comparativa de ingreso contra la tienda pública (StoreOrder). Nunca
 * suma SalesOrder como ingreso — no tiene columna de monto propia, su
 * valor vive en la Quote de origen (ver StatisticsController).
 */
class VentasStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $quotesInPeriod = Quote::whereBetween('created_at', [$from, $to])->get();
        $acceptedInPeriod = $quotesInPeriod->where('status', 'accepted');

        $acceptedTotal = (float) $acceptedInPeriod->sum('total');
        $quotedTotal = (float) $quotesInPeriod->sum('total');
        $sentOrLater = $quotesInPeriod->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])->count();
        $conversionPct = $sentOrLater > 0 ? round($acceptedInPeriod->count() / $sentOrLater * 100, 1) : 0.0;

        $storeTotal = (float) StoreOrder::whereBetween('created_at', [$from, $to])->sum('total');

        $salesOrdersOpen = SalesOrder::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['pendiente', 'en_preparacion'])
            ->count();

        $kpis = [
            [
                'label' => 'Monto cotizado', 'value' => StatisticsFormatter::compactMoney($quotedTotal),
                'trend' => $quotesInPeriod->count() . ' cotizaciones creadas', 'trendUp' => true,
                'hint' => $quotesInPeriod->count() . ' cotizaciones creadas', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 3 8-9"/></svg>',
            ],
            [
                'label' => 'Monto aceptado', 'value' => StatisticsFormatter::compactMoney($acceptedTotal),
                'trend' => $acceptedInPeriod->count() . ' ganadas', 'trendUp' => true,
                'hint' => $acceptedInPeriod->count() . ' cotizaciones ganadas', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 6.5"/></svg>',
            ],
            [
                'label' => 'Tasa de conversión', 'value' => StatisticsFormatter::pct($conversionPct, 1),
                'trend' => 'aceptadas / enviadas', 'trendUp' => $conversionPct >= 40,
                'hint' => 'aceptadas / enviadas+cerradas', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19V6M4 19h16M8 16v-5M12 16V8M16 16v-3"/></svg>',
            ],
            [
                'label' => 'Pedidos abiertos', 'value' => (string) $salesOrdersOpen,
                'trend' => 'pendiente + en preparación', 'trendUp' => true,
                'hint' => 'pendiente + en preparación', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v13H4zM8 7V4.5h8V7"/></svg>',
            ],
        ];

        $panels = [
            $this->ingresoCombinadoPanel($from, $to),
            $this->tendenciaMontoPanel(),
            $this->cotizacionesPorEstatusPanel($quotesInPeriod),
            $this->rankingClientesPanel($acceptedInPeriod),
            $this->cerradasVsNoCerradasPanel($quotesInPeriod),
            $this->pedidosDerivadosPanel($from, $to),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function ingresoCombinadoPanel($from, $to): array
    {
        $b2b = (float) Quote::where('status', 'accepted')->whereBetween('created_at', [$from, $to])->sum('total');
        $tienda = (float) StoreOrder::whereBetween('created_at', [$from, $to])->sum('total');

        return [
            'id' => 'v0', 'title' => 'Ingreso combinado por canal',
            'subtitle' => 'Cotizaciones aceptadas (B2B) + pedidos del checkout público',
            'modes' => ['stacked', 'bars', 'table'], 'span' => true, 'money' => true, 'totalLabel' => 'Ingreso combinado',
            'colLabel' => 'Canal', 'colValue' => 'Ingreso',
            'rows' => [
                ['B2B asistido · cotizaciones aceptadas', $b2b, 'brand'],
                ['Tienda pública · pedidos web', $tienda, 'info'],
            ],
            'foot' => 'Fuente única por canal: el ingreso B2B sale del total de la cotización aceptada, no del pedido derivado (que no tiene monto propio), para no contar el mismo ingreso dos veces.',
        ];
    }

    private function tendenciaMontoPanel(): array
    {
        $months = StatisticsPeriodResolver::trailingMonths(6);
        $quoted = [];
        $accepted = [];

        foreach ($months as $m) {
            $quoted[] = round((float) Quote::whereBetween('created_at', [$m['from'], $m['to']])->sum('total') / 1_000_000, 2);
            $accepted[] = round((float) Quote::where('status', 'accepted')->whereBetween('created_at', [$m['from'], $m['to']])->sum('total') / 1_000_000, 2);
        }

        return [
            'id' => 'v1', 'title' => 'Monto cotizado por mes', 'subtitle' => 'Monto cotizado vs. monto aceptado (últimos 6 meses)',
            'modes' => ['line', 'columns', 'table'], 'span' => true,
            'axis' => array_column($months, 'label'), 'series' => $quoted, 'compareSeries' => $accepted,
            'compareLabel' => 'Monto aceptado', 'unit' => 'M',
            'colLabel' => 'Mes', 'colValue' => 'Cotizado (M)', 'noPct' => true,
            'rows' => array_map(fn ($lbl, $v) => [$lbl, $v, 'brand'], array_column($months, 'label'), $quoted),
            'foot' => 'Montos en millones MXN, convertidos con el tipo de cambio del documento.',
        ];
    }

    private function cotizacionesPorEstatusPanel(Collection $quotes): array
    {
        $labels = ['accepted' => 'Aceptada', 'sent' => 'Enviada', 'draft' => 'Borrador', 'rejected' => 'Rechazada', 'expired' => 'Expirada'];
        $colors = ['accepted' => 'ok', 'sent' => 'info', 'draft' => 'gray', 'rejected' => 'err', 'expired' => 'warn'];
        $counts = $quotes->countBy('status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, $counts->get($status, 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'v2', 'title' => 'Cotizaciones por estatus', 'subtitle' => 'Embudo documental del periodo',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Cotizaciones', 'totalLabel' => 'cotizaciones',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function rankingClientesPanel(Collection $acceptedQuotes): array
    {
        $grouped = $acceptedQuotes->groupBy(fn ($q) => $q->customer_id ? 'c:' . $q->customer_id : 'g:' . ($q->guest_email ?: $q->guest_name));

        $rows = $grouped->map(function (Collection $group) {
            $first = $group->first();
            $label = $first->guest_company ?: $first->guest_name;
            $segment = !empty($first->guest_company) ? 'Empresa (B2B)' : 'Particular';

            return [$label, (float) $group->sum('total'), $segment];
        })->sortByDesc(fn ($r) => $r[1])->values()->take(50)->all();

        return [
            'id' => 'v5', 'title' => 'Ranking de clientes por monto comprado',
            'subtitle' => 'Ordenable · segmento derivado de tipo_persona y del campo company',
            'modes' => ['rank', 'bars', 'table'], 'span' => true, 'rank' => true, 'money' => true,
            'colLabel' => 'Cliente', 'colValue' => 'Monto', 'segLabel' => 'Segmento',
            'filters' => ['Empresa (B2B)', 'Particular'],
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'El segmento no es un campo dedicado: "Empresa (B2B)" es que la cotización tenga razón social capturada (guest_company); el resto se clasifica como Particular.',
        ];
    }

    private function cerradasVsNoCerradasPanel(Collection $quotes): array
    {
        $decided = $quotes->whereIn('status', ['accepted', 'rejected', 'expired']);
        $rows = [
            ['Cerradas · aceptadas', $decided->where('status', 'accepted')->count(), 'ok'],
            ['No cerradas · rechazadas', $decided->where('status', 'rejected')->count(), 'err'],
            ['No cerradas · expiradas', $decided->where('status', 'expired')->count(), 'warn'],
        ];
        $rows = array_values(array_filter($rows, fn ($r) => $r[1] > 0));

        return [
            'id' => 'v6', 'title' => 'Cotizaciones cerradas vs. no cerradas', 'subtitle' => 'Aceptadas contra rechazadas + expiradas',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Resultado', 'colValue' => 'Cotizaciones', 'totalLabel' => 'cotizaciones',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'disabled' => 'Desglose por motivo de rechazo · el sistema no guarda ese campo hoy (mejora futura)',
        ];
    }

    private function pedidosDerivadosPanel($from, $to): array
    {
        $labels = ['entregado' => 'Entregado', 'en_preparacion' => 'En preparación', 'parcialmente_entregado' => 'Parcialmente entregado', 'pendiente' => 'Pendiente', 'cancelado' => 'Cancelado'];
        $colors = ['entregado' => 'ok', 'en_preparacion' => 'warn', 'parcialmente_entregado' => 'info', 'pendiente' => 'violet', 'cancelado' => 'err'];

        $counts = SalesOrder::whereBetween('created_at', [$from, $to])->get()->countBy('status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, $counts->get($status, 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 'v4', 'title' => 'Pedidos generados por cotización aceptada', 'subtitle' => 'Estatus de los pedidos derivados',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Pedidos', 'totalLabel' => 'pedidos',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }
}
