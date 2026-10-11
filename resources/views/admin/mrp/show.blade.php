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
    $poShowRoute = \Illuminate\Support\Facades\Route::has('admin.purchase-orders.show') ? 'admin.purchase-orders.show' : null;
    $pos = $requirement->lines->pluck('purchaseOrder')->filter()->unique('id');
    $hasPurchasable = collect($groups)->isNotEmpty();
    $covered = $totals['lines'] - $totals['short'];
    // Compra sugerida con IVA 16%, por moneda (solo grupos con proveedor).
    $suggested = collect($groups)->where('has_supplier', true)->groupBy('currency')
        ->map(fn ($gs, $cur) => number_format($gs->sum('subtotal') * 1.16, 2) . ' ' . $cur)->values()->implode(' + ');
    $kpis = [
        ['label' => 'Partidas', 'value' => $totals['lines'], 'tone' => 'neu'],
        ['label' => 'Cubiertas', 'value' => $covered, 'tone' => 'ok'],
        ['label' => 'Con faltante', 'value' => $totals['short'], 'tone' => $totals['short'] ? 'bad' : 'ok'],
        ['label' => 'Compra sugerida', 'value' => $suggested !== '' ? '$' . $suggested : '—', 'tone' => 'info'],
    ];
@endphp

@section('content')
<div class="erp-page mrp-page">
    <x-erp.object-header
        :title="$requirement->number"
        :eyebrow="'REQUERIMIENTO DE MATERIAL · MRP1 · ' . strtoupper($requirement->originLabel()) . ($requirement->originNumber() ? ' ' . $requirement->originNumber() : '')"
        :subtitle="$customer ? 'Cliente: ' . $customer : null"
        :status="\App\Models\MaterialRequirement::statusLabel($requirement->status)"
        :tone="\App\Models\MaterialRequirement::statusTone($requirement->status)"
        :back="route('admin.mrp.index')"
        :facts="[
            ['label' => 'Cliente', 'value' => $customer ?: '—'],
            ['label' => 'Proyecto', 'value' => $project ? ($project->project_number ?? '#' . $project->id) : '—'],
            ['label' => 'Requerido para', 'value' => $requirement->needed_by_date?->format('d/m/Y')],
            ['label' => 'Existencias de', 'value' => $warehouse?->name ?? 'Todos los almacenes'],
            ['label' => 'Líneas con faltante', 'value' => $totals['short'] . ' de ' . $totals['lines']],
        ]"
        process="Planeación → Compras → Finanzas → Logística"
        :steps="$steps"
        :current="$currentIdx">
        @unless ($closed)
            @if ($can('edit'))
                <form method="POST" action="{{ route('admin.mrp.recalculate', $requirement) }}" class="mrp-inline-form">@csrf
                    <button class="erp-btn" type="submit">Recalcular</button>
                </form>
                <form method="POST" action="{{ route('admin.mrp.reserve', $requirement) }}" class="mrp-inline-form">@csrf
                    <button class="erp-btn" type="submit">Reservar</button>
                </form>
                <form method="POST" action="{{ route('admin.mrp.release', $requirement) }}" class="mrp-inline-form">@csrf
                    <button class="erp-btn" type="submit" @disabled($totals['reserved'] === 0)>Liberar</button>
                </form>
            @endif
            @if ($can('create') && $canPo && $hasPurchasable)
                <a href="#mrp-compras" class="erp-btn erp-btn--primary">Generar órdenes de compra</a>
            @endif
            @if ($can('delete'))
                <form method="POST" action="{{ route('admin.mrp.cancel', $requirement) }}" class="mrp-inline-form" onsubmit="return confirm('¿Cancelar este requerimiento? Se liberarán sus reservas.')">@csrf
                    <button class="erp-btn erp-btn--danger" type="submit">Cancelar</button>
                </form>
            @endif
        @endunless
    </x-erp.object-header>

    @if (session('success'))<div class="mrp-ok" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="mrp-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="erp-split">
        <div>
            @if ($requirement->notes)
                <x-erp.card title="Notas"><p style="margin: 0;">{{ $requirement->notes }}</p></x-erp.card>
            @endif

            {{-- Planificador: requerido vs existencias, reservas y faltante --}}
            <section class="erp-card mrp-matrix">
                <div class="erp-card-head" style="padding-bottom: 12px;">
                    <div>
                        <h2 class="erp-card-title">Materiales requeridos</h2>
                        <p class="erp-card-sub">Faltante = requerido − (existencia − reservado por otros). «Reservar» aparta las existencias disponibles para este requerimiento.</p>
                    </div>
                    <x-erp.kpi-strip :items="$kpis" />
                </div>
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Demanda</th>
                                <th class="is-right">Requerido</th>
                                <th class="is-right">Existencia</th>
                                <th class="is-right">Reservado (otros)</th>
                                <th class="is-right">Reservado (este)</th>
                                <th class="is-right">Disponible</th>
                                <th class="is-right">Faltante</th>
                                <th>Estado</th>
                                <th>Orden de compra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requirement->lines as $l)
                                @php
                                    $short = $l->shortfall > 0.00005;
                                    $tone = $l->tone();
                                    $avail = max(0, $l->on_hand - $l->reserved);
                                @endphp
                                <tr class="{{ $short && $tone === 'bad' ? 'is-short' : '' }}">
                                    <td>
                                        <div class="erp-cell-main">{{ $l->product->name ?? '—' }}</div>
                                        <div class="erp-cell-sub">{{ $l->product->sku ?? '' }}</div>
                                    </td>
                                    <td>{{ \App\Models\MaterialRequirementLine::sourceLabel($l->source) }}</td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($l->required_quantity)" :unit="$l->unit" /></td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($l->on_hand)" /></td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($l->reserved)" /></td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($l->own_reserved)" /></td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($avail)" /></td>
                                    <td class="is-num"><x-erp.matrix-cell :value="$n($l->shortfall)" :tone="$short ? 'bad' : null" :strong="$short" /></td>
                                    <td>
                                        @if (!$short)
                                            <x-erp.chip tone="ok" label="Cubierto" />
                                        @elseif ($tone === 'warn')
                                            <x-erp.chip tone="info" label="Faltante con OC" />
                                        @else
                                            <x-erp.chip tone="bad" label="Faltante" />
                                        @endif
                                    </td>
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
                                <tr><td colspan="10" style="padding: 0; border: 0;">
                                    <x-erp.empty title="Sin demanda de materiales" text="El origen no genera demanda de materiales (¿sin productos de catálogo?)." />
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if (!$closed)
            <div id="mrp-compras">
                <div class="mrp-section-title">
                    <h2>Compras sugeridas por proveedor</h2>
                    <span>Precios de última compra · IVA 16%</span>
                </div>
                @if (!$hasPurchasable)
                    <x-erp.empty :title="$pos->isNotEmpty() ? 'Todos los faltantes ya tienen orden de compra' : 'No hay faltantes por comprar'"
                                 text="Todo el material está cubierto con existencias o con órdenes de compra." />
                @else
                    <form method="POST" action="{{ route('admin.mrp.generate-purchase-orders', $requirement) }}" data-po-form>
                        @csrf
                        <div class="mrp-suggest">
                            @foreach ($groups as $g)
                                <div class="mrp-sug {{ $g['has_supplier'] ? '' : 'is-nosupplier' }}" data-po-group>
                                    <div>
                                        <div class="mrp-sug-name">{{ $g['has_supplier'] ? ($g['supplier']->company_name ?? $g['supplier']->contact_name ?? ('Proveedor #' . $g['supplier_id'])) : 'Sin proveedor' }}</div>
                                        @if ($g['has_supplier'])
                                            <div class="mrp-sug-lead">{{ $g['currency'] }}{{ $g['lead_time_days'] !== null ? ' · Entrega estimada: ' . $g['lead_time_days'] . ' d' : '' }}</div>
                                        @else
                                            <div class="mrp-sug-warn">Asigna un proveedor para poder comprarlos.</div>
                                        @endif
                                    </div>
                                    <ul class="mrp-sug-items">
                                        @foreach ($g['lines'] as $it)
                                            <li class="mrp-sug-item">
                                                @if ($can('create') && $canPo)
                                                    <input type="checkbox" name="selection[]" value="{{ $it['line']->id }}" @checked($g['has_supplier']) data-po-check aria-label="Comprar {{ $it['product']->name ?? '' }}">
                                                @else
                                                    <span></span>
                                                @endif
                                                <span style="min-width: 0;">
                                                    <b>{{ $it['product']->name ?? '—' }}</b>
                                                    <span class="mrp-muted"> · {{ $it['quantity'] }} × {{ $it['unit_price'] > 0 ? number_format($it['unit_price'], 2) : '—' }}</span>
                                                </span>
                                                <span class="mrp-imp">{{ $it['line_total'] > 0 ? number_format($it['line_total'], 2) : '—' }}</span>
                                                @unless ($g['has_supplier'])
                                                    <span class="mrp-sug-supplier">
                                                        <select name="suppliers[{{ $it['line']->id }}]" class="erp-select" aria-label="Proveedor de {{ $it['product']->name ?? '' }}">
                                                            <option value="">Elegir proveedor…</option>
                                                            @foreach ($suppliers as $s)
                                                                <option value="{{ $s->id }}">{{ $s->company_name ?: $s->contact_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </span>
                                                @endunless
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($g['has_supplier'])
                                        <div class="mrp-sug-totals">
                                            <div><span>Subtotal</span><span>{{ number_format($g['subtotal'], 2) }}</span></div>
                                            <div><span>IVA 16%</span><span>{{ number_format($g['subtotal'] * 0.16, 2) }}</span></div>
                                            <div class="is-total"><span>Total {{ $g['currency'] }}</span><span>{{ number_format($g['subtotal'] * 1.16, 2) }}</span></div>
                                        </div>
                                        @if ($can('create') && $canPo)
                                            <button type="button" class="erp-btn erp-btn--primary mrp-sug-btn" data-po-only>Generar orden de compra</button>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($can('create') && $canPo)
                            <div class="mrp-sug-footer">
                                <span class="mrp-muted">Se creará una orden de compra (pendiente) por proveedor y moneda, con IVA 16%.</span>
                                <button type="submit" class="erp-btn erp-btn--primary">Generar órdenes de compra</button>
                            </div>
                        @endif
                    </form>
                @endif

                @if ($pos->isNotEmpty())
                    <x-erp.card title="Órdenes de compra generadas">
                        <ul class="mrp-polist">
                            @foreach ($pos as $po)
                                <li>
                                    @if ($poShowRoute)<a class="mrp-link" href="{{ route($poShowRoute, $po->id) }}">{{ $po->po_number }}</a>@else<strong>{{ $po->po_number }}</strong>@endif
                                    <x-erp.status :value="$po->status" />
                                    <span class="mrp-muted">{{ number_format((float) $po->total, 2) }} {{ $po->currency }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-erp.card>
                @endif
            </div>
            @endif
        </div>

        <aside>
            <x-erp.document-flow :document="$requirement" />
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-mrp.js'])
@endpush
