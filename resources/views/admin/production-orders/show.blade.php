@extends('admin.layouts.master')

@section('title', $order->number . ' - Producción - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/erp-production.js'])
@endpush

@php
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
@endphp

@section('content')
<div class="erp-page prd-page">
    <x-erp.object-header
        :title="$order->number"
        :subtitle="'Orden de producción · ' . ($order->product->name ?? '—')"
        :status="\App\Models\ProductionOrder::statusLabel($order->status)"
        :tone="\App\Models\ProductionOrder::statusTone($order->status)"
        :back="route('admin.production-orders.index')"
        :facts="[
            ['label' => 'Cantidad', 'value' => $n($order->quantity_produced) . ' / ' . $n($order->quantity_planned)],
            ['label' => 'Almacén', 'value' => $order->warehouse?->name],
            ['label' => 'Proyecto', 'value' => $project ? ($project->project_number ?? '#' . $project->id) : null],
            ['label' => 'Pedido', 'value' => $order->salesOrder?->order_number],
            ['label' => 'Fecha límite', 'value' => $order->due_date ? $order->due_date->format('d/m/Y') . ($open && $days !== null ? ($days < 0 ? ' (vencida ' . abs($days) . ' d)' : ' (' . $days . ' d)') : '') : null],
        ]">
        @if ($open && $can('edit'))
            <a href="{{ route('admin.production-orders.edit', $order) }}" class="prd-btn">Editar</a>
        @endif
        @if ($order->status === 'borrador' && $can('edit'))
            <form method="POST" action="{{ route('admin.production-orders.release', $order) }}">@csrf
                <button class="prd-btn prd-btn-primary" type="submit" data-confirm="¿Liberar la orden? Se generará el requerimiento de materiales y se reservarán los componentes disponibles.">Liberar</button>
            </form>
        @endif
        @if ($running && $can('edit') && $pendingConsume)
            <form method="POST" action="{{ route('admin.production-orders.consume', $order) }}">@csrf
                <button class="prd-btn" type="submit" data-confirm="¿Consumir todos los componentes pendientes? Se registrará la salida de inventario.">Consumir todo</button>
            </form>
        @endif
        @if ($open && $order->status !== 'borrador' && $can('delete'))
            <form method="POST" action="{{ route('admin.production-orders.cancel', $order) }}" data-cancel-form data-consumed="{{ $anyConsumed ? 1 : 0 }}">@csrf
                <input type="hidden" name="return_consumed" value="0">
                <button class="prd-btn prd-btn-danger" type="submit">Cancelar</button>
            </form>
        @endif
        @if ($order->status === 'borrador' && $can('delete'))
            <form method="POST" action="{{ route('admin.production-orders.destroy', $order) }}">@csrf @method('DELETE')
                <button class="prd-btn prd-btn-danger" type="submit" data-confirm="¿Eliminar esta orden en borrador?">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('success'))<div class="prd-ok">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="prd-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if ($cancelled)
        <div class="prd-alert">Esta orden fue cancelada.</div>
    @else
        <x-erp.process-chevrons :steps="$steps" :current="$currentIdx" />
    @endif

    <x-erp.document-flow :document="$order" />

    <div class="prd-card prd-pad">
        <div class="prd-section-head">
            <h2 class="prd-h2">Avance</h2>
            <strong>{{ $order->progress }}%</strong>
        </div>
        <div class="prd-bar prd-bar-lg"><span style="width: {{ $order->progress }}%"></span></div>
        <p class="prd-muted" style="margin-top:6px">Producidas {{ $n($order->quantity_produced) }} de {{ $n($order->quantity_planned) }} @if($order->bom)· BOM v{{ $order->bom->version ?? $order->bom_id }}@endif</p>

        @if ($running && $can('edit') && $remaining > 0)
            <form method="POST" action="{{ route('admin.production-orders.finish', $order) }}" class="prd-inline-form">
                @csrf
                <input type="hidden" name="token" value="{{ \Illuminate\Support\Str::random(24) }}">
                <label>Terminar cantidad
                    <input type="number" name="quantity" class="prd-input prd-input-sm" min="1" max="{{ floor($remaining) }}" step="1" value="{{ floor($remaining) }}">
                </label>
                <input type="text" name="note" class="prd-input prd-input-sm" maxlength="500" placeholder="Nota (opcional)">
                <button class="prd-btn prd-btn-primary" type="submit" data-confirm="¿Registrar producción terminada? Se consumirán los componentes proporcionales y se dará entrada al producto terminado.">Terminar</button>
            </form>
            <small class="prd-muted">Puedes terminar por partes (producción parcial). Al terminar se consume automáticamente lo que falte de los componentes.</small>
        @endif
    </div>

    <div class="prd-card prd-pad">
        <div class="prd-section-head">
            <h2 class="prd-h2">Componentes</h2>
            @if ($order->requirement)
                <a class="prd-link" href="{{ route('admin.mrp.show', $order->requirement) }}">Requerimiento {{ $order->requirement->number }}</a>
            @endif
        </div>
        @if ($running && $can('edit') && $pendingConsume)
            <form method="POST" action="{{ route('admin.production-orders.consume', $order) }}" id="prd-consume-form">@csrf</form>
        @endif
        <div class="prd-table-scroll">
            <table class="prd-table">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th class="num">Requerido</th>
                        <th class="num">Reservado</th>
                        <th class="num">Consumido</th>
                        <th class="num">Faltante</th>
                        @if ($running && $can('edit') && $pendingConsume)<th class="num">Consumir ahora</th>@endif
                        <th class="num">Costo unit.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->components as $c)
                        @php $short = $order->status === 'cancelada' ? 0 : $c->shortfall(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $c->product->name ?? '—' }}</strong>
                                <div class="prd-muted">{{ $c->product->sku ?? '' }}</div>
                            </td>
                            <td class="num">{{ $n($c->required_quantity) }} <span class="prd-muted">{{ $c->unit }}</span></td>
                            <td class="num">{{ $n($c->reserved_quantity) }}</td>
                            <td class="num">{{ $n($c->consumed_quantity) }}</td>
                            <td class="num">
                                @if ($short > 0.00005 && $open)
                                    <span class="erp-tone-bad"><strong>{{ $n($short) }}</strong></span>
                                @else
                                    <span class="erp-tone-ok">0</span>
                                @endif
                            </td>
                            @if ($running && $can('edit') && $pendingConsume)
                                <td class="num">
                                    @if ($c->pending() > 0.00005)
                                        <input type="number" form="prd-consume-form" name="quantities[{{ $c->id }}]" class="prd-input prd-input-sm prd-input-num" min="0" max="{{ ceil($c->pending()) }}" step="1" placeholder="0">
                                    @else
                                        <span class="prd-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="num">{{ $money($c->unit_cost) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($running && $can('edit') && $pendingConsume)
            <div class="prd-actions">
                <button class="prd-btn" type="submit" form="prd-consume-form" data-confirm="¿Consumir las cantidades capturadas? Se registrará la salida de inventario.">Consumir cantidades capturadas</button>
            </div>
            <small class="prd-muted">El inventario se mueve por unidades enteras: las fracciones se consumen redondeadas hacia arriba.</small>
        @endif
    </div>

    <div class="prd-card prd-pad">
        <h2 class="prd-h2">Costo real vs. estándar</h2>
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
    </div>

    @if ($order->notes)
        <div class="prd-card prd-pad"><strong>Notas:</strong> {{ $order->notes }}</div>
    @endif

    <div class="prd-card prd-pad">
        <h2 class="prd-h2">Bitácora</h2>
        <div class="prd-table-scroll">
            <table class="prd-table">
                <thead><tr><th>Fecha</th><th>Acción</th><th>Estado</th><th>Avance</th><th>Usuario</th><th>Nota</th></tr></thead>
                <tbody>
                    @forelse ($order->logs as $l)
                        <tr>
                            <td>{{ $l->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ ucfirst($l->action) }}</td>
                            <td>{{ $l->to_status ? \App\Models\ProductionOrder::statusLabel($l->to_status) : '—' }}</td>
                            <td>{{ $l->progress !== null ? $l->progress . '%' : '—' }}</td>
                            <td>{{ $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : '—' }}</td>
                            <td>{{ $l->note }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="prd-empty">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
