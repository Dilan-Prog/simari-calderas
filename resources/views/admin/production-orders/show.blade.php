@extends('admin.layouts.master')

@section('title', $order->number . ' - Producción - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/erp-production.js'])
@endpush

@php
    use App\Models\ProductionOrder;
    use App\Support\ErpUi;

    $user = auth()->user();
    $can = fn ($a) => $user->isAdmin() || $user->hasPermission('production-orders', $a);
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.') ?: '0';
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $steps = ['Borrador', 'Liberada', 'En proceso', 'Terminada'];
    $currentIdx = match ($order->status) { 'borrador' => 0, 'liberada' => 1, 'en_proceso' => 2, 'terminada' => 3, default => -1 };
    $cancelled = $order->status === 'cancelada';
    $open = $order->isOpen();
    $running = in_array($order->status, ['liberada', 'en_proceso'], true);
    $remaining = max(0, $order->quantity_planned - $order->quantity_produced);
    $pendingConsume = $order->components->contains(fn ($c) => $c->pending() > 0.00005);
    $anyConsumed = $order->components->contains(fn ($c) => $c->consumed_quantity > 0);
    $stdProduced = $order->standardCostProduced();
    $variance = round($order->actual_cost - $stdProduced, 2);
    $days = $order->daysToDue();
    $shortCount = $cancelled ? 0 : $order->components->filter(fn ($c) => $c->shortfall() > 0.00005)->count();
    $canConsumeNow = $running && $can('edit') && $pendingConsume;
    $tone = ErpUi::tone($order->status);
@endphp

@section('content')
<div class="erp-page prd-page">
    <x-erp.object-header
        eyebrow="ORDEN DE PRODUCCIÓN · PRD2"
        :title="$order->number"
        :subtitle="$order->product->name ?? '—'"
        :status="ProductionOrder::statusLabel($order->status)"
        :tone="$tone"
        :back="route('admin.production-orders.index')"
        process="PLAN-TO-PRODUCE"
        :steps="$cancelled ? [] : $steps"
        :current="$currentIdx"
        :facts="[
            ['label' => 'Cantidad', 'value' => $n($order->quantity_produced) . ' / ' . $n($order->quantity_planned)],
            ['label' => 'Almacén', 'value' => $order->warehouse?->name],
            ['label' => 'Proyecto', 'value' => $project ? ($project->project_number ?? '#' . $project->id) : null],
            ['label' => 'Pedido', 'value' => $order->salesOrder?->order_number],
            ['label' => 'Fecha límite', 'value' => $order->due_date ? $order->due_date->format('d/m/Y') . ($open && $days !== null ? ($days < 0 ? ' (vencida ' . abs($days) . ' d)' : ' (' . $days . ' d)') : '') : null],
        ]">
        @if ($open && $can('edit'))
            <a href="{{ route('admin.production-orders.edit', $order) }}" class="erp-btn">Editar</a>
        @endif
        @if ($order->status === 'borrador' && $can('edit'))
            <form method="POST" action="{{ route('admin.production-orders.release', $order) }}">@csrf
                <button class="erp-btn erp-btn--primary" type="submit" data-confirm="¿Liberar la orden? Se generará el requerimiento de materiales y se reservarán los componentes disponibles.">Liberar</button>
            </form>
        @endif
        @if ($canConsumeNow)
            <form method="POST" action="{{ route('admin.production-orders.consume', $order) }}">@csrf
                <button class="erp-btn" type="submit" data-confirm="¿Consumir todos los componentes pendientes? Se registrará la salida de inventario.">Consumir todo</button>
            </form>
        @endif
        @if ($open && $order->status !== 'borrador' && $can('delete'))
            <form method="POST" action="{{ route('admin.production-orders.cancel', $order) }}" data-cancel-form data-consumed="{{ $anyConsumed ? 1 : 0 }}">@csrf
                <input type="hidden" name="return_consumed" value="0">
                <button class="erp-btn erp-btn--danger" type="submit">Cancelar</button>
            </form>
        @endif
        @if ($order->status === 'borrador' && $can('delete'))
            <form method="POST" action="{{ route('admin.production-orders.destroy', $order) }}">@csrf @method('DELETE')
                <button class="erp-btn erp-btn--danger" type="submit" data-confirm="¿Eliminar esta orden en borrador?">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('success'))<div class="prd-ok" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="prd-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @if ($cancelled)
        <div class="prd-alert" role="alert">Esta orden fue cancelada.</div>
    @elseif ($shortCount > 0 && $open)
        <div class="prd-warn" role="status">{{ $shortCount }} {{ $shortCount === 1 ? 'componente tiene' : 'componentes tienen' }} faltante de material. Revisa la pestaña Componentes.</div>
    @endif

    <div class="erp-split prd-split">
        <div class="erp-card prd-main" data-erp-tabs>
            <x-erp.tab-nav :tabs="['resumen' => 'Resumen', 'componentes' => 'Componentes' . ($shortCount ? ' (' . $shortCount . ' faltantes)' : ''), 'bitacora' => 'Bitácora']" active="resumen" />

            {{-- Resumen: avance, terminar, costos y notas --}}
            <div class="erp-tabpanel is-active" data-panel="resumen">
                <div class="prd-section-head">
                    <h2 class="prd-h2">Avance</h2>
                    <x-erp.progress :value="$order->progress" />
                </div>
                <p class="prd-muted">Producidas {{ $n($order->quantity_produced) }} de {{ $n($order->quantity_planned) }} @if($order->bom)· BOM v{{ $order->bom->version ?? $order->bom_id }}@endif</p>

                @if ($running && $can('edit') && $remaining > 0)
                    <form method="POST" action="{{ route('admin.production-orders.finish', $order) }}" class="prd-inline-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ \Illuminate\Support\Str::random(24) }}">
                        <label>Terminar cantidad
                            <input type="number" name="quantity" class="erp-input prd-input-sm" min="1" max="{{ floor($remaining) }}" step="1" value="{{ floor($remaining) }}">
                        </label>
                        <label class="prd-grow">Nota (opcional)
                            <input type="text" name="note" class="erp-input prd-input-sm" maxlength="500" placeholder="Nota (opcional)">
                        </label>
                        <button class="erp-btn erp-btn--primary" type="submit" data-confirm="¿Registrar producción terminada? Se consumirán los componentes proporcionales y se dará entrada al producto terminado.">Terminar</button>
                    </form>
                    <small class="prd-muted">Puedes terminar por partes (producción parcial). Al terminar se consume automáticamente lo que falte de los componentes.</small>
                @endif

                <h2 class="prd-h2 prd-h2--gap">Costo real vs. estándar</h2>
                <dl class="prd-costs">
                    <div><dt>Estándar (orden completa)</dt><dd>{{ $money($order->standard_cost) }}</dd></div>
                    <div><dt>Estándar de lo producido</dt><dd>{{ $money($stdProduced) }}</dd></div>
                    <div><dt>Costo real (consumido)</dt><dd>{{ $money($order->actual_cost) }}</dd></div>
                    <div>
                        <dt>Variación (real − estándar producido)</dt>
                        <dd class="{{ $order->quantity_produced > 0 ? ($variance > 0.005 ? 'erp-tone-bad' : 'erp-tone-ok') : '' }}">
                            {{ $order->quantity_produced > 0 ? ($variance > 0 ? '+' : '') . $money($variance) : '—' }}
                        </dd>
                    </div>
                </dl>

                @if ($order->notes)
                    <h2 class="prd-h2 prd-h2--gap">Notas</h2>
                    <p class="prd-notes">{{ $order->notes }}</p>
                @endif
            </div>

            {{-- Componentes: requerido / reservado / consumido --}}
            <div class="erp-tabpanel" data-panel="componentes">
                <div class="prd-section-head">
                    <h2 class="prd-h2">Componentes</h2>
                    @if ($order->requirement)
                        <a class="prd-link" href="{{ route('admin.mrp.show', $order->requirement) }}">Requerimiento {{ $order->requirement->number }}</a>
                    @endif
                </div>
                @if ($canConsumeNow)
                    <form method="POST" action="{{ route('admin.production-orders.consume', $order) }}" id="prd-consume-form">@csrf</form>
                @endif
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th class="is-right">Requerido</th>
                                <th class="is-right">Reservado</th>
                                <th class="is-right">Consumido</th>
                                <th class="is-right">Faltante</th>
                                @if ($canConsumeNow)<th class="is-right">Consumir ahora</th>@endif
                                <th class="is-right">Costo unit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->components as $c)
                                @php $short = $cancelled ? 0 : $c->shortfall(); $isShort = $short > 0.00005 && $open; @endphp
                                <tr class="{{ $isShort ? 'prd-row-short' : '' }}">
                                    <td>
                                        <div class="erp-cell-main">{{ $c->product->name ?? '—' }}</div>
                                        <div class="erp-cell-sub">{{ $c->product->sku ?? '' }}</div>
                                    </td>
                                    <td class="is-num">{{ $n($c->required_quantity) }} <span class="erp-cell-sub">{{ $c->unit }}</span></td>
                                    <td class="is-num">{{ $n($c->reserved_quantity) }}</td>
                                    <td class="is-num">{{ $n($c->consumed_quantity) }}</td>
                                    <td class="is-num">
                                        @if ($isShort)
                                            <span class="prd-short"><strong>{{ $n($short) }}</strong></span>
                                        @else
                                            <span class="erp-tone-ok">0</span>
                                        @endif
                                    </td>
                                    @if ($canConsumeNow)
                                        <td class="is-num">
                                            @if ($c->pending() > 0.00005)
                                                <input type="number" form="prd-consume-form" name="quantities[{{ $c->id }}]" class="erp-input prd-input-sm prd-input-num" min="0" max="{{ ceil($c->pending()) }}" step="1" placeholder="0" aria-label="Consumir {{ $c->product->name ?? 'componente' }}">
                                            @else
                                                <span class="prd-muted">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="is-num">{{ $money($c->unit_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($canConsumeNow)
                    <div class="erp-form-actions">
                        <button class="erp-btn" type="submit" form="prd-consume-form" data-confirm="¿Consumir las cantidades capturadas? Se registrará la salida de inventario.">Consumir cantidades capturadas</button>
                    </div>
                    <small class="prd-muted">El inventario se mueve por unidades enteras: las fracciones se consumen redondeadas hacia arriba.</small>
                @endif
            </div>

            {{-- Bitácora --}}
            <div class="erp-tabpanel" data-panel="bitacora">
                <h2 class="prd-h2">Bitácora</h2>
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Fecha</th><th>Acción</th><th>Estatus</th><th>Avance</th><th>Usuario</th><th>Nota</th></tr></thead>
                        <tbody>
                            @forelse ($order->logs as $l)
                                <tr>
                                    <td class="prd-nowrap">{{ $l->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ ucfirst($l->action) }}</td>
                                    <td>@if ($l->to_status)<x-erp.chip :tone="ErpUi::tone($l->to_status)" :label="ProductionOrder::statusLabel($l->to_status)" />@else — @endif</td>
                                    <td>{{ $l->progress !== null ? $l->progress . '%' : '—' }}</td>
                                    <td>{{ $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : '—' }}</td>
                                    <td>{{ $l->note }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-erp.empty title="Sin movimientos" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <aside class="prd-side">
            <x-erp.document-flow :document="$order" />
        </aside>
    </div>
</div>
@endsection
