@extends('admin.layouts.master')

@section('title', 'Lista de materiales ' . $product->name . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-boms.css'])
@endpush

@php
    $can = fn ($a) => auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', $a);
    $fmt = fn ($v) => $v > 0 ? '$' . number_format($v, 2) : '—';
    $unitLabel = $product->stock_unit ?? 'unidad';
@endphp

@section('content')
<div class="erp-page bom-page">
    <x-erp.object-header
        :title="$product->name"
        eyebrow="LISTA DE MATERIALES · PLA3"
        :subtitle="'Versión v' . $bom->version . ($bom->name ? ' · ' . $bom->name : '')"
        :status="$bom->is_active ? 'Vigente' : 'No vigente'"
        :tone="$bom->is_active ? 'ok' : 'neu'"
        :back="route('admin.boms.index')"
        :facts="[
            ['label' => 'SKU', 'value' => $product->sku],
            ['label' => 'Rinde', 'value' => rtrim(rtrim(number_format((float) $bom->yield_quantity, 4), '0'), '.') . ' ' . ($product->stock_unit ?? '')],
            ['label' => 'Componentes', 'value' => $bom->lines->count()],
            ['label' => 'Costo estándar', 'value' => $fmt($cost)],
            ['label' => 'Precio (s/IVA)', 'value' => $fmt($price)],
            ['label' => 'Margen', 'value' => $margin !== null && $cost > 0 ? $margin . '%' : '—'],
        ]">
        @if ($can('edit') && !$bom->is_active)
            <form method="POST" action="{{ route('admin.boms.activate', $bom) }}" class="bom-inline-form">@csrf
                <button class="erp-btn erp-btn--primary" type="submit">Marcar como vigente</button>
            </form>
        @endif
        @if ($can('create'))
            <form method="POST" action="{{ route('admin.boms.duplicate', $bom) }}" class="bom-inline-form">@csrf
                <button class="erp-btn" type="submit">Duplicar versión</button>
            </form>
        @endif
        @if ($can('edit'))
            <a href="{{ route('admin.boms.edit', $bom) }}" class="erp-btn">Editar</a>
        @endif
        @if ($can('delete'))
            <form method="POST" action="{{ route('admin.boms.destroy', $bom) }}" class="bom-inline-form" onsubmit="return confirm('¿Eliminar esta versión de la lista de materiales?')">@csrf @method('DELETE')
                <button class="erp-btn erp-btn--danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if ($errors->any())
        <div class="bom-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if ($bom->notes)
        <x-erp.card title="Notas"><p style="margin: 0;">{{ $bom->notes }}</p></x-erp.card>
    @endif

    <section class="erp-card" data-erp-tabs>
        <x-erp.tab-nav :tabs="['tree' => 'Estructura', 'vers' => 'Versiones (' . $otherVersions->count() . ')']" active="tree" />

        <div class="erp-tabpanel is-active" data-panel="tree" data-bom-tree>
            <div class="bom-tree-tools">
                <button type="button" class="erp-btn erp-btn--sm" data-expand-all>Expandir todo</button>
                <button type="button" class="erp-btn erp-btn--sm" data-collapse-all>Contraer todo</button>
            </div>
            <div class="erp-table-wrap">
                <div class="erp-tree">
                    <div class="erp-tree-row erp-tree-row--bom erp-tree-head" aria-hidden="true">
                        <span>Componente (por 1 {{ $unitLabel }})</span>
                        <span class="is-num">Cantidad</span><span class="is-num">Merma</span><span class="is-num">Costo unit.</span><span class="is-num">Costo rama</span>
                    </div>
                    <div class="erp-tree-row erp-tree-row--bom erp-tree-row--root">
                        <span class="bom-node-main"><span class="bom-node-name">{{ $product->name }}</span> <span class="bom-muted">{{ $product->sku }}</span> <span class="bom-lvl">N1</span></span>
                        <span class="is-num bom-col-qty">1 <span class="bom-muted">{{ $unitLabel }}</span></span>
                        <span class="is-num bom-col-merma">—</span>
                        <span class="is-num bom-col-unit">{{ $fmt($cost) }}</span>
                        <span class="is-num bom-col-cost">{{ $fmt($cost) }}</span>
                    </div>
                    @if (count($tree))
                        <ul class="bom-tree">
                            @include('admin.boms._tree_node', ['nodes' => $tree, 'depth' => 0])
                        </ul>
                    @else
                        <div class="erp-tree-row" style="display: block;"><span class="bom-muted">Esta lista de materiales no tiene componentes.</span></div>
                    @endif
                </div>
            </div>
            <div class="bom-tree-total"><span>Costo estándar total</span><strong>{{ $fmt($cost) }}</strong></div>
            <p class="bom-note">Las cantidades incluyen la merma y se acumulan por nivel. Los subensambles usan su receta vigente.</p>
        </div>

        <div class="erp-tabpanel" data-panel="vers">
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead><tr><th>Versión</th><th>Estado</th><th class="is-right">Componentes</th><th>Actualizada</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($otherVersions as $v)
                            <tr>
                                <td>
                                    <span class="erp-cell-main">v{{ $v->version }}</span>
                                    @if ($v->name)<span class="erp-cell-sub"> · {{ $v->name }}</span>@endif
                                    @if ($v->id === $bom->id)<span class="bom-muted">(esta)</span>@endif
                                </td>
                                <td><x-erp.status :value="$v->is_active ? 'Vigente' : 'No vigente'" :tone="$v->is_active ? 'ok' : 'neu'" /></td>
                                <td class="is-num">{{ $v->lines_count }}</td>
                                <td>{{ $v->updated_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <div class="bom-versions-actions">
                                        @if ($v->id !== $bom->id)<a class="erp-btn erp-btn--sm" href="{{ route('admin.boms.show', $v) }}">Ver</a>@endif
                                        @if ($can('edit') && !$v->is_active)
                                            <form method="POST" action="{{ route('admin.boms.activate', $v) }}" class="bom-inline-form">@csrf
                                                <button class="erp-btn erp-btn--sm" type="submit">Marcar vigente</button>
                                            </form>
                                        @endif
                                        @if ($can('create'))
                                            <form method="POST" action="{{ route('admin.boms.duplicate', $v) }}" class="bom-inline-form">@csrf
                                                <button class="erp-btn erp-btn--sm" type="submit">Duplicar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-boms.js'])
@endpush
