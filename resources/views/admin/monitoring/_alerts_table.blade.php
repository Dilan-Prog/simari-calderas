@php
    use App\Http\Controllers\Backend\MonitoringController;

    $fmtN = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
@endphp
<div class="erp-table-wrap">
    <table class="erp-table mon-cards-table mon-alerts-table">
        <thead>
            <tr>
                <th>Fecha</th>
                @if ($showProgram)<th>Cliente / programa</th>@endif
                <th>Equipo</th>
                <th>Parámetro</th>
                <th class="mon-num">Valor</th>
                <th>Rango aceptable</th>
                <th>Desviación</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($alerts->take(500) as $a)
            @php
                $item = $a->item;
                $min = $item?->limit_min;
                $max = $item?->limit_max;
                $dev = null;
                if ($min !== null && $a->value < $min) {
                    $dev = 'Por debajo en ' . $fmtN($min - $a->value);
                } elseif ($max !== null && $a->value > $max) {
                    $dev = 'Por encima en ' . $fmtN($a->value - $max);
                }
                $range = ($min !== null && $max !== null) ? $fmtN($min) . ' – ' . $fmtN($max)
                    : ($min !== null ? '≥ ' . $fmtN($min) : ($max !== null ? '≤ ' . $fmtN($max) : '—'));
            @endphp
            <tr class="mon-card-row">
                <td data-label="Fecha">{{ $a->entry_date->format('d/m/Y') }}</td>
                @if ($showProgram)
                    <td class="mon-c-head" data-label="Cliente / programa">
                        <div class="erp-cell-main">{{ MonitoringController::customerName($a->program?->customer) }}</div>
                        @if ($a->program)
                            <a class="erp-cell-sub" href="{{ route('admin.monitoring.show', ['program' => $a->program_id, 'tab' => 'registros', 'item_id' => $a->item_id, 'out' => 1]) }}">{{ $a->program->name }}</a>
                        @endif
                    </td>
                @endif
                <td data-label="Equipo">{{ $a->equipment?->name ?? '—' }}</td>
                <td data-label="Parámetro">{{ $item?->name ?? '—' }}</td>
                <td class="mon-num" data-label="Valor"><span class="mon-bad">{{ $fmtN($a->value) }}</span> {{ $item?->unit }}</td>
                <td class="mon-muted" data-label="Rango aceptable">{{ $range }} {{ $item?->unit }}</td>
                <td class="mon-c-full" data-label="Desviación">@if ($dev)<x-erp.chip tone="bad" :label="$dev" />@else — @endif</td>
                <td class="mon-c-full" data-label="Nota" style="max-width:240px;">{{ $a->note ?: '—' }}</td>
            </tr>
        @empty
            <tr class="mon-empty-row"><td colspan="{{ $showProgram ? 8 : 7 }}"><x-erp.empty title="Sin lecturas fuera de rango" text="No hay lecturas fuera del rango aceptable en el periodo seleccionado." /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if ($alerts->count() > 0)
    <p class="mon-muted" style="margin:10px 0 0;">{{ $alerts->count() }} {{ $alerts->count() === 1 ? 'lectura fuera de rango' : 'lecturas fuera de rango' }}.@if ($alerts->count() > 500) Se muestran las 500 más recientes; acota el periodo o el cliente para ver el resto.@endif</p>
@endif
