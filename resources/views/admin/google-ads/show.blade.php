@extends('admin.layouts.master')

@section('title')
    Conversión #{{ $conversion->id }} — Google Ads | Admin
@endsection

@push('styles')
    <style>
        /* ── Status badge extensions (same as index) ── */
        .users-manager-badge.status-stored  { background: #f3f4f6; color: #6b7280; }
        .users-manager-badge.status-pending { background: #fef9c3; color: #854d0e; }
        .users-manager-badge.status-failed  { background: #fee2e2; color: #991b1b; }

        /* ── Detail card ── */
        .ga-detail-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.75rem 2rem;
            margin-top: 1.25rem;
        }
        .ga-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem 2.5rem;
        }
        .ga-detail-field label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.3rem;
        }
        .ga-detail-field p {
            font-size: 0.95rem;
            color: #111827;
            word-break: break-all;
        }
        .ga-detail-field p.mono {
            font-family: monospace;
            font-size: 0.85rem;
            background: #f9fafb;
            padding: 0.35rem 0.6rem;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .ga-error-block {
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
        }
        .ga-error-block label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }
        .ga-error-block p {
            font-size: 0.9rem;
            color: #7f1d1d;
            word-break: break-word;
        }
        .ga-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.875rem;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.4rem 0.9rem;
            background: #fff;
            text-decoration: none;
            transition: background 0.15s;
        }
        .ga-back-btn:hover { background: #f3f4f6; }

        @media (max-width: 640px) {
            .ga-detail-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

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
