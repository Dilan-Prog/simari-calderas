@extends('admin.layouts.master')

@section('title', 'Listas de materiales - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-boms.css'])
@endpush

@php
    $canCreate = auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', 'create');
    $viewUrl = fn ($s) => route('admin.boms.index', array_filter(['q' => $q, 'status' => $s]));
    $views = [
        ['label' => 'Todas', 'url' => $viewUrl(''), 'active' => $status === ''],
        ['label' => 'Vigentes', 'url' => $viewUrl('active'), 'active' => $status === 'active'],
        ['label' => 'No vigentes', 'url' => $viewUrl('inactive'), 'active' => $status === 'inactive'],
    ];
    $chips = [];
    if ($q !== '') {
        $chips[] = ['label' => 'Búsqueda: ' . $q, 'url' => route('admin.boms.index', array_filter(['status' => $status]))];
    }
    if ($status !== '') {
        $chips[] = ['label' => 'Estado: ' . ($status === 'active' ? 'Vigentes' : 'No vigentes'), 'url' => route('admin.boms.index', array_filter(['q' => $q]))];
    }
@endphp

@section('content')
<div class="erp-page bom-page">
    <x-erp.page-header title="Listas de materiales" code="PLA3"
        desc="Estructura multinivel por producto con cantidades, merma, costo por rama y versiones."
        :crumbs="[['Planeación', null], ['Listas de materiales', null]]">
        @if ($canCreate)
            <a href="{{ route('admin.boms.create') }}" class="erp-btn erp-btn--primary">+ Nueva lista de materiales</a>
        @endif
    </x-erp.page-header>

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="bom-toolbar-form">
                @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
                <div class="erp-search">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por producto, SKU o nombre de versión…" aria-label="Buscar listas de materiales">
                </div>
                <button class="erp-btn" type="submit">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Producto / SKU</th>
                        <th>Versión</th>
                        <th class="is-right">Componentes</th>
                        <th class="is-right">Costo estándar</th>
                        <th class="is-right">Precio (s/IVA)</th>
                        <th class="is-right">Margen</th>
                        <th>Actualizada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($boms as $bom)
                        @php
                            $cost = $costs[$bom->id] ?? 0;
                            $price = $bom->product->base_price;
                            $margin = $price > 0 && $cost > 0 ? round((($price - $cost) / $price) * 100, 1) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="erp-cell-main"><a href="{{ route('admin.boms.show', $bom) }}" class="erp-cell-link">{{ $bom->product->name }}</a></div>
                                <div class="erp-cell-sub">{{ $bom->product->sku }}</div>
                            </td>
                            <td>v{{ $bom->version }}@if ($bom->name)<div class="erp-cell-sub">{{ $bom->name }}</div>@endif</td>
                            <td class="is-num">{{ $bom->lines_count }}</td>
                            <td class="is-num"><strong>{{ $cost > 0 ? '$' . number_format($cost, 2) : '—' }}</strong></td>
                            <td class="is-num">{{ $price > 0 ? '$' . number_format($price, 2) : '—' }}</td>
                            <td class="is-num">
                                @if ($margin !== null)
                                    <span class="bom-margin erp-tone-{{ $margin < 0 ? 'bad' : ($margin < 15 ? 'warn' : 'ok') }}">{{ $margin }}%</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $bom->updated_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><x-erp.status :value="$bom->is_active ? 'Vigente' : 'No vigente'" :tone="$bom->is_active ? 'ok' : 'neu'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding: 0; border: 0;">
                            <x-erp.empty title="Aún no hay listas de materiales" text="Crea la primera con «Nueva lista de materiales».">
                                @if ($canCreate)<a href="{{ route('admin.boms.create') }}" class="erp-btn erp-btn--primary">+ Nueva lista de materiales</a>@endif
                            </x-erp.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>{{ $boms->total() }} {{ $boms->total() === 1 ? 'lista de materiales' : 'listas de materiales' }}</span>
            <div>{{ $boms->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
