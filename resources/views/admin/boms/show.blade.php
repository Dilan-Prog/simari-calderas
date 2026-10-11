@extends('admin.layouts.master')

@section('title', 'Receta ' . $product->name . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-boms.css'])
@endpush

@php
    $can = fn ($a) => auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', $a);
    $fmt = fn ($v) => $v > 0 ? '$' . number_format($v, 2) : '—';
@endphp

@section('content')
<div class="erp-page bom-page">
    <x-erp.object-header
        :title="$product->name"
        :subtitle="'Lista de materiales · v' . $bom->version . ($bom->name ? ' · ' . $bom->name : '')"
        :status="$bom->is_active ? 'Vigente' : 'No vigente'"
        :tone="$bom->is_active ? 'ok' : 'neutral'"
        :back="route('admin.boms.index')"
        :facts="[
            ['label' => 'SKU', 'value' => $product->sku],
            ['label' => 'Rinde', 'value' => rtrim(rtrim(number_format((float) $bom->yield_quantity, 4), '0'), '.') . ' ' . ($product->stock_unit ?? '')],
            ['label' => 'Costo estándar', 'value' => $fmt($cost)],
            ['label' => 'Precio (s/IVA)', 'value' => $fmt($price)],
            ['label' => 'Margen', 'value' => $margin !== null && $cost > 0 ? $margin . '%' : '—'],
        ]">
        @if ($can('edit') && !$bom->is_active)
            <form method="POST" action="{{ route('admin.boms.activate', $bom) }}">@csrf
                <button class="bom-btn bom-btn-primary" type="submit">Marcar como vigente</button>
            </form>
        @endif
        @if ($can('create'))
            <form method="POST" action="{{ route('admin.boms.duplicate', $bom) }}">@csrf
                <button class="bom-btn" type="submit">Duplicar versión</button>
            </form>
        @endif
        @if ($can('edit'))
            <a href="{{ route('admin.boms.edit', $bom) }}" class="bom-btn">Editar</a>
        @endif
        @if ($can('delete'))
            <form method="POST" action="{{ route('admin.boms.destroy', $bom) }}" onsubmit="return confirm('¿Eliminar esta versión de la receta?')">@csrf @method('DELETE')
                <button class="bom-btn bom-btn-danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if ($errors->any())
        <div class="bom-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if ($bom->notes)
        <div class="bom-card bom-pad"><strong>Notas:</strong> {{ $bom->notes }}</div>
    @endif

    <div class="bom-card bom-pad" data-bom-tree>
        <div class="bom-section-head">
            <h2>Estructura (por 1 {{ $product->stock_unit ?? 'unidad' }})</h2>
            <div class="bom-tree-tools">
                <button type="button" class="bom-btn" data-expand-all>Expandir todo</button>
                <button type="button" class="bom-btn" data-collapse-all>Contraer todo</button>
            </div>
        </div>
        <div class="bom-tree-head">
            <span>Componente</span><span>Cantidad</span><span>Costo unit.</span><span>Costo rama</span>
        </div>
        @if (count($tree))
            <ul class="bom-tree">
                @include('admin.boms._tree_node', ['nodes' => $tree])
            </ul>
        @else
            <p class="bom-muted">Esta receta no tiene componentes.</p>
        @endif
        <div class="bom-tree-total"><span>Costo estándar total</span><strong>{{ $fmt($cost) }}</strong></div>
        <p class="bom-muted">Las cantidades incluyen la merma y se acumulan por nivel. Los subensambles usan su receta vigente.</p>
    </div>

    <div class="bom-card bom-pad">
        <div class="bom-section-head"><h2>Versiones del producto</h2></div>
        <table class="bom-table">
            <thead><tr><th>Versión</th><th>Estado</th><th class="num">Componentes</th><th></th></tr></thead>
            <tbody>
                @foreach ($otherVersions as $v)
                    <tr>
                        <td>v{{ $v->version }}{{ $v->name ? ' · ' . $v->name : '' }} @if ($v->id === $bom->id)<span class="bom-muted">(esta)</span>@endif</td>
                        <td><span class="erp-chip erp-tone-{{ $v->is_active ? 'ok' : 'neutral' }}">{{ $v->is_active ? 'Vigente' : 'No vigente' }}</span></td>
                        <td class="num">{{ $v->lines_count }}</td>
                        <td class="num">@if ($v->id !== $bom->id)<a class="bom-link" href="{{ route('admin.boms.show', $v) }}">Ver</a>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-boms.js'])
@endpush
