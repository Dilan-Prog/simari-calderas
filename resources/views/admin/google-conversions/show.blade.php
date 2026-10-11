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
            :facts="[
                ['label' => 'Valor', 'value' => $upload->conversion_value !== null ? '$' . number_format((float) $upload->conversion_value, 2) . ' ' . $upload->currency : null],
                ['label' => 'Identificador', 'value' => strtoupper($upload->identifier_type)],
                ['label' => 'Intentos', 'value' => $upload->attempts . '/' . config('google_conversions.max_attempts')],
                ['label' => 'Enviada', 'value' => optional($upload->sent_at)->format('d/m/Y H:i')],
            ]">
            @if ($upload->canRetry())
                <form method="POST" action="{{ route('admin.google-conversions.retry', $upload) }}">@csrf
                    <button class="erp-btn primary" type="submit">Reintentar</button>
                </form>
            @endif
        </x-erp.object-header>

        @if (session('success'))<div class="crm-flash ok">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err">{{ session('error') }}</div>@endif

        <div class="crm-grid">
            <div>
                @if ($upload->last_error)
                    <div class="crm-card">
                        <h2>Último error</h2>
                        <div class="crm-error-box">{{ $upload->last_error }}</div>
                        @if ($upload->alerted_at)<p class="crm-muted">Alerta generada el {{ $upload->alerted_at->format('d/m/Y H:i') }} (tarea de seguimiento).</p>@endif
                    </div>
                @endif

                <div class="crm-card">
                    <h2>Respuesta de Google</h2>
                    @if ($upload->response)
                        <pre class="crm-json">{{ json_encode($upload->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <p class="crm-muted">Sin respuesta registrada.</p>
                    @endif
                </div>

                @if ($upload->adjustments->count())
                    <div class="crm-card">
                        <h2>Ajustes</h2>
                        <table class="crm-table">
                            @foreach ($upload->adjustments as $a)
                                <tr>
                                    <td><a href="{{ route('admin.google-conversions.show', $a) }}">#{{ $a->id }}</a></td>
                                    <td>{{ $a->operation_label }}</td>
                                    <td>{{ $a->conversion_value !== null ? '$' . number_format((float) $a->conversion_value, 2) : '—' }}</td>
                                    <td><span class="erp-chip erp-tone-{{ $a->status_tone }}">{{ $a->status_label }}</span></td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <div class="crm-card">
                    <h2>Detalle</h2>
                    <dl class="crm-dl">
                        <dt>Order ID</dt><dd class="crm-mono">{{ $upload->order_id }}</dd>
                        <dt>Fecha conversión</dt><dd>{{ $upload->conversion_date_time->copy()->setTimezone($upload->timezone)->format('d/m/Y H:i:s P') }}</dd>
                        <dt>Clic</dt><dd>{{ optional($upload->click_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                        <dt>Vence GCLID</dt><dd>{{ optional($upload->gclid_expires_at)->format('d/m/Y') ?? '—' }}</dd>
                        <dt>Identificador</dt><dd class="crm-mono">{{ $upload->identifier ?: ($upload->user_data ? 'correo/teléfono (SHA-256)' : '—') }}</dd>
                        <dt>Siguiente intento</dt><dd>{{ optional($upload->next_attempt_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                        <dt>Lead</dt><dd>@if ($upload->lead)<a href="{{ route('admin.leads-inbox.show', $upload->lead) }}">{{ $upload->lead->display_name }}</a>@else — @endif</dd>
                        <dt>Negocio</dt><dd>@if ($upload->deal)<a href="{{ route('admin.deals.show', $upload->deal_id) }}">{{ $upload->deal->folio }}</a>@else — @endif</dd>
                        @if ($upload->parent)<dt>Conversión original</dt><dd><a href="{{ route('admin.google-conversions.show', $upload->parent) }}">#{{ $upload->parent->id }}</a></dd>@endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
