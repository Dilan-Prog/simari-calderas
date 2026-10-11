@extends('admin.layouts.master')

@section('title', 'MRP · Requerimientos de material - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-mrp.css'])
@endpush

@php
    $user = auth()->user();
    $canCreate = $user->isAdmin() || $user->hasPermission('mrp', 'create');
    $canBatchPo = $canCreate && ($user->isAdmin() || $user->hasPermission('purchase-orders', 'create'));
    $viewUrl = fn (array $over) => route('admin.mrp.index', array_filter(array_merge($filters, ['shortfall' => '', 'status' => ''], $over), fn ($v) => $v !== '' && $v !== null));
    $views = [
        ['label' => 'Todos', 'url' => route('admin.mrp.index'), 'active' => !array_filter($filters)],
        ['label' => 'Con faltantes', 'url' => $viewUrl(['shortfall' => '1']), 'active' => $filters['shortfall'] === '1' && $filters['status'] === ''],
        ['label' => 'En compra', 'url' => $viewUrl(['status' => 'en_compra']), 'active' => $filters['status'] === 'en_compra' && $filters['shortfall'] === ''],
        ['label' => 'Cubiertos', 'url' => $viewUrl(['status' => 'cubierto']), 'active' => $filters['status'] === 'cubierto' && $filters['shortfall'] === ''],
    ];
    $wlChips = collect($chips)->map(fn ($c) => ['label' => $c['label'] . ': ' . $c['value'], 'url' => $c['url']])->all();
    $colspan = $canBatchPo ? 9 : 8;
@endphp

@section('content')
<div class="erp-page mrp-page">
    <x-erp.page-header title="Requerimientos de material" code="MRP2"
        desc="Qué se necesita, qué hay en existencia y qué falta comprar para pedidos, proyectos y servicios."
        :crumbs="[['MRP', null], ['Requerimientos de material', null]]">
        @if ($canCreate)
            <a href="{{ route('admin.mrp.create') }}" class="erp-btn erp-btn--primary">+ Nuevo requerimiento</a>
        @endif
    </x-erp.page-header>

    @if (session('success'))<div class="mrp-ok" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="mrp-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-erp.worklist :views="$views" :chips="$wlChips">
        <x-slot:toolbar>
            <form method="GET" class="mrp-toolbar-form">
                <div class="erp-search">
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por folio…" aria-label="Buscar por folio">
                </div>
                <select name="status" class="erp-select" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach (\App\Models\MaterialRequirement::STATUSES as $s)
                        <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ \App\Models\MaterialRequirement::statusLabel($s) }}</option>
                    @endforeach
                </select>
                <select name="origin" class="erp-select" aria-label="Origen">
                    <option value="">Todos los orígenes</option>
                    @foreach (['sales_order' => 'Pedido', 'project' => 'Proyecto', 'technical_service' => 'Servicio técnico', 'production_order' => 'Orden de producción', 'manual' => 'Manual'] as $k => $l)
                        <option value="{{ $k }}" @selected($filters['origin'] === $k)>{{ $l }}</option>
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
                <input type="date" name="from" value="{{ $filters['from'] }}" class="erp-select" title="Creado desde" aria-label="Creado desde">
                <input type="date" name="to" value="{{ $filters['to'] }}" class="erp-select" title="Creado hasta" aria-label="Creado hasta">
                <label class="mrp-check"><input type="checkbox" name="shortfall" value="1" @checked($filters['shortfall'] === '1')> Solo con faltante</label>
                <button class="erp-btn" type="submit">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @if ($canBatchPo)
            <form method="POST" action="{{ route('admin.mrp.generate-purchase-orders-batch') }}" data-mrp-batch-form>
                @csrf
                <x-erp.batch-bar noun="requerimientos">
                    <button type="submit" class="erp-btn">Generar órdenes de compra</button>
                </x-erp.batch-bar>
            </form>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        @if ($canBatchPo)<th class="mrp-col-check"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todos"></th>@endif
                        <th>Folio</th>
                        <th>Origen</th>
                        <th>Cliente</th>
                        <th class="is-right">Partidas</th>
                        <th class="is-right">Faltantes</th>
                        <th>Cobertura</th>
                        <th>Fecha requerida</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reqs as $r)
                        @php
                            $closed = in_array($r->status, ['cancelado', 'cubierto'], true);
                            $coverage = $r->lines_count > 0 ? (($r->lines_count - $r->short_lines_count) / $r->lines_count) * 100 : 0;
                        @endphp
                        <tr>
                            @if ($canBatchPo)
                                <td class="mrp-col-check">
                                    @unless ($r->status === 'cancelado')
                                        <input type="checkbox" class="erp-row-check" value="{{ $r->id }}" aria-label="Seleccionar {{ $r->number }}">
                                    @endunless
                                </td>
                            @endif
                            <td class="erp-cell-folio"><a href="{{ route('admin.mrp.show', $r) }}">{{ $r->number }}</a></td>
                            <td>
                                <div class="erp-cell-main">{{ $r->originLabel() }}</div>
                                @if ($r->originNumber())<div class="erp-cell-sub">{{ $r->originNumber() }}</div>@endif
                            </td>
                            <td>{{ $r->customer ? trim($r->customer->first_name . ' ' . $r->customer->last_name) : '—' }}</td>
                            <td class="is-num">{{ $r->lines_count }}</td>
                            <td class="is-num">
                                @if ($r->short_lines_count > 0)
                                    <strong class="erp-tone-bad">{{ $r->short_lines_count }}</strong>
                                @else
                                    <span class="erp-tone-ok">0</span>
                                @endif
                            </td>
                            <td><x-erp.progress :value="$coverage" /></td>
                            <td>@if ($r->needed_by_date)<x-erp.due :date="$r->needed_by_date" :closed="$closed" />@else — @endif</td>
                            <td><x-erp.status :value="$r->status" :label="\App\Models\MaterialRequirement::statusLabel($r->status)" :tone="\App\Models\MaterialRequirement::statusTone($r->status)" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $colspan }}" style="padding: 0; border: 0;">
                            <x-erp.empty title="Aún no hay requerimientos" text="Crea uno con «Nuevo requerimiento» o desde la ficha de un pedido.">
                                @if ($canCreate)<a href="{{ route('admin.mrp.create') }}" class="erp-btn erp-btn--primary">+ Nuevo requerimiento</a>@endif
                            </x-erp.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>{{ $reqs->total() }} {{ $reqs->total() === 1 ? 'requerimiento' : 'requerimientos' }}</span>
            <div>{{ $reqs->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-mrp.js'])
@endpush
