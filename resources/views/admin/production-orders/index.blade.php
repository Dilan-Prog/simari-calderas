@extends('admin.layouts.master')

@section('title', 'Órdenes de producción - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@php
    use App\Models\ProductionOrder;
    use App\Support\ErpUi;

    $canCreate = auth()->user()->isAdmin() || auth()->user()->hasPermission('production-orders', 'create');
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.') ?: '0';
    $base = route('admin.production-orders.index');
    $noExtra = $filters['q'] === '' && $filters['product'] === '' && $filters['project_id'] === '';
    $views = [
        ['label' => 'Todas', 'url' => $base, 'active' => $noExtra && $filters['status'] === '' && $filters['overdue'] === ''],
        ['label' => 'Vencidas', 'url' => $base . '?overdue=1', 'active' => $noExtra && $filters['status'] === '' && $filters['overdue'] === '1'],
        ['label' => 'Liberadas', 'url' => $base . '?status=liberada', 'active' => $noExtra && $filters['status'] === 'liberada' && $filters['overdue'] === ''],
        ['label' => 'En proceso', 'url' => $base . '?status=en_proceso', 'active' => $noExtra && $filters['status'] === 'en_proceso' && $filters['overdue'] === ''],
        ['label' => 'Terminadas', 'url' => $base . '?status=terminada', 'active' => $noExtra && $filters['status'] === 'terminada' && $filters['overdue'] === ''],
    ];
    $wlChips = collect($chips)->map(fn ($c) => ['label' => $c['label'] . ': ' . $c['value'], 'url' => $c['url']])->all();
@endphp

@section('content')
<div class="erp-page prd-page">
    <x-erp.page-header
        title="Órdenes de producción"
        code="PRD2"
        desc="Fabricación propia: reserva de componentes, consumo, producto terminado y costo real contra estándar."
        :crumbs="[['Producción', null], ['Órdenes de producción', null]]">
        @if ($canCreate)
            <a href="{{ route('admin.production-orders.create') }}" class="erp-btn erp-btn--primary">+ Nueva orden</a>
        @endif
    </x-erp.page-header>

    @if (session('success'))<div class="prd-ok" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="prd-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-erp.worklist :views="$views" :chips="$wlChips">
        <x-slot:toolbar>
            <form method="GET" class="prd-toolbar-form" role="search">
                <div class="erp-search"><input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por folio…" aria-label="Folio"></div>
                <input type="search" name="product" value="{{ $filters['product'] }}" placeholder="Producto o SKU…" class="erp-input prd-w-md" aria-label="Producto o SKU">
                <select name="status" class="erp-select" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach (ProductionOrder::STATUSES as $s)
                        <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ ProductionOrder::statusLabel($s) }}</option>
                    @endforeach
                </select>
                @if ($projects->isNotEmpty())
                    <select name="project_id" class="erp-select" aria-label="Proyecto">
                        <option value="">Todos los proyectos</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($filters['project_id'] === (string) $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 28) }}</option>
                        @endforeach
                    </select>
                @endif
                <label class="prd-check"><input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] === '1')> Solo vencidas</label>
                <button class="erp-btn" type="submit">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Producto</th>
                        <th class="is-right">Producido / plan</th>
                        <th>Estatus</th>
                        <th>Avance</th>
                        <th>Fecha límite</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $o)
                        @php $days = $o->daysToDue(); @endphp
                        <tr>
                            <td class="erp-cell-folio"><a href="{{ route('admin.production-orders.show', $o) }}">{{ $o->number }}</a></td>
                            <td>
                                <div class="erp-cell-main">{{ $o->product->name ?? '—' }}</div>
                                <div class="erp-cell-sub">{{ $o->product->sku ?? '' }}</div>
                            </td>
                            <td class="is-num">{{ $n($o->quantity_produced) }} / {{ $n($o->quantity_planned) }}</td>
                            <td><x-erp.chip :tone="ErpUi::tone($o->status)" :label="ProductionOrder::statusLabel($o->status)" /></td>
                            <td><x-erp.progress :value="$o->progress" /></td>
                            <td>
                                @if ($o->due_date)
                                    <x-erp.due :date="$o->due_date" :closed="!$o->isOpen()" />
                                    @if ($o->isOpen() && $days !== null)
                                        <div class="erp-cell-sub">{{ $days < 0 ? 'Vencida hace ' . abs($days) . ' d' : ($days === 0 ? 'Vence hoy' : 'En ' . $days . ' d') }}</div>
                                    @endif
                                @else
                                    <span class="erp-cell-sub">Sin fecha</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-erp.empty title="Aún no hay órdenes de producción" text="Crea la primera orden para reservar componentes y registrar la fabricación.">
                                @if ($canCreate)<a href="{{ route('admin.production-orders.create') }}" class="erp-btn erp-btn--primary">+ Nueva orden</a>@endif
                            </x-erp.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>{{ $orders->total() }} {{ $orders->total() === 1 ? 'orden' : 'órdenes' }}</span>
            <div>{{ $orders->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
