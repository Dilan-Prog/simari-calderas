{{--
    Diagrama de Gantt del programa de monitoreo (renderizado en servidor, sin JS).
    Uso: <x-monitoring.gantt :data="$gantt" />   ($gantt = MonitoringService::gantt())
--}}
@props(['data' => []])

@php
    $weeks    = $data['weeks'] ?? [];
    $rows     = $data['rows'] ?? [];
    $services = $data['services'] ?? [];
    $program  = $data['program'] ?? null;
    $nWeeks   = max(1, count($weeks));
    $totalDays = $nWeeks * 7;

    $startDay = $program ? \Carbon\Carbon::parse($program['start'])->startOfDay() : null;
    $dayIdx = function (string $date) use ($startDay): int {
        return (int) $startDay->diffInDays(\Carbon\Carbon::parse($date)->startOfDay(), false);
    };
    $pct = fn (float $dayPos): string => rtrim(rtrim(number_format($dayPos / $totalDays * 100, 3, '.', ''), '0'), '.');
    $fmt = fn (string $d): string => \Carbon\Carbon::parse($d)->locale('es')->isoFormat('D MMM');

    $todayPct = null;
    if ($startDay) {
        $ti = (int) $startDay->diffInDays(\Carbon\Carbon::today(), false);
        if ($ti >= 0 && $ti < $totalDays) {
            $todayPct = $pct($ti + 0.5);
        }
    }
    $uid = 'mg' . substr(md5(json_encode($program) . count($rows)), 0, 6);
@endphp

<div class="mg-root" id="{{ $uid }}" style="--mg-weeks: {{ $nWeeks }};">
    <style>
        .mg-root { --mg-label: 190px; --mg-col: 64px; --mg-param: #2563eb; --mg-param-bg: #dbeafe; --mg-chem: #0f766e; --mg-chem-bg: #ccfbf1; --mg-line: #e2e8f0; --mg-text: #0f172a; --mg-muted: #64748b; --mg-bad: #dc2626; --mg-today: #f59e0b; font-family: inherit; color: var(--mg-text); }
        .mg-root * { box-sizing: border-box; }
        .mg-scroll { overflow-x: auto; border: 1px solid var(--mg-line); border-radius: 10px; background: #fff; }
        .mg-table { min-width: calc(var(--mg-label) + var(--mg-weeks) * var(--mg-col)); }
        .mg-row { display: grid; grid-template-columns: var(--mg-label) 1fr; border-bottom: 1px solid var(--mg-line); }
        .mg-row:last-child { border-bottom: 0; }
        .mg-label { position: sticky; left: 0; z-index: 3; background: #fff; padding: 8px 10px; border-right: 1px solid var(--mg-line); display: flex; flex-direction: column; justify-content: center; gap: 3px; min-width: 0; }
        .mg-label-name { font-size: 13px; font-weight: 600; line-height: 1.25; overflow-wrap: anywhere; }
        .mg-label-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; font-size: 11px; color: var(--mg-muted); }
        .mg-badge { display: inline-block; padding: 1px 7px; border-radius: 999px; font-size: 10.5px; font-weight: 700; letter-spacing: .02em; }
        .mg-badge--parameter { background: var(--mg-param-bg); color: #1e40af; }
        .mg-badge--chemical { background: var(--mg-chem-bg); color: #115e59; }
        .mg-badge--oor { background: #fee2e2; color: #991b1b; }
        .mg-badge--staff { background: #f1f5f9; color: #475569; }
        .mg-head { background: #f8fafc; }
        .mg-head .mg-label { background: #f8fafc; font-size: 12px; font-weight: 700; color: var(--mg-muted); text-transform: uppercase; letter-spacing: .04em; }
        .mg-weeks { display: grid; grid-template-columns: repeat(var(--mg-weeks), 1fr); }
        .mg-week { padding: 6px 4px; text-align: center; border-left: 1px solid var(--mg-line); font-size: 12px; font-weight: 700; line-height: 1.2; }
        .mg-week:first-child { border-left: 0; }
        .mg-week small { display: block; font-weight: 400; font-size: 10.5px; color: var(--mg-muted); white-space: nowrap; }
        .mg-week.is-current { background: #fef3c7; color: #92400e; }
        .mg-week.is-current small { color: #92400e; }
        .mg-track { position: relative; min-height: 46px; background-image: repeating-linear-gradient(to right, transparent 0, transparent calc(100% / var(--mg-weeks) - 1px), var(--mg-line) calc(100% / var(--mg-weeks) - 1px), var(--mg-line) calc(100% / var(--mg-weeks))); }
        .mg-track--services { min-height: 44px; }
        .mg-today { position: absolute; top: 0; bottom: 0; width: 0; border-left: 2px dashed var(--mg-today); z-index: 2; pointer-events: none; }
        .mg-today-tag { position: absolute; top: 0; transform: translateX(-50%); background: var(--mg-today); color: #fff; font-size: 9.5px; font-weight: 700; padding: 0 5px; border-radius: 0 0 4px 4px; z-index: 2; white-space: nowrap; }
        .mg-bar { position: absolute; top: 6px; height: 22px; border-radius: 6px; overflow: hidden; border: 1px solid var(--mg-param); background: var(--mg-param-bg); }
        .mg-bar--chemical { border-color: var(--mg-chem); background: var(--mg-chem-bg); }
        .mg-fill { position: absolute; inset: 0 auto 0 0; background: var(--mg-param); opacity: .85; }
        .mg-bar--chemical .mg-fill { background: var(--mg-chem); }
        .mg-bar-text { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 7px; font-size: 11px; font-weight: 700; color: #0f172a; text-shadow: 0 0 3px #fff, 0 0 3px #fff; white-space: nowrap; }
        .mg-mark { position: absolute; top: 32px; width: 8px; height: 8px; margin-left: -4px; border-radius: 50%; background: var(--mg-param); border: 1px solid #fff; z-index: 1; }
        .mg-mark--chemical { background: var(--mg-chem); border-radius: 2px; transform: rotate(45deg); }
        .mg-oor-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--mg-bad); }
        .mg-pin { position: absolute; top: 4px; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; z-index: 1; font-size: 10px; line-height: 1.1; color: #334155; cursor: help; }
        .mg-pin-icon { width: 20px; height: 20px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); background: #7c3aed; border: 2px solid #fff; box-shadow: 0 0 0 1px #7c3aed; }
        .mg-pin--completed .mg-pin-icon { background: #16a34a; box-shadow: 0 0 0 1px #16a34a; }
        .mg-pin--cancelled .mg-pin-icon { background: #94a3b8; box-shadow: 0 0 0 1px #94a3b8; }
        .mg-pin--in_progress .mg-pin-icon { background: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        .mg-pin-date { margin-top: 2px; font-weight: 700; white-space: nowrap; }
        .mg-empty { padding: 28px 16px; text-align: center; color: var(--mg-muted); font-size: 14px; border: 1px dashed var(--mg-line); border-radius: 10px; }
        .mg-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 10px; font-size: 12px; color: var(--mg-muted); align-items: center; }
        .mg-legend span { display: inline-flex; align-items: center; gap: 6px; }
        .mg-sw { display: inline-block; width: 18px; height: 10px; border-radius: 3px; border: 1px solid; }
        .mg-sw--param { background: var(--mg-param-bg); border-color: var(--mg-param); }
        .mg-sw--chem { background: var(--mg-chem-bg); border-color: var(--mg-chem); }
        .mg-sw--fill { background: var(--mg-param); border-color: var(--mg-param); }
        .mg-sw--dot { width: 8px; height: 8px; border-radius: 50%; background: var(--mg-param); border: 0; }
        .mg-sw--today { width: 0; height: 12px; border: 0; border-left: 2px dashed var(--mg-today); border-radius: 0; }
        .mg-sw--pin { width: 10px; height: 10px; border: 0; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); background: #7c3aed; }
        .mg-svc-list { margin: 10px 0 0; padding: 0; list-style: none; font-size: 12px; color: var(--mg-muted); display: flex; flex-wrap: wrap; gap: 4px 14px; }
        .mg-svc-list strong { color: var(--mg-text); }
        @media (max-width: 640px) { .mg-root { --mg-label: 140px; } .mg-label-name { font-size: 12px; } }
        @media print {
            .mg-scroll { overflow: visible; border-color: #999; }
            .mg-table { min-width: 0; }
            .mg-label { position: static; }
            .mg-fill, .mg-bar, .mg-week.is-current, .mg-pin-icon, .mg-mark { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

    @if (empty($rows))
        <div class="mg-empty" role="status">Este programa aún no tiene parámetros ni químicos planeados, por lo que no hay diagrama que mostrar.</div>
    @else
        <div class="mg-scroll" role="group" aria-label="Diagrama de Gantt del programa{{ $program ? ' ' . $program['name'] : '' }}">
            <div class="mg-table">
                {{-- Encabezado de semanas --}}
                <div class="mg-row mg-head">
                    <div class="mg-label">Elemento</div>
                    <div class="mg-weeks">
                        @foreach ($weeks as $w)
                            <div class="mg-week {{ $w['is_current'] ? 'is-current' : '' }}">
                                Sem {{ $w['n'] }}{{ $w['is_current'] ? ' (actual)' : '' }}
                                <small>{{ $fmt($w['start']) }} – {{ $fmt($w['end']) }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Filas de elementos --}}
                @foreach ($rows as $row)
                    @php
                        $isChem = ($row['kind'] ?? '') === 'chemical';
                        $sw = max(1, (int) $row['start_week']);
                        $ew = min($nWeeks, max($sw, (int) $row['end_week']));
                        $visible = $sw <= $nWeeks && ($row['planned_days'] ?? 0) > 0;
                        $left = $pct(($sw - 1) * 7);
                        $width = $pct(($ew - $sw + 1) * 7);
                        $kindLabel = $isChem ? 'Químico' : 'Lectura';
                        $barTitle = $row['label'] . ' · ' . $kindLabel . ' · semanas ' . $sw . ' a ' . $ew
                            . ' · ' . $row['logged_days'] . ' de ' . $row['planned_days'] . ' días con registro'
                            . ' · avance ' . $row['progress'] . '%'
                            . (($row['out_of_range'] ?? 0) > 0 ? ' · ' . $row['out_of_range'] . ' fuera de rango' : '');
                    @endphp
                    <div class="mg-row">
                        <div class="mg-label">
                            <span class="mg-label-name">{{ $row['label'] }}{{ ! empty($row['unit']) ? ' (' . $row['unit'] . ')' : '' }}</span>
                            <span class="mg-label-meta">
                                <span class="mg-badge mg-badge--{{ $isChem ? 'chemical' : 'parameter' }}">{{ $kindLabel }}</span>
                                @if (($row['added_by'] ?? 'staff') === 'customer')
                                    <span class="mg-badge mg-badge--staff">Agregado por el cliente</span>
                                @endif
                                @if (($row['out_of_range'] ?? 0) > 0)
                                    <span class="mg-badge mg-badge--oor"><span class="mg-oor-dot" aria-hidden="true"></span> {{ $row['out_of_range'] }} fuera de rango</span>
                                @endif
                            </span>
                        </div>
                        <div class="mg-track">
                            @if ($todayPct !== null)
                                <div class="mg-today" style="left: {{ $todayPct }}%;" aria-hidden="true"></div>
                            @endif
                            @if ($visible)
                                <div class="mg-bar mg-bar--{{ $isChem ? 'chemical' : 'parameter' }}" style="left: {{ $left }}%; width: {{ $width }}%;" title="{{ $barTitle }}" role="img" aria-label="{{ $barTitle }}">
                                    <div class="mg-fill" style="width: {{ (int) $row['progress'] }}%;"></div>
                                    <div class="mg-bar-text"><span>{{ $row['progress'] }}%</span><span>{{ $row['logged_days'] }}/{{ $row['planned_days'] }} d</span></div>
                                </div>
                                @foreach (($row['marks'] ?? []) as $date => $count)
                                    @php $di = $dayIdx($date); @endphp
                                    @if ($di >= 0 && $di < $totalDays)
                                        <span class="mg-mark mg-mark--{{ $isChem ? 'chemical' : 'parameter' }}" style="left: {{ $pct($di + 0.5) }}%;" title="{{ $fmt($date) }}: {{ $count }} {{ $isChem ? ($count === 1 ? 'dosificación' : 'dosificaciones') : ($count === 1 ? 'lectura' : 'lecturas') }}"></span>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Visitas de servicio --}}
                @if (! empty($services) || array_key_exists('services', $data))
                    <div class="mg-row">
                        <div class="mg-label">
                            <span class="mg-label-name">Visitas de servicio</span>
                            <span class="mg-label-meta">{{ count($services) }} {{ count($services) === 1 ? 'visita' : 'visitas' }}</span>
                        </div>
                        <div class="mg-track mg-track--services">
                            @if ($todayPct !== null)
                                <div class="mg-today" style="left: {{ $todayPct }}%;" aria-hidden="true"></div>
                            @endif
                            @foreach ($services as $s)
                                @php
                                    $si = $dayIdx($s['date']);
                                    $sTitle = $s['label'] . ' · ' . $s['status_label'] . ' · ' . \Carbon\Carbon::parse($s['date'])->format('d/m/Y');
                                @endphp
                                @if ($si >= 0 && $si < $totalDays)
                                    <div class="mg-pin mg-pin--{{ $s['status'] }}" style="left: {{ $pct($si + 0.5) }}%;" title="{{ $sTitle }}" role="img" aria-label="{{ $sTitle }}">
                                        <span class="mg-pin-icon" aria-hidden="true"></span>
                                        <span class="mg-pin-date">{{ $fmt($s['date']) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if (! empty($services))
            <ul class="mg-svc-list" aria-label="Detalle de visitas de servicio">
                @foreach ($services as $s)
                    <li><strong>{{ \Carbon\Carbon::parse($s['date'])->format('d/m/Y') }}</strong> · Sem {{ $s['week'] }} · {{ $s['label'] }} ({{ $s['status_label'] }})</li>
                @endforeach
            </ul>
        @endif

        <div class="mg-legend" aria-label="Leyenda">
            <span><i class="mg-sw mg-sw--param"></i> Lectura de parámetro (barra planeada)</span>
            <span><i class="mg-sw mg-sw--chem"></i> Dosificación de químico</span>
            <span><i class="mg-sw mg-sw--fill"></i> Avance = días con registro / días transcurridos</span>
            <span><i class="mg-sw mg-sw--dot"></i> Día con registro</span>
            <span><i class="mg-oor-dot"></i> Registros fuera de rango</span>
            @if ($todayPct !== null)
                <span><i class="mg-sw mg-sw--today"></i> Hoy</span>
            @endif
            <span><i class="mg-sw mg-sw--pin"></i> Visita de servicio técnico</span>
        </div>
    @endif
</div>
