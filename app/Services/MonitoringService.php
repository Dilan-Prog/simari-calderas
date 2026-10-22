<?php

namespace App\Services;

use App\Models\MonitoringEntry;
use App\Models\MonitoringProgram;
use App\Models\MonitoringProgramItem;
use App\Models\TechnicalService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculos y agregados del módulo "Monitoreo de agua/químicos":
 * resúmenes semanales, series para gráficas, diagrama de Gantt, alertas y KPIs de un programa.
 * Todas las consultas agrupan en PHP/SQL sobre una sola lectura (sin N+1).
 */
class MonitoringService
{
    /** Estados de servicio técnico que se muestran en el Gantt (todo menos borradores). */
    private const SERVICE_STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    /**
     * Resumen semana por semana (1..weeks) de un programa.
     * Por semana: conteo de lecturas/dosis, fuera de rango, días registrados vs esperados y el detalle
     * por parámetro (promedio/mín/máx) y por químico (total dosificado vs planeado).
     * `planned_total` = planned_daily_dose × días de la semana con la barra activa (en la semana
     * actual solo cuentan los días transcurridos, para poder comparar contra lo dosificado).
     *
     * @return array<int, array<string, mixed>>
     */
    public function weekSummaries(MonitoringProgram $p, ?int $equipmentId = null): array
    {
        $items = $p->items()->get()->keyBy('id');
        $today = Carbon::today();
        $currentWeek = $p->currentWeek();
        $startTs = $this->startTs($p);

        // Acumuladores por semana.
        $acc = [];
        foreach ($this->entryRows($p, $equipmentId) as $r) {
            $item = $items->get($r->item_id);
            if (! $item) {
                continue;
            }
            $date = substr((string) $r->entry_date, 0, 10);
            $w = $this->weekOfTs($startTs, $date);
            if ($w < 1 || $w > $p->weeks) {
                continue;
            }

            $acc[$w] ??= ['readings' => 0, 'doses' => 0, 'oor' => 0, 'dates' => [], 'items' => []];
            $a = &$acc[$w];
            $r->entry_type === MonitoringEntry::TYPE_DOSE ? $a['doses']++ : $a['readings']++;
            $a['dates'][$date] = true;

            $value = (float) $r->value;
            $oor = $r->in_range !== null && ! (bool) $r->in_range;
            if ($oor) {
                $a['oor']++;
            }

            $i = &$a['items'][$item->id];
            $i ??= ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null, 'oor' => 0, 'dates' => []];
            $i['count']++;
            $i['sum'] += $value;
            $i['min'] = $i['min'] === null ? $value : min($i['min'], $value);
            $i['max'] = $i['max'] === null ? $value : max($i['max'], $value);
            $i['oor'] += $oor ? 1 : 0;
            $i['dates'][$date] = true;
            unset($i, $a);
        }

        $out = [];
        for ($n = 1; $n <= $p->weeks; $n++) {
            $start = $p->weekStart($n);
            $end = $p->weekEnd($n);
            $isFuture = $start->gt($today);
            $isCurrent = $currentWeek === $n;
            $daysExpected = $isFuture ? 0 : ($isCurrent ? (int) $start->diffInDays($today) + 1 : 7);
            $a = $acc[$n] ?? null;

            $parameters = [];
            $chemicals = [];
            foreach ($items as $item) {
                $i = $a['items'][$item->id] ?? null;
                if (! $i) {
                    continue;
                }
                if ($item->kind === MonitoringProgramItem::KIND_CHEMICAL) {
                    $active = ($n >= $item->start_week && $n <= $item->end_week) ? $daysExpected : 0;
                    $chemicals[] = [
                        'item_id'       => $item->id,
                        'name'          => $item->name,
                        'unit'          => $item->unit,
                        'total'         => round($i['sum'], 3),
                        'planned_total' => $item->planned_daily_dose !== null
                            ? round($item->planned_daily_dose * $active, 3) : null,
                        'days'          => count($i['dates']),
                    ];
                } else {
                    $parameters[] = [
                        'item_id'      => $item->id,
                        'name'         => $item->name,
                        'unit'         => $item->unit,
                        'avg'          => round($i['sum'] / $i['count'], 3),
                        'min'          => $i['min'],
                        'max'          => $i['max'],
                        'count'        => $i['count'],
                        'out_of_range' => $i['oor'],
                        'limit_min'    => $item->limit_min,
                        'limit_max'    => $item->limit_max,
                    ];
                }
            }

            $out[] = [
                'n'              => $n,
                'start'          => $start->toDateString(),
                'end'            => $end->toDateString(),
                'is_current'     => $isCurrent,
                'is_future'      => $isFuture,
                'readings_count' => $a['readings'] ?? 0,
                'doses_count'    => $a['doses'] ?? 0,
                'out_of_range'   => $a['oor'] ?? 0,
                'days_logged'    => $a ? count($a['dates']) : 0,
                'days_expected'  => $daysExpected,
                'parameters'     => $parameters,
                'chemicals'      => $chemicals,
            ];
        }

        return $out;
    }

    /**
     * Serie diaria de un elemento (promedio por día) para graficar.
     * Las etiquetas van del inicio del programa a min(fin, hoy), o todo el programa si aún no inicia.
     * Un día sin registros devuelve null (hueco en la línea).
     *
     * @return array<string, mixed>
     */
    public function chartSeries(MonitoringProgram $p, int $itemId, ?int $equipmentId = null): array
    {
        $item = $p->items()->where('id', $itemId)->first();
        $today = Carbon::today();
        $start = $p->start_date->copy()->startOfDay();
        $end = $p->endDate()->startOfDay();
        $last = $today->lt($start) ? $end : ($today->lt($end) ? $today : $end);

        $labels = [];
        for ($d = $start->copy(); $d->lte($last); $d->addDay()) {
            $labels[] = $d->toDateString();
        }

        $series = [
            'item_id'   => $itemId,
            'name'      => $item?->name ?? '',
            'unit'      => $item?->unit,
            'kind'      => $item?->kind ?? MonitoringProgramItem::KIND_PARAMETER,
            'labels'    => $labels,
            'values'    => array_fill(0, count($labels), null),
            'limit_min' => $item?->limit_min,
            'limit_max' => $item?->limit_max,
            'planned'   => $item && $item->isChemical() ? $item->planned_daily_dose : null,
            'points'    => 0,
            'out_of_range' => 0,
        ];
        if (! $item || ! $labels) {
            return $series;
        }

        $rows = DB::table('monitoring_entries')
            ->where('program_id', $p->id)
            ->where('item_id', $itemId)
            ->when($equipmentId, fn ($q) => $q->where('equipment_id', $equipmentId))
            ->whereBetween('entry_date', [$labels[0], end($labels)])
            ->groupBy('entry_date')
            ->selectRaw('entry_date, AVG(value) AS avg_value, COUNT(*) AS n, SUM(CASE WHEN in_range = 0 THEN 1 ELSE 0 END) AS oor')
            ->get();

        $index = array_flip($labels);
        foreach ($rows as $r) {
            $key = $index[substr((string) $r->entry_date, 0, 10)] ?? null;
            if ($key === null) {
                continue;
            }
            $series['values'][$key] = round((float) $r->avg_value, 3);
            $series['points'] += (int) $r->n;
            $series['out_of_range'] += (int) $r->oor;
        }

        return $series;
    }

    /**
     * Datos del diagrama de Gantt: semanas, una fila por elemento (barra start_week..end_week con
     * avance de registro, marcas por día y fuera de rango) y las visitas de servicio técnico del cliente.
     * `progress` = días con registro dentro de la barra / días de la barra transcurridos hasta hoy (0..100).
     *
     * @return array<string, mixed>
     */
    public function gantt(MonitoringProgram $p, bool $includeServices = true, ?int $equipmentId = null): array
    {
        $today = Carbon::today();
        $startTs = $this->startTs($p);
        $items = $p->items()->get();
        $currentWeek = $p->currentWeek();

        $weeks = [];
        for ($n = 1; $n <= $p->weeks; $n++) {
            $weeks[] = [
                'n'          => $n,
                'start'      => $p->weekStart($n)->toDateString(),
                'end'        => $p->weekEnd($n)->toDateString(),
                'is_current' => $currentWeek === $n,
            ];
        }

        // Marcas por elemento y fecha (solo dentro del programa).
        $byItem = [];
        foreach ($this->entryRows($p, $equipmentId) as $r) {
            $date = substr((string) $r->entry_date, 0, 10);
            $w = $this->weekOfTs($startTs, $date);
            if ($w < 1 || $w > $p->weeks) {
                continue;
            }
            $byItem[$r->item_id]['marks'][$date] = ($byItem[$r->item_id]['marks'][$date] ?? 0) + 1;
            if ($r->in_range !== null && ! (bool) $r->in_range) {
                $byItem[$r->item_id]['oor'] = ($byItem[$r->item_id]['oor'] ?? 0) + 1;
            }
        }

        $todayIdx =(int) $p->start_date->copy()->startOfDay()->diffInDays($today, false);

        $rows = [];
        foreach ($items as $item) {
            $sw = max(1, $item->start_week);
            $ew = min($p->weeks, max($sw, $item->end_week));
            $planned = $item->start_week > $p->weeks ? 0 : ($ew - $sw + 1) * 7;
            $barFrom = ($sw - 1) * 7;                // índice de día (0-based) donde inicia la barra
            $barTo = $barFrom + $planned - 1;        // último día de la barra
            $marks = $byItem[$item->id]['marks'] ?? [];
            ksort($marks);

            $logged = 0;
            foreach (array_keys($marks) as $date) {
                $idx = (int) (($this->dateTs($date) - $startTs) / 86400);
                if ($idx >= $barFrom && $idx <= $barTo) {
                    $logged++;
                }
            }

            $elapsed = $planned > 0 ? max(0, min($barTo, $todayIdx) - $barFrom + 1) : 0;
            $progress = $elapsed > 0 ? (int) min(100, round($logged / $elapsed * 100)) : 0;

            $rows[] = [
                'id'           => $item->id,
                'label'        => $item->name,
                'kind'         => $item->kind,
                'unit'         => $item->unit,
                'start_week'   => $item->start_week,
                'end_week'     => $item->end_week,
                'planned_days' => $planned,
                'logged_days'  => $logged,
                'out_of_range' => $byItem[$item->id]['oor'] ?? 0,
                'progress'     => $progress,
                'added_by'     => $item->added_by,
                'marks'        => $marks,
            ];
        }

        $services = [];
        if ($includeServices) {
            $list = TechnicalService::with('serviceType')
                ->where('customer_id', $p->customer_id)
                ->whereIn('status', self::SERVICE_STATUSES)
                ->whereBetween('service_date', [$p->start_date->toDateString(), $p->endDate()->toDateString()])
                ->orderBy('service_date')->orderBy('id')
                ->get();

            foreach ($list as $s) {
                $label = $s->service_type_label;
                $services[] = [
                    'id'           => $s->id,
                    'date'         => $s->service_date->toDateString(),
                    'week'         => $p->weekOf($s->service_date),
                    'label'        => ($label === '' || $label === '—') ? 'Servicio técnico' : $label,
                    'status'       => $s->status,
                    'status_label' => $s->status_label,
                ];
            }
        }

        return [
            'program' => [
                'id'    => $p->id,
                'name'  => $p->name,
                'start' => $p->start_date->toDateString(),
                'end'   => $p->endDate()->toDateString(),
                'weeks' => $p->weeks,
            ],
            'weeks'     => $weeks,
            'today_week' => $currentWeek,
            'rows'      => $rows,
            'services'  => $services,
        ];
    }

    /**
     * Registros fuera de rango recientes (más nuevos primero), con item, equipment y program.customer cargados.
     *
     * @return Collection<int, MonitoringEntry>
     */
    public function alerts(?int $customerId = null, int $days = 30, ?int $programId = null): Collection
    {
        return MonitoringEntry::query()
            ->with(['item', 'equipment', 'program.customer'])
            ->where('in_range', false)
            ->whereDate('entry_date', '>=', Carbon::today()->subDays($days)->toDateString())
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($customerId, fn ($q) => $q->whereHas('program', fn ($pq) => $pq->where('customer_id', $customerId)))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * KPIs de un programa: totales, fuera de rango, última fecha de registro y % de cumplimiento
     * (lecturas dentro de rango entre las lecturas que tienen rango; null si ninguna lo tiene).
     *
     * @return array{entries:int, readings:int, doses:int, out_of_range:int, last_entry_date:?string, compliance:?int}
     */
    public function programStats(MonitoringProgram $p): array
    {
        $r = DB::table('monitoring_entries')
            ->where('program_id', $p->id)
            ->selectRaw("COUNT(*) AS entries,
                SUM(CASE WHEN entry_type = 'reading' THEN 1 ELSE 0 END) AS readings,
                SUM(CASE WHEN entry_type = 'dose' THEN 1 ELSE 0 END) AS doses,
                SUM(CASE WHEN in_range = 0 THEN 1 ELSE 0 END) AS oor,
                SUM(CASE WHEN in_range = 1 THEN 1 ELSE 0 END) AS ok,
                MAX(entry_date) AS last_date")
            ->first();

        $ranged = (int) $r->ok + (int) $r->oor;

        return [
            'entries'         => (int) $r->entries,
            'readings'        => (int) $r->readings,
            'doses'           => (int) $r->doses,
            'out_of_range'    => (int) $r->oor,
            'last_entry_date' => $r->last_date ? substr((string) $r->last_date, 0, 10) : null,
            'compliance'      => $ranged > 0 ? (int) round($r->ok / $ranged * 100) : null,
        ];
    }

    // ---------------------------------------------------------------- helpers

    /** Lectura única (sin modelos) de los registros del programa. */
    private function entryRows(MonitoringProgram $p, ?int $equipmentId): Collection
    {
        return DB::table('monitoring_entries')
            ->where('program_id', $p->id)
            ->when($equipmentId, fn ($q) => $q->where('equipment_id', $equipmentId))
            ->select('item_id', 'entry_type', 'entry_date', 'value', 'in_range')
            ->get();
    }

    private function startTs(MonitoringProgram $p): int
    {
        return $this->dateTs($p->start_date->format('Y-m-d'));
    }

    private function dateTs(string $date): int
    {
        return (int) strtotime($date . ' 00:00:00 UTC');
    }

    /** Misma regla que MonitoringProgram::weekOf() pero sin parsear Carbon por registro. */
    private function weekOfTs(int $startTs, string $date): int
    {
        return (int) floor((($this->dateTs($date) - $startTs) / 86400) / 7) + 1;
    }
}
