{{--
    Gráfica de líneas SVG (renderizada en servidor, sin JS) de una serie de monitoreo.
    Uso: <x-monitoring.line-chart :series="$series" :height="220" />   ($series = MonitoringService::chartSeries())
--}}
@props(['series' => [], 'height' => 220])

@php
    $labels  = array_values($series['labels'] ?? []);
    $values  = array_values($series['values'] ?? []);
    $n       = count($labels);
    $points  = (int) ($series['points'] ?? 0);
    $oorCnt  = (int) ($series['out_of_range'] ?? 0);
    $name    = (string) ($series['name'] ?? '');
    $unit    = $series['unit'] ?? null;
    $isChem  = ($series['kind'] ?? 'parameter') === 'chemical';
    $lmin    = isset($series['limit_min']) ? (float) $series['limit_min'] : null;
    $lmax    = isset($series['limit_max']) ? (float) $series['limit_max'] : null;
    $planned = isset($series['planned']) ? (float) $series['planned'] : null;
    $title   = $name . ($unit ? ' (' . $unit . ')' : '');
    $noun    = $isChem ? ['dosificación', 'dosificaciones'] : ['lectura', 'lecturas'];
    $summary = $points . ' ' . ($points === 1 ? $noun[0] : $noun[1]) . ', ' . $oorCnt . ' fuera de rango';
    $fmtNum  = fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    $fmtDate = fn (string $d): string => \Carbon\Carbon::parse($d)->locale('es')->isoFormat('D MMM');

    // Geometría
    $W = 640; $H = max(140, (int) $height);
    $padL = 54; $padR = 18; $padT = 14; $padB = 34;
    $plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;

    $present = array_values(array_filter($values, fn ($v) => $v !== null));
    $domainVals = $present;
    foreach ([$lmin, $lmax, $planned] as $ref) { if ($ref !== null) { $domainVals[] = $ref; } }
    $hasData = $points > 0 && count($present) > 0;

    if ($hasData) {
        $yMin = min($domainVals); $yMax = max($domainVals);
        if ($yMax - $yMin < 1e-9) { $pad = max(abs($yMax) * 0.1, 1); } else { $pad = ($yMax - $yMin) * 0.12; }
        $yMin -= $pad; $yMax += $pad;
        if (min($domainVals) >= 0 && $yMin < 0) { $yMin = 0; }
        $y = fn (float $v): float => $padT + $plotH - (($v - $yMin) / ($yMax - $yMin)) * $plotH;
        $x = fn (int $i): float => $n <= 1 ? $padL + $plotW / 2 : $padL + ($i / ($n - 1)) * $plotW;
        $f = fn (float $v): string => number_format($v, 1, '.', '');

        // Trazo con huecos en los null
        $path = ''; $pen = false;
        foreach ($values as $i => $v) {
            if ($v === null) { $pen = false; continue; }
            $path .= ($pen ? 'L' : 'M') . $f($x($i)) . ' ' . $f($y((float) $v)) . ' ';
            $pen = true;
        }

        // Banda de rango aceptable
        $bandTop = $lmax !== null ? $y($lmax) : $padT;
        $bandBottom = $lmin !== null ? $y($lmin) : $padT + $plotH;
        $hasBand = $lmin !== null || $lmax !== null;

        // Marcas del eje Y sin traslapes
        $ticks = [];
        $cands = [[$yMax, false], [$yMin, false]];
        if ($lmax !== null) { $cands[] = [$lmax, true]; }
        if ($lmin !== null) { $cands[] = [$lmin, true]; }
        if ($planned !== null) { $cands[] = [$planned, true]; }
        usort($cands, fn ($a, $b) => (int) $b[1] <=> (int) $a[1]); // los valores de referencia tienen prioridad
        foreach ($cands as [$val, $isRef]) {
            $py = $y((float) $val);
            $clash = false;
            foreach ($ticks as $t) { if (abs($t['y'] - $py) < 13) { $clash = true; break; } }
            if (! $clash) { $ticks[] = ['y' => $py, 'v' => $val]; }
        }

        // Etiquetas del eje X: primera, media y última
        $xl = [];
        if ($n >= 1) { $xl[] = [0, 'start']; }
        if ($n >= 5) { $xl[] = [intdiv($n - 1, 2), 'middle']; }
        if ($n >= 2) { $xl[] = [$n - 1, 'end']; }

        $isOut = fn (float $v): bool => ($lmin !== null && $v < $lmin) || ($lmax !== null && $v > $lmax);
    }
    $ariaLabel = 'Gráfica de ' . $title . ': ' . $summary
        . ($lmin !== null || $lmax !== null ? '. Rango aceptable ' . ($lmin !== null ? 'desde ' . $fmtNum($lmin) : '') . ($lmax !== null ? ' hasta ' . $fmtNum($lmax) : '') : '');
@endphp

<figure class="mlc-root" style="margin: 0;">
    <style>
        .mlc-root { font-family: inherit; color: #0f172a; }
        .mlc-title { margin: 0 0 6px; font-size: 14px; font-weight: 700; }
        .mlc-title small { font-weight: 400; color: #64748b; }
        .mlc-svg { display: block; width: 100%; height: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
        .mlc-grid { stroke: #e2e8f0; stroke-width: 1; }
        .mlc-axis text, .mlc-axis-text { font-size: 11px; fill: #475569; font-family: inherit; }
        .mlc-line { fill: none; stroke: #2563eb; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
        .mlc-root--chem .mlc-line { stroke: #0f766e; }
        .mlc-band { fill: #22c55e; fill-opacity: .13; }
        .mlc-limit { stroke: #16a34a; stroke-width: 1; stroke-dasharray: 5 4; }
        .mlc-planned { stroke: #7c3aed; stroke-width: 1.5; stroke-dasharray: 7 4; }
        .mlc-dot { fill: #2563eb; stroke: #fff; stroke-width: 1.5; }
        .mlc-root--chem .mlc-dot { fill: #0f766e; }
        .mlc-dot--bad { fill: #dc2626; stroke: #fff; stroke-width: 1.5; }
        .mlc-cap { margin-top: 6px; font-size: 12px; color: #475569; display: flex; flex-wrap: wrap; gap: 4px 14px; align-items: center; }
        .mlc-cap strong { color: #0f172a; }
        .mlc-cap .mlc-bad { color: #b91c1c; font-weight: 700; }
        .mlc-key { display: inline-flex; align-items: center; gap: 5px; }
        .mlc-key i { display: inline-block; width: 16px; height: 0; border-top: 2px dashed; }
        .mlc-key i.band { height: 9px; border: 0; background: rgba(34,197,94,.25); }
        .mlc-empty { padding: 26px 14px; text-align: center; color: #64748b; font-size: 14px; border: 1px dashed #cbd5e1; border-radius: 10px; background: #fff; }
        @media print { .mlc-svg { border-color: #999; } .mlc-band, .mlc-dot--bad { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>

    @if ($title !== '')
        <figcaption class="mlc-title">{{ $name }}@if ($unit) <small>({{ $unit }})</small>@endif</figcaption>
    @endif

    @if (! $hasData)
        <div class="mlc-empty" role="status">Aún no hay {{ $isChem ? 'dosificaciones' : 'lecturas' }} registradas para {{ $name !== '' ? $name : 'este elemento' }}.</div>
    @else
        <div class="mlc-{{ $isChem ? 'wrapchem' : 'wrap' }}">
            <svg class="mlc-svg" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMid meet" role="img" aria-label="{{ $ariaLabel }}">
                <title>{{ $ariaLabel }}</title>

                @if ($hasBand)
                    <rect class="mlc-band" x="{{ $padL }}" y="{{ $f($bandTop) }}" width="{{ $plotW }}" height="{{ $f(max(0, $bandBottom - $bandTop)) }}"><title>Rango aceptable</title></rect>
                @endif

                @foreach ($ticks as $t)
                    <line class="mlc-grid" x1="{{ $padL }}" x2="{{ $padL + $plotW }}" y1="{{ $f($t['y']) }}" y2="{{ $f($t['y']) }}" />
                    <text class="mlc-axis-text" x="{{ $padL - 6 }}" y="{{ $f($t['y'] + 4) }}" text-anchor="end">{{ $fmtNum((float) $t['v']) }}</text>
                @endforeach

                @if ($lmax !== null)
                    <line class="mlc-limit" x1="{{ $padL }}" x2="{{ $padL + $plotW }}" y1="{{ $f($y($lmax)) }}" y2="{{ $f($y($lmax)) }}"><title>Límite máximo {{ $fmtNum($lmax) }}</title></line>
                @endif
                @if ($lmin !== null)
                    <line class="mlc-limit" x1="{{ $padL }}" x2="{{ $padL + $plotW }}" y1="{{ $f($y($lmin)) }}" y2="{{ $f($y($lmin)) }}"><title>Límite mínimo {{ $fmtNum($lmin) }}</title></line>
                @endif
                @if ($planned !== null)
                    <line class="mlc-planned" x1="{{ $padL }}" x2="{{ $padL + $plotW }}" y1="{{ $f($y($planned)) }}" y2="{{ $f($y($planned)) }}"><title>Dosis diaria planeada {{ $fmtNum($planned) }}</title></line>
                @endif

                <line class="mlc-grid" x1="{{ $padL }}" x2="{{ $padL }}" y1="{{ $padT }}" y2="{{ $padT + $plotH }}" />
                <line class="mlc-grid" x1="{{ $padL }}" x2="{{ $padL + $plotW }}" y1="{{ $padT + $plotH }}" y2="{{ $padT + $plotH }}" />

                @foreach ($xl as [$idx, $anchor])
                    <text class="mlc-axis-text" x="{{ $f($x($idx)) }}" y="{{ $padT + $plotH + 18 }}" text-anchor="{{ $n <= 1 ? 'middle' : $anchor }}">{{ $fmtDate($labels[$idx]) }}</text>
                @endforeach

                <g class="mlc-root--{{ $isChem ? 'chem' : 'param' }}">
                    <path class="mlc-line" d="{{ trim($path) }}" />
                    @foreach ($values as $i => $v)
                        @if ($v !== null)
                            @php $bad = $isOut((float) $v); @endphp
                            <circle class="{{ $bad ? 'mlc-dot--bad' : 'mlc-dot' }}" cx="{{ $f($x($i)) }}" cy="{{ $f($y((float) $v)) }}" r="{{ $bad ? 5 : 3.5 }}">
                                <title>{{ $fmtDate($labels[$i]) }}: {{ $fmtNum((float) $v) }}{{ $unit ? ' ' . $unit : '' }}{{ $bad ? ' (fuera de rango)' : '' }}</title>
                            </circle>
                        @endif
                    @endforeach
                </g>
            </svg>
        </div>
    @endif

    <div class="mlc-cap">
        <span><strong>{{ $summary }}</strong></span>
        @if ($oorCnt > 0)<span class="mlc-bad">Atención: hay valores fuera del rango aceptable</span>@endif
        @if ($hasData && $lmin !== null || $hasData && $lmax !== null)
            <span class="mlc-key"><i class="band"></i> Rango aceptable {{ $lmin !== null ? $fmtNum($lmin) : '' }}{{ $lmin !== null && $lmax !== null ? ' a ' : ($lmax !== null ? 'hasta ' : 'desde ') }}{{ $lmax !== null ? $fmtNum($lmax) : '' }}{{ $unit ? ' ' . $unit : '' }}</span>
        @endif
        @if ($hasData && $planned !== null)
            <span class="mlc-key"><i style="border-color:#7c3aed"></i> Dosis diaria planeada {{ $fmtNum($planned) }}{{ $unit ? ' ' . $unit : '' }}</span>
        @endif
    </div>
</figure>
