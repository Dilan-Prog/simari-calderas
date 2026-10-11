@extends('admin.layouts.master')

@section('title', 'Listas de materiales - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-boms.css'])
@endpush

@section('content')
<div class="erp-page bom-page">
    <div class="erp-page-head">
        <div>
            <h1 class="erp-title">Listas de materiales</h1>
            <p class="erp-subtitle">Recetas de los productos fabricados: componentes, mermas y costo estándar.</p>
        </div>
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', 'create'))
            <a href="{{ route('admin.boms.create') }}" class="bom-btn bom-btn-primary">+ Nueva receta</a>
        @endif
    </div>

    <form method="GET" class="bom-filters">
        <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por producto, SKU o nombre de receta…" class="bom-input">
        <select name="status" class="bom-input" onchange="this.form.submit()">
            <option value="">Todas</option>
            <option value="active" @selected($status === 'active')>Solo vigentes</option>
            <option value="inactive" @selected($status === 'inactive')>No vigentes</option>
        </select>
        <button class="bom-btn" type="submit">Filtrar</button>
        @if ($q !== '' || $status !== '')
            <a href="{{ route('admin.boms.index') }}" class="bom-btn">Limpiar</a>
        @endif
    </form>

    <div class="bom-card">
        <div class="bom-table-scroll">
            <table class="bom-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Versión</th>
                        <th>Estado</th>
                        <th class="num">Componentes</th>
                        <th class="num">Costo estándar</th>
                        <th class="num">Precio (s/IVA)</th>
                        <th class="num">Margen</th>
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
                                <a href="{{ route('admin.boms.show', $bom) }}" class="bom-link">{{ $bom->product->name }}</a>
                                <div class="bom-muted">{{ $bom->product->sku }}</div>
                            </td>
                            <td>v{{ $bom->version }}{{ $bom->name ? ' · ' . $bom->name : '' }}</td>
                            <td>
                                <span class="erp-chip erp-tone-{{ $bom->is_active ? 'ok' : 'neutral' }}">{{ $bom->is_active ? 'Vigente' : 'No vigente' }}</span>
                            </td>
                            <td class="num">{{ $bom->lines_count }}</td>
                            <td class="num">{{ $cost > 0 ? '$' . number_format($cost, 2) : '—' }}</td>
                            <td class="num">{{ $price > 0 ? '$' . number_format($price, 2) : '—' }}</td>
                            <td class="num">
                                @if ($margin !== null)
                                    <span class="erp-tone-{{ $margin < 0 ? 'bad' : ($margin < 15 ? 'warn' : 'ok') }}">{{ $margin }}%</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="bom-empty">Aún no hay recetas. Crea la primera con «Nueva receta».</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bom-pagination">{{ $boms->links('admin.components.pagination') }}</div>
</div>
@endsection
