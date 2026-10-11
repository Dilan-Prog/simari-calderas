@extends('admin.layouts.master')
@section('title')
    Conversiones a Google - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-crm.js'])
@endpush
@section('content')
    @php
        $chipItems = collect($chips)->map(fn ($c) => ['label' => $c['label'] . ': ' . $c['value'], 'url' => $c['remove']])->all();
        $st = request('status');
        $views = [
            ['label' => 'Todas', 'url' => route('admin.google-conversions.index'), 'active' => !$st && !request('por_vencer')],
            ['label' => 'Fallidas', 'url' => route('admin.google-conversions.index', ['status' => 'fallida']), 'active' => $st === 'fallida'],
            ['label' => 'Pendientes', 'url' => route('admin.google-conversions.index', ['status' => 'pendiente']), 'active' => $st === 'pendiente'],
            ['label' => 'Enviadas', 'url' => route('admin.google-conversions.index', ['status' => 'enviada']), 'active' => $st === 'enviada'],
        ];
    @endphp
    <div class="erp-page">
        <x-erp.page-header
            title="Conversiones a Google Ads"
            code="VTA2"
            :crumbs="[['Ventas', null], ['Conversiones a Google Ads', null]]">
            <a class="erp-btn" href="{{ route('admin.google-ads.index') }}">Panel legado</a>
            <form method="POST" action="{{ route('admin.google-conversions.retry-failed') }}" data-crm-confirm="¿Reintentar todas las subidas fallidas?">@csrf
                <button class="erp-btn erp-btn--primary" type="submit">Reintentar fallidas</button>
            </form>
        </x-erp.page-header>
        <p class="erp-subtitle" style="margin: -12px 0 16px;">
            Cotizado (secundaria) y Venta ganada (principal) enviadas con la Data Manager API.
            @unless ($configured)
                <strong class="erp-tone-warn">Credenciales de Google sin configurar: las conversiones quedan en cola.</strong>
            @endunless
        </p>

        @if (session('success'))<div class="crm-flash ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err" role="alert">{{ session('error') }}</div>@endif

        <div class="erp-tiles crm-tiles">
            <x-erp.kpi-tile title="Enviadas" :value="$summary['enviadas']" tone="ok" caption="Subidas aceptadas por Google"
                :href="route('admin.google-conversions.index', ['status' => 'enviada'])" />
            <x-erp.kpi-tile title="Pendientes" :value="$summary['pendientes']" tone="warn" caption="En cola de envío"
                :href="route('admin.google-conversions.index', ['status' => 'pendiente'])" />
            <x-erp.kpi-tile title="Fallidas" :value="$summary['fallidas']" :tone="$summary['fallidas'] ? 'bad' : 'ok'" caption="Requieren reintento o revisión"
                :href="route('admin.google-conversions.index', ['status' => 'fallida'])" />
            <x-erp.kpi-tile title="Sin atribución" :value="$summary['sin_atribucion']" tone="neu" caption="Sin GCLID ni datos de contacto"
                :href="route('admin.google-conversions.index', ['status' => 'sin_atribucion'])" />
            <x-erp.kpi-tile title="GCLID por vencer" :value="$summary['por_vencer']" :tone="$summary['por_vencer'] ? 'warn' : 'ok'"
                :caption="'Vencen en ' . config('google_conversions.expiring_days') . ' días o menos'"
                :href="route('admin.google-conversions.index', ['por_vencer' => 1])" />
        </div>

        <x-erp.worklist :views="$views" :chips="$chipItems">
            <x-slot:toolbar>
                <form method="GET" class="crm-toolbar-form" role="search">
                    <label class="erp-field erp-field--grow">Buscar
                        <input class="erp-input" type="text" name="q" value="{{ request('q') }}" placeholder="Order ID o identificador">
                    </label>
                    <label class="erp-field">Estado
                        <select class="erp-select" name="status" data-crm-autosubmit>
                            <option value="">Todos</option>
                            @foreach (\App\Models\GoogleConversionUpload::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Acción
                        <select class="erp-select" name="action" data-crm-autosubmit>
                            <option value="">Todas</option>
                            @foreach (\App\Models\GoogleConversionUpload::ACTIONS as $k => $label)
                                <option value="{{ $k }}" @selected(request('action') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Operación
                        <select class="erp-select" name="operation" data-crm-autosubmit>
                            <option value="">Todas</option>
                            @foreach (\App\Models\GoogleConversionUpload::OPERATIONS as $k => $label)
                                <option value="{{ $k }}" @selected(request('operation') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Desde<input class="erp-input" type="date" name="desde" value="{{ request('desde') }}"></label>
                    <label class="erp-field">Hasta<input class="erp-input" type="date" name="hasta" value="{{ request('hasta') }}"></label>
                    <button class="erp-btn" type="submit">Filtrar</button>
                    @if (count($chipItems))<a class="erp-btn erp-btn--link" href="{{ route('admin.google-conversions.index') }}">Limpiar todo</a>@endif
                </form>
            </x-slot:toolbar>

            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead>
                        <tr><th>#</th><th>Order ID</th><th>Acción</th><th>Operación</th><th>Identificador</th><th class="is-right">Valor (MXN)</th><th>Estado</th><th class="is-right">Intentos</th><th>Fecha</th><th><span class="sr-only">Acciones</span></th></tr>
                    </thead>
                    <tbody>
                    @forelse ($uploads as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td><a class="crm-link" href="{{ route('admin.google-conversions.show', $u) }}">{{ $u->order_id }}</a></td>
                            <td>{{ $u->action_label }}</td>
                            <td>{{ $u->operation_label }}</td>
                            <td class="crm-mono">{{ strtoupper($u->identifier_type) }}@if ($u->identifier): {{ \Illuminate\Support\Str::limit($u->identifier, 18) }}@endif
                                @if ($u->gclid_expires_at && $u->status === 'pendiente')<div class="crm-muted">vence {{ $u->gclid_expires_at->format('d/m/Y') }}</div>@endif
                            </td>
                            <td class="is-num">{{ $u->conversion_value !== null ? '$' . number_format((float) $u->conversion_value, 2) : '—' }}</td>
                            <td>
                                <x-erp.chip :tone="$u->status_tone" :label="$u->status_label" />
                                @if ($u->last_error)<div class="crm-err-line" title="{{ $u->last_error }}">{{ \Illuminate\Support\Str::limit($u->last_error, 50) }}</div>@endif
                            </td>
                            <td class="is-num">{{ $u->attempts }}/{{ config('google_conversions.max_attempts') }}</td>
                            <td>{{ $u->created_at->format('d/m/Y H:i') }}</td>
                            <td class="is-right">
                                @if ($u->canRetry())
                                    <form method="POST" action="{{ route('admin.google-conversions.retry', $u) }}">@csrf
                                        <button class="erp-btn erp-btn--sm" type="submit">Reintentar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><x-erp.empty title="Aún no hay conversiones con estos filtros" text="Las conversiones se generan al cotizar o ganar un negocio con un lead atribuido." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <x-slot:footer>{{ $uploads->links() }}</x-slot:footer>
        </x-erp.worklist>
    </div>
@endsection
