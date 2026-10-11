@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    {{ $count->number }} - Conteos físicos - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('stock-counts', 'edit');
    $canDelete = $user->hasPermission('stock-counts', 'delete');
    $pct = $progress['total'] ? round($progress['counted'] / $progress['total'] * 100) : 0;
    $steps = ['Abierto', 'En conteo', 'Por ajustar', 'Ajustado'];
    $stepIdx = array_search($count->status, ['abierto', 'en_conteo', 'cerrado', 'ajustado'], true);
    $tones = ['abierto' => 'neu', 'en_conteo' => 'info', 'cerrado' => 'warn', 'ajustado' => 'ok'];
    $labels = ['abierto' => 'Abierto', 'en_conteo' => 'En conteo', 'cerrado' => 'Por ajustar', 'ajustado' => 'Ajustado'];
    $money = fn ($v) => ($v < 0 ? '-' : '') . '$' . number_format(abs($v), 2);
    // Totales iniciales de la hoja de captura (a costo).
    $sheetNeg = 0; $sheetPos = 0;
    foreach ($count->lines()->with('product:id,cost')->whereNotNull('counted_quantity')->get() as $l) {
        if ($l->difference !== null && $l->difference != 0) {
            $val = $l->difference * (float) ($l->product->cost ?? 0);
            $val < 0 ? $sheetNeg += $val : $sheetPos += $val;
        }
    }
@endphp
<div class="erp-page">
    <x-erp.object-header :title="$count->number" eyebrow="CONTEO FÍSICO" :subtitle="$count->warehouse->name"
        :status="$labels[$count->status] ?? $count->status" :tone="$tones[$count->status] ?? 'neu'"
        :back="route('admin.stock-counts.index')"
        process="Conteo" :steps="$steps" :current="$stepIdx === false ? null : $stepIdx"
        :facts="[
            ['label' => 'Almacén', 'value' => $count->warehouse->name],
            ['label' => 'Inicio', 'value' => $count->started_at?->format('d/m/Y H:i')],
            ['label' => 'Cierre', 'value' => $count->closed_at?->format('d/m/Y H:i')],
            ['label' => 'Avance', 'value' => $progress['counted'] . ' de ' . $progress['total']],
        ]">
        @if (! $editable)
            <a href="{{ route('admin.stock-counts.export', $count) }}" class="erp-btn">Exportar CSV</a>
        @endif
        @if ($canEdit && $editable)
            <form method="POST" action="{{ route('admin.stock-counts.close', $count) }}" class="wh-inline" data-confirm="¿Cerrar el conteo? Ya no podrás capturar cantidades. Las líneas sin contar no generan ajuste.">
                @csrf <button class="erp-btn erp-btn--primary" type="submit">Cerrar conteo</button>
            </form>
        @endif
        @if ($canEdit && $count->status === 'cerrado')
            <form method="POST" action="{{ route('admin.stock-counts.adjust', $count) }}" class="wh-inline"
                  data-confirm="Se generarán {{ $progress['diffs'] }} ajuste(s) de inventario en {{ $count->warehouse->name }}. Esta acción no se puede deshacer. ¿Continuar?">
                @csrf <button class="erp-btn erp-btn--primary" type="submit">Generar ajustes</button>
            </form>
        @endif
        @if ($canDelete && $editable)
            <form method="POST" action="{{ route('admin.stock-counts.destroy', $count) }}" class="wh-inline" data-confirm="¿Eliminar este conteo?">
                @csrf @method('DELETE') <button class="erp-btn erp-btn--danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif

    @if ($editable)
        <div data-wh-count-sheet data-neg="{{ $sheetNeg }}" data-pos="{{ $sheetPos }}">
            <div class="wh-sticky-bar">
                <div style="flex:1 1 220px;">
                    <div class="wh-progress" style="width:100%"><span data-progress-bar style="width: {{ $pct }}%"></span></div>
                    <small data-progress-label>{{ $progress['counted'] }} de {{ $progress['total'] }} contados</small>
                </div>
                <div class="wh-totals" style="grid-template-columns:repeat(3,auto);gap:10px;">
                    <div><span>Faltantes</span><strong class="wh-neg" data-total-neg style="font-size:15px;">{{ $money($sheetNeg) }}</strong></div>
                    <div><span>Sobrantes</span><strong class="wh-pos" data-total-pos style="font-size:15px;">{{ $money($sheetPos) }}</strong></div>
                    <div><span>Neto</span><strong data-total-net style="font-size:15px;">{{ $money($sheetNeg + $sheetPos) }}</strong></div>
                </div>
                <form method="GET" class="erp-toolbar">
                    <div class="erp-search"><input type="search" name="search" placeholder="Buscar producto o SKU..." value="{{ request('search') }}" aria-label="Buscar producto o SKU"></div>
                    <select name="filter" class="erp-select" aria-label="Filtrar líneas" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="pendientes" @selected(request('filter') === 'pendientes')>Pendientes de contar</option>
                        <option value="diferencias" @selected(request('filter') === 'diferencias')>Con diferencia</option>
                    </select>
                </form>
            </div>

            <section class="erp-card">
                <div class="erp-table-wrap">
                    <table class="erp-table wh-stack">
                        <thead><tr><th>Producto</th><th>SKU</th><th class="is-right">Sistema</th><th class="is-right">Contado</th><th class="is-right">Diferencia</th><th class="is-right">Valor (costo)</th></tr></thead>
                        <tbody>
                            @forelse ($lines as $line)
                                @php $cost = (float) ($line->product->cost ?? 0); $d = $line->difference; @endphp
                                <tr>
                                    <td data-label="Producto" class="erp-cell-main">{{ $line->product->name }}</td>
                                    <td data-label="SKU">{{ $line->product->sku }}</td>
                                    <td data-label="Sistema" class="is-num">{{ $line->system_quantity }}</td>
                                    <td data-label="Contado" class="is-num">
                                        @if ($canEdit)
                                            <input type="number" inputmode="numeric" min="0" step="1" class="wh-count-input" data-count-input
                                                   aria-label="Cantidad contada de {{ $line->product->name }}"
                                                   data-line="{{ $line->id }}" data-cost="{{ $cost }}" data-url="{{ route('admin.stock-counts.lines.update', [$count, $line]) }}"
                                                   data-last="{{ $line->counted_quantity }}" value="{{ $line->counted_quantity }}">
                                        @else {{ $line->counted_quantity ?? '—' }} @endif
                                    </td>
                                    <td data-label="Diferencia" class="is-num">
                                        <span data-diff-for="{{ $line->id }}" class="{{ $d === null || $d == 0 ? 'wh-muted' : ($d > 0 ? 'wh-pos' : 'wh-neg') }}">{{ $d === null ? '—' : ($d > 0 ? '+' . $d : $d) }}</span>
                                    </td>
                                    <td data-label="Valor (costo)" class="is-num">
                                        <span data-value-for="{{ $line->id }}" data-value="{{ $d === null ? 0 : $d * $cost }}" class="{{ $d === null || $d == 0 ? 'wh-muted' : ($d > 0 ? 'wh-pos' : 'wh-neg') }}">{{ $d === null || $d == 0 ? '—' : $money($d * $cost) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-erp.empty title="Sin resultados" style="border:0;" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @else
        <x-erp.card title="Reporte de diferencias (valorizado a costo)">
            <div class="wh-totals">
                <div><span>Faltantes</span><strong class="wh-neg">{{ $money($report['total_negative']) }}</strong></div>
                <div><span>Sobrantes</span><strong class="wh-pos">{{ $money($report['total_positive']) }}</strong></div>
                <div><span>Neto</span><strong>{{ $money($report['total_net']) }}</strong></div>
                <div><span>Productos contados</span><strong>{{ $report['counted'] }} de {{ $report['total'] }}</strong></div>
            </div>
            @if ($count->status === 'cerrado')
                <p class="wh-alert wh-alert-info" style="margin:14px 0 0;">Los ajustes aún no se han generado: el inventario no ha cambiado.</p>
            @else
                <p class="wh-muted" style="margin:14px 0 0;">Ajustes generados el {{ $count->adjusted_at?->format('d/m/Y H:i') }}.</p>
            @endif
        </x-erp.card>

        <section class="erp-card">
            <div class="erp-table-wrap">
                <table class="erp-table wh-stack">
                    <thead><tr><th>Producto</th><th>SKU</th><th class="is-right">Sistema</th><th class="is-right">Contado</th><th class="is-right">Diferencia</th><th class="is-right">Costo</th><th class="is-right">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($report['rows'] as $r)
                            @php $l = $r['line']; @endphp
                            <tr>
                                <td data-label="Producto" class="erp-cell-main">{{ $l->product->name }}</td>
                                <td data-label="SKU">{{ $l->product->sku }}</td>
                                <td data-label="Sistema" class="is-num">{{ $l->system_quantity }}</td>
                                <td data-label="Contado" class="is-num">{{ $l->counted_quantity }}</td>
                                <td data-label="Diferencia" class="is-num {{ $l->difference > 0 ? 'wh-pos' : 'wh-neg' }}">{{ $l->difference > 0 ? '+' : '' }}{{ $l->difference }}</td>
                                <td data-label="Costo" class="is-num">{{ $money($r['cost']) }}</td>
                                <td data-label="Valor" class="is-num {{ $r['value'] < 0 ? 'wh-neg' : 'wh-pos' }}">{{ $money($r['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-erp.empty title="Sin diferencias" text="No hubo diferencias." style="border:0;" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($count->notes)
        <x-erp.card title="Notas"><p style="margin:0; white-space:pre-line;">{{ $count->notes }}</p></x-erp.card>
    @endif
</div>
@endsection
