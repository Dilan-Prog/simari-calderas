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
    $steps = ['abierto' => 'Abierto', 'en_conteo' => 'En conteo', 'cerrado' => 'Cerrado', 'ajustado' => 'Ajustado'];
    $money = fn ($v) => ($v < 0 ? '-' : '') . '$' . number_format(abs($v), 2);
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif

    <x-erp.object-header :title="$count->number" :subtitle="$count->warehouse->name"
        :status="\App\Models\StockCount::statusLabel($count->status)" :tone="\App\Models\StockCount::statusTone($count->status)"
        :back="route('admin.stock-counts.index')"
        :facts="[
            ['label' => 'Almacén', 'value' => $count->warehouse->name],
            ['label' => 'Inicio', 'value' => $count->started_at?->format('d/m/Y H:i')],
            ['label' => 'Cierre', 'value' => $count->closed_at?->format('d/m/Y H:i')],
            ['label' => 'Avance', 'value' => $progress['counted'] . ' de ' . $progress['total']],
        ]">
        @if (! $editable)
            <a href="{{ route('admin.stock-counts.export', $count) }}" class="wh-btn">Exportar CSV</a>
        @endif
        @if ($canEdit && $editable)
            <form method="POST" action="{{ route('admin.stock-counts.close', $count) }}" data-confirm="¿Cerrar el conteo? Ya no podrás capturar cantidades. Las líneas sin contar no generan ajuste.">
                @csrf <button class="wh-btn wh-btn-primary" type="submit">Cerrar conteo</button>
            </form>
        @endif
        @if ($canEdit && $count->status === 'cerrado')
            <form method="POST" action="{{ route('admin.stock-counts.adjust', $count) }}"
                  data-confirm="Se generarán {{ $progress['diffs'] }} ajuste(s) de inventario en {{ $count->warehouse->name }}. Esta acción no se puede deshacer. ¿Continuar?">
                @csrf <button class="wh-btn wh-btn-primary" type="submit">Generar ajustes</button>
            </form>
        @endif
        @if ($canDelete && $editable)
            <form method="POST" action="{{ route('admin.stock-counts.destroy', $count) }}" data-confirm="¿Eliminar este conteo?">
                @csrf @method('DELETE') <button class="wh-btn wh-btn-danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.process-chevrons :steps="$steps" :current="$count->status" />

    @if ($editable)
        <div data-wh-count-sheet>
            <div class="wh-sticky-bar">
                <div style="flex:1 1 200px;">
                    <div class="wh-progress" style="width:100%"><span data-progress-bar style="width: {{ $pct }}%"></span></div>
                    <small data-progress-label>{{ $progress['counted'] }} de {{ $progress['total'] }} contados</small>
                </div>
                <form method="GET" class="wh-filters" style="margin:0;">
                    <input type="text" name="search" class="users-manager-input" placeholder="Buscar producto o SKU..." value="{{ request('search') }}">
                    <select name="filter" class="users-manager-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="pendientes" @selected(request('filter') === 'pendientes')>Pendientes de contar</option>
                        <option value="diferencias" @selected(request('filter') === 'diferencias')>Con diferencia</option>
                    </select>
                </form>
            </div>

            <div class="wh-card wh-table-wrap">
                <table class="wh-table wh-stack">
                    <thead><tr><th>Producto</th><th>SKU</th><th class="num">Sistema</th><th class="num">Contado</th><th class="num">Diferencia</th></tr></thead>
                    <tbody>
                        @forelse ($lines as $line)
                            <tr>
                                <td data-label="Producto">{{ $line->product->name }}</td>
                                <td data-label="SKU">{{ $line->product->sku }}</td>
                                <td data-label="Sistema" class="num">{{ $line->system_quantity }}</td>
                                <td data-label="Contado" class="num">
                                    @if ($canEdit)
                                        <input type="number" inputmode="numeric" min="0" step="1" class="wh-count-input" data-count-input
                                               data-line="{{ $line->id }}" data-url="{{ route('admin.stock-counts.lines.update', [$count, $line]) }}"
                                               data-last="{{ $line->counted_quantity }}" value="{{ $line->counted_quantity }}">
                                    @else {{ $line->counted_quantity ?? '—' }} @endif
                                </td>
                                <td data-label="Diferencia" class="num">
                                    @php $d = $line->difference; @endphp
                                    <span data-diff-for="{{ $line->id }}" class="{{ $d === null || $d == 0 ? 'wh-muted' : ($d > 0 ? 'wh-pos' : 'wh-neg') }}">{{ $d === null ? '—' : ($d > 0 ? '+' . $d : $d) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="wh-empty">Sin resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="wh-card">
            <h2>Reporte de diferencias (valorizado a costo)</h2>
            <div class="wh-totals">
                <div><span class="wh-muted">Faltantes</span><strong class="wh-neg">{{ $money($report['total_negative']) }}</strong></div>
                <div><span class="wh-muted">Sobrantes</span><strong class="wh-pos">{{ $money($report['total_positive']) }}</strong></div>
                <div><span class="wh-muted">Neto</span><strong>{{ $money($report['total_net']) }}</strong></div>
                <div><span class="wh-muted">Productos contados</span><strong>{{ $report['counted'] }} de {{ $report['total'] }}</strong></div>
            </div>
            @if ($count->status === 'cerrado')
                <p class="wh-alert wh-alert-info" style="margin-top:14px;">Los ajustes aún no se han generado: el inventario no ha cambiado.</p>
            @else
                <p class="wh-muted" style="margin-top:14px;">Ajustes generados el {{ $count->adjusted_at?->format('d/m/Y H:i') }}.</p>
            @endif
        </div>

        <div class="wh-card wh-table-wrap">
            <table class="wh-table wh-stack">
                <thead><tr><th>Producto</th><th>SKU</th><th class="num">Sistema</th><th class="num">Contado</th><th class="num">Diferencia</th><th class="num">Costo</th><th class="num">Valor</th></tr></thead>
                <tbody>
                    @forelse ($report['rows'] as $r)
                        @php $l = $r['line']; @endphp
                        <tr>
                            <td data-label="Producto">{{ $l->product->name }}</td>
                            <td data-label="SKU">{{ $l->product->sku }}</td>
                            <td data-label="Sistema" class="num">{{ $l->system_quantity }}</td>
                            <td data-label="Contado" class="num">{{ $l->counted_quantity }}</td>
                            <td data-label="Diferencia" class="num {{ $l->difference > 0 ? 'wh-pos' : 'wh-neg' }}">{{ $l->difference > 0 ? '+' : '' }}{{ $l->difference }}</td>
                            <td data-label="Costo" class="num">{{ $money($r['cost']) }}</td>
                            <td data-label="Valor" class="num {{ $r['value'] < 0 ? 'wh-neg' : 'wh-pos' }}">{{ $money($r['value']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="wh-empty">No hubo diferencias.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($count->notes)
        <div class="wh-card"><h2>Notas</h2><p style="margin:0; white-space:pre-line;">{{ $count->notes }}</p></div>
    @endif
</section>
</div>
@endsection
