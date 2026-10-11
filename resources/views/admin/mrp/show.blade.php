@extends('admin.layouts.master')

@section('title', $requirement->number . ' - MRP - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-mrp.css'])
@endpush

@php
    $user = auth()->user();
    $can = fn ($a) => $user->isAdmin() || $user->hasPermission('mrp', $a);
    $canPo = $user->isAdmin() || $user->hasPermission('purchase-orders', 'create');
    $closed = $requirement->status === 'cancelado';
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
    $customer = $requirement->customer ? trim($requirement->customer->first_name . ' ' . $requirement->customer->last_name) : null;
    $project = $requirement->project;
    $originUrl = null;
    if ($requirement->origin_type === 'sales_order' && \Illuminate\Support\Facades\Route::has('admin.sales-orders.show')) {
        $originUrl = route('admin.sales-orders.show', $requirement->origin_id);
    } elseif ($requirement->origin_type === 'project' && \Illuminate\Support\Facades\Route::has('admin.projects.show')) {
        $originUrl = route('admin.projects.show', $requirement->origin_id);
    }
    $poShowRoute = \Illuminate\Support\Facades\Route::has('admin.purchase-orders.show') ? 'admin.purchase-orders.show' : null;
    $pos = $requirement->lines->pluck('purchaseOrder')->filter()->unique('id');
    $hasPurchasable = collect($groups)->isNotEmpty();
@endphp

@section('content')
<div class="erp-page mrp-page">
    <x-erp.object-header
        :title="$requirement->number"
        :subtitle="'Requerimiento de materiales · ' . $requirement->originLabel() . ($requirement->originNumber() ? ' ' . $requirement->originNumber() : '')"
        :status="\App\Models\MaterialRequirement::statusLabel($requirement->status)"
        :tone="\App\Models\MaterialRequirement::statusTone($requirement->status)"
        :back="route('admin.mrp.index')"
        :facts="[
            ['label' => 'Cliente', 'value' => $customer ?: '—'],
            ['label' => 'Proyecto', 'value' => $project ? ($project->project_number ?? '#' . $project->id) : '—'],
            ['label' => 'Requerido para', 'value' => $requirement->needed_by_date?->format('d/m/Y')],
            ['label' => 'Existencias de', 'value' => $warehouse?->name ?? 'Todos los almacenes'],
            ['label' => 'Líneas con faltante', 'value' => $totals['short'] . ' de ' . $totals['lines']],
        ]">
        @unless ($closed)
            @if ($can('edit'))
                <form method="POST" action="{{ route('admin.mrp.recalculate', $requirement) }}">@csrf
                    <button class="mrp-btn" type="submit">Recalcular</button>
                </form>
                <form method="POST" action="{{ route('admin.mrp.reserve', $requirement) }}">@csrf
                    <button class="mrp-btn" type="submit">Reservar</button>
                </form>
                <form method="POST" action="{{ route('admin.mrp.release', $requirement) }}">@csrf
                    <button class="mrp-btn" type="submit" @disabled($totals['reserved'] === 0)>Liberar</button>
                </form>
            @endif
            @if ($can('create') && $canPo && $hasPurchasable)
                <a href="#mrp-compras" class="mrp-btn mrp-btn-primary">Generar órdenes de compra</a>
            @endif
            @if ($can('delete'))
                <form method="POST" action="{{ route('admin.mrp.cancel', $requirement) }}" onsubmit="return confirm('¿Cancelar este requerimiento? Se liberarán sus reservas.')">@csrf
                    <button class="mrp-btn mrp-btn-danger" type="submit">Cancelar</button>
                </form>
            @endif
        @endunless
    </x-erp.object-header>

    @if (session('success'))<div class="mrp-ok">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="mrp-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-erp.process-chevrons :steps="$steps" :current="$currentIdx" />

    <x-erp.document-flow :document="$requirement" />

    @if ($requirement->notes)
        <div class="mrp-card mrp-pad"><strong>Notas:</strong> {{ $requirement->notes }}</div>
    @endif

    <div class="mrp-card mrp-pad">
        <div class="mrp-section-head">
            <h2 class="mrp-h2">Materiales requeridos</h2>
            <div class="mrp-legend">
                <span class="mrp-dot mrp-dot-ok"></span> Cubierto
                <span class="mrp-dot mrp-dot-warn"></span> Faltante con OC
                <span class="mrp-dot mrp-dot-bad"></span> Faltante sin OC
            </div>
        </div>
        <div class="mrp-table-scroll">
            <table class="mrp-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Producto</th>
                        <th>Demanda</th>
                        <th class="num">Requerido</th>
                        <th class="num">En existencia</th>
                        <th class="num">Reservado (otros)</th>
                        <th class="num">Reservado (este)</th>
                        <th class="num">Faltante</th>
                        <th>Orden de compra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requirement->lines as $l)
                        <tr>
                            <td><span class="mrp-dot mrp-dot-{{ $l->tone() }}" title="{{ $l->tone() === 'ok' ? 'Cubierto' : ($l->tone() === 'warn' ? 'Faltante con OC' : 'Faltante sin OC') }}"></span></td>
                            <td>
                                <strong>{{ $l->product->name ?? '—' }}</strong>
                                <div class="mrp-muted">{{ $l->product->sku ?? '' }}</div>
                            </td>
                            <td>{{ \App\Models\MaterialRequirementLine::sourceLabel($l->source) }}</td>
                            <td class="num">{{ $n($l->required_quantity) }} <span class="mrp-muted">{{ $l->unit }}</span></td>
                            <td class="num">{{ $n($l->on_hand) }}</td>
                            <td class="num">{{ $n($l->reserved) }}</td>
                            <td class="num">{{ $n($l->own_reserved) }}</td>
                            <td class="num"><span class="erp-tone-{{ $l->shortfall > 0.00005 ? 'bad' : 'ok' }}"><strong>{{ $n($l->shortfall) }}</strong></span></td>
                            <td>
                                @if ($l->purchaseOrder)
                                    @if ($poShowRoute)
                                        <a class="mrp-link" href="{{ route($poShowRoute, $l->purchaseOrder->id) }}">{{ $l->purchaseOrder->po_number }}</a>
                                    @else
                                        {{ $l->purchaseOrder->po_number }}
                                    @endif
                                @else
                                    <span class="mrp-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="mrp-empty">El origen no genera demanda de materiales (¿sin productos de catálogo?).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mrp-muted">Faltante = requerido − (existencia − reservado por otros). «Reservar» aparta las existencias disponibles para este requerimiento.</p>
    </div>

    @if (!$closed)
    <div class="mrp-card mrp-pad" id="mrp-compras">
        <div class="mrp-section-head"><h2 class="mrp-h2">Compras sugeridas por proveedor</h2></div>
        @if (!$hasPurchasable)
            <p class="mrp-muted">
                @if ($pos->isNotEmpty())
                    Todos los faltantes ya tienen orden de compra.
                @else
                    No hay faltantes por comprar.
                @endif
            </p>
        @else
            <form method="POST" action="{{ route('admin.mrp.generate-purchase-orders', $requirement) }}" data-po-form>
                @csrf
                @foreach ($groups as $g)
                    <div class="mrp-group {{ $g['has_supplier'] ? '' : 'is-nosupplier' }}">
                        <div class="mrp-group-head">
                            <div>
                                <strong>{{ $g['has_supplier'] ? ($g['supplier']->company_name ?? $g['supplier']->contact_name ?? ('Proveedor #' . $g['supplier_id'])) : 'Sin proveedor' }}</strong>
                                @if ($g['has_supplier'])<span class="mrp-muted"> · {{ $g['currency'] }}{{ $g['lead_time_days'] !== null ? ' · entrega ' . $g['lead_time_days'] . ' d' : '' }}</span>@endif
                            </div>
                            @if ($g['has_supplier'])
                                <div class="mrp-muted">Subtotal: <strong>{{ number_format($g['subtotal'], 2) }} {{ $g['currency'] }}</strong> (sin IVA)</div>
                            @else
                                <div class="mrp-muted">Asigna un proveedor para poder comprarlos.</div>
                            @endif
                        </div>
                        <table class="mrp-table">
                            <thead>
                                <tr>
                                    <th style="width:32px"></th>
                                    <th>Producto</th>
                                    <th class="num">Cantidad</th>
                                    <th class="num">Precio unit.</th>
                                    <th class="num">Importe</th>
                                    @unless ($g['has_supplier'])<th>Proveedor</th>@endunless
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($g['lines'] as $it)
                                    <tr>
                                        <td>
                                            @if ($can('create') && $canPo)
                                                <input type="checkbox" name="selection[]" value="{{ $it['line']->id }}" @checked($g['has_supplier']) data-po-check>
                                            @endif
                                        </td>
                                        <td>{{ $it['product']->name ?? '—' }} <span class="mrp-muted">{{ $it['product']->sku ?? '' }}</span></td>
                                        <td class="num">{{ $it['quantity'] }}</td>
                                        <td class="num">{{ $it['unit_price'] > 0 ? number_format($it['unit_price'], 2) : '—' }}</td>
                                        <td class="num">{{ $it['line_total'] > 0 ? number_format($it['line_total'], 2) : '—' }}</td>
                                        @unless ($g['has_supplier'])
                                            <td>
                                                <select name="suppliers[{{ $it['line']->id }}]" class="mrp-input mrp-input-sm">
                                                    <option value="">Elegir proveedor…</option>
                                                    @foreach ($suppliers as $s)
                                                        <option value="{{ $s->id }}">{{ $s->company_name ?: $s->contact_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endunless
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                @if ($can('create') && $canPo)
                    <div class="mrp-actions">
                        <span class="mrp-muted">Se creará una orden de compra (pendiente) por proveedor y moneda, con IVA 16%.</span>
                        <button type="submit" class="mrp-btn mrp-btn-primary">Generar órdenes de compra</button>
                    </div>
                @endif
            </form>
        @endif

        @if ($pos->isNotEmpty())
            <h3 class="mrp-h3">Órdenes de compra generadas</h3>
            <ul class="mrp-polist">
                @foreach ($pos as $po)
                    <li>
                        @if ($poShowRoute)<a class="mrp-link" href="{{ route($poShowRoute, $po->id) }}">{{ $po->po_number }}</a>@else{{ $po->po_number }}@endif
                        <span class="erp-chip erp-tone-neutral">{{ $po->status }}</span>
                        <span class="mrp-muted">{{ number_format((float) $po->total, 2) }} {{ $po->currency }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-mrp.js'])
@endpush
