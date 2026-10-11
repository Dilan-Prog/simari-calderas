@extends('admin.layouts.master')
@section('title')
    Subida {{ $upload->order_id }} - Conversiones a Google - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-crm.js'])
@endpush
@section('content')
    <div class="erp-page">
        <x-erp.object-header
            :title="$upload->order_id"
            :subtitle="$upload->action_label . ' · ' . $upload->operation_label"
            :status="$upload->status_label"
            :tone="$upload->status_tone"
            :back="route('admin.google-conversions.index')"
            eyebrow="CONVERSIÓN OFFLINE · VTA2"
            :facts="[
                ['label' => 'Valor', 'value' => $upload->conversion_value !== null ? '$' . number_format((float) $upload->conversion_value, 2) . ' ' . $upload->currency : null],
                ['label' => 'Identificador', 'value' => strtoupper($upload->identifier_type)],
                ['label' => 'Intentos', 'value' => $upload->attempts . '/' . config('google_conversions.max_attempts')],
                ['label' => 'Enviada', 'value' => optional($upload->sent_at)->format('d/m/Y H:i')],
            ]">
            @if ($upload->lead)
                <a class="erp-btn" href="{{ route('admin.leads-inbox.show', $upload->lead) }}">Ver lead</a>
            @endif
            @if ($upload->canRetry())
                <form method="POST" action="{{ route('admin.google-conversions.retry', $upload) }}">@csrf
                    <button class="erp-btn erp-btn--primary" type="submit">Reintentar</button>
                </form>
            @endif
        </x-erp.object-header>

        @if (session('success'))<div class="crm-flash ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err" role="alert">{{ session('error') }}</div>@endif

        <div class="crm-detail">
            <div>
                @if ($upload->last_error)
                    <x-erp.card title="Último error">
                        <div class="crm-error-box" role="alert">{{ $upload->last_error }}</div>
                        @if ($upload->alerted_at)<p class="crm-muted" style="margin:8px 0 0">Alerta generada el {{ $upload->alerted_at->format('d/m/Y H:i') }} (tarea de seguimiento).</p>@endif
                    </x-erp.card>
                @endif

                <x-erp.card title="Respuesta de Google">
                    @if ($upload->response)
                        <pre class="crm-json">{{ json_encode($upload->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <p class="crm-muted" style="margin:0">Sin respuesta registrada.</p>
                    @endif
                </x-erp.card>

                @if ($upload->adjustments->count())
                    <x-erp.card title="Ajustes">
                        <div class="erp-table-wrap crm-sub-table">
                            <table class="erp-table">
                                <tbody>
                                @foreach ($upload->adjustments as $a)
                                    <tr>
                                        <td><a class="crm-link" href="{{ route('admin.google-conversions.show', $a) }}">#{{ $a->id }}</a></td>
                                        <td>{{ $a->operation_label }}</td>
                                        <td class="is-num">{{ $a->conversion_value !== null ? '$' . number_format((float) $a->conversion_value, 2) : '—' }}</td>
                                        <td><x-erp.chip :tone="$a->status_tone" :label="$a->status_label" /></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-erp.card>
                @endif
            </div>

            <div>
                <x-erp.card title="Detalle">
                    <dl style="margin:0">
                        <div class="crm-attr"><dt>Order ID</dt><dd class="crm-mono">{{ $upload->order_id }}</dd></div>
                        <div class="crm-attr"><dt>Fecha conversión</dt><dd>{{ $upload->conversion_date_time->copy()->setTimezone($upload->timezone)->format('d/m/Y H:i:s P') }}</dd></div>
                        <div class="crm-attr"><dt>Clic</dt><dd>{{ optional($upload->click_at)->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div class="crm-attr"><dt>Vence GCLID</dt><dd>{{ optional($upload->gclid_expires_at)->format('d/m/Y') ?? '—' }}</dd></div>
                        <div class="crm-attr"><dt>Identificador</dt><dd class="crm-mono">{{ $upload->identifier ?: ($upload->user_data ? 'correo/teléfono (SHA-256)' : '—') }}</dd></div>
                        <div class="crm-attr"><dt>Siguiente intento</dt><dd>{{ optional($upload->next_attempt_at)->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div class="crm-attr"><dt>Lead</dt><dd>@if ($upload->lead)<a class="crm-link" href="{{ route('admin.leads-inbox.show', $upload->lead) }}">{{ $upload->lead->display_name }}</a>@else — @endif</dd></div>
                        <div class="crm-attr"><dt>Negocio</dt><dd>@if ($upload->deal)<a class="crm-link" href="{{ route('admin.deals.show', $upload->deal_id) }}">{{ $upload->deal->folio }}</a>@else — @endif</dd></div>
                        @if ($upload->parent)<div class="crm-attr"><dt>Conversión original</dt><dd><a class="crm-link" href="{{ route('admin.google-conversions.show', $upload->parent) }}">#{{ $upload->parent->id }}</a></dd></div>@endif
                    </dl>
                </x-erp.card>
            </div>
        </div>
    </div>
@endsection
