@extends('admin.layouts.master')

@section('title')
    Conversión #{{ $conversion->id }} — Google Ads | Admin
@endsection


@section('content')
    <div class="container user-manager">
        <section class="users-manager-section">

            {{-- Page header --}}
            <header class="users-manager-main">
                <div>
                    <h1>Detalle de conversión #{{ $conversion->id }}</h1>
                    <p class="breadcrumb-users-manager main">
                        Google Ads — Conversiones
                    </p>
                </div>
                <a href="{{ route('admin.google-ads.index') }}" class="ga-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Volver al listado
                </a>
            </header>

            {{-- Status badge prominent --}}
            <div style="margin-bottom: 0.5rem;">
                @php
                    [$label, $cls] = match ($conversion->status) {
                        'stored'  => ['Almacenado', 'status-stored'],
                        'pending' => ['Pendiente',  'status-pending'],
                        'sent'    => ['Enviado',    'status'],
                        'failed'  => ['Fallido',    'status-failed'],
                        default   => [$conversion->status, ''],
                    };
                @endphp
                <span class="users-manager-badge {{ $cls }}" style="font-size:0.85rem;padding:0.35rem 0.85rem;">
                    Estado: {{ $label }}
                </span>
            </div>

            {{-- Detail card --}}
            <div class="ga-detail-card">
                <div class="ga-detail-grid">

                    <div class="ga-detail-field">
                        <label>ID</label>
                        <p>{{ $conversion->id }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>GCLID</label>
                        <p class="mono">{{ $conversion->gclid }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Nombre de conversión</label>
                        <p>{{ $conversion->conversion_name }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Valor de conversión</label>
                        <p>${{ number_format((float) $conversion->conversion_value, 2) }} {{ $conversion->currency_code }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Código de moneda</label>
                        <p>{{ $conversion->currency_code }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Orden ID</label>
                        <p>{{ $conversion->order_id ?? '—' }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Tiempo de conversión</label>
                        <p>{{ $conversion->conversion_time?->format('d/m/Y H:i:s') ?? '—' }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Enviado en</label>
                        <p>{{ $conversion->sent_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Creado</label>
                        <p>{{ $conversion->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                    <div class="ga-detail-field">
                        <label>Actualizado</label>
                        <p>{{ $conversion->updated_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                </div>

                {{-- Error message block — only visible when status is failed --}}
                @if($conversion->status === 'failed' && $conversion->error_message)
                    <div class="ga-error-block">
                        <label>Mensaje de error</label>
                        <p>{{ $conversion->error_message }}</p>
                    </div>
                @endif
            </div>

        </section>
    </div>
@endsection
