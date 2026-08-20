@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/payment-methods.css')
@endpush
@section('title')
    Cuentas de WhatsApp - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; WhatsApp &gt; Cuentas
                    </p>
                    <h1>Cuentas de WhatsApp</h1>
                    <p class="breadcrumb-clients-manager main">Números de WhatsApp Business conectados vía Meta Cloud
                        API</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    @permiso('whatsapp', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewWhatsappAccount">
                        + Nueva cuenta
                    </button>
                    @endpermiso
                </div>
            </header>

            {{-- Aviso de seguridad --}}
            <p class="ap-readonly-note" style="margin-top:8px;">
                El token de acceso se guarda cifrado. Por seguridad, el valor guardado nunca se muestra de nuevo —
                solo puedes reemplazarlo al editar.
            </p>

            {{-- Datos del webhook: se necesitan al configurar el número en Meta for
                 Developers > WhatsApp > Configuration. La URL se calcula con route(),
                 así que siempre refleja el dominio real donde corre la app (hoy local,
                 el real cuando el sitio esté publicado en Hostinger). El "Token de
                 verificación" que Meta pide es el mismo que cada cuenta guarda en el
                 campo "Token de verificación del webhook" del formulario. --}}
            <div class="ap-info-card" style="margin-top:14px; padding:14px 16px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb;">
                <p style="margin:0 0 8px; font-weight:600; font-size:13px; color:#141516;">
                    Configuración del webhook (Meta for Developers → WhatsApp → Configuration)
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:20px;">
                    <div style="min-width:260px;">
                        <p style="margin:0 0 4px; font-size:12px; color:#6b7280;">Callback URL</p>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <code id="waWebhookUrlDisplay" style="font-size:13px; background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:4px 8px;">{{ $webhookUrl }}</code>
                            <button type="button" class="action-btn btn-copy-webhook-value" data-value="{{ $webhookUrl }}"
                                title="Copiar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="min-width:220px;">
                        <p style="margin:0 0 4px; font-size:12px; color:#6b7280;">Verify Token</p>
                        <p style="margin:0; font-size:13px; color:#141516;">
                            El "Token de verificación del webhook" que guardaste en cada cuenta (abajo, en su columna Estado/Acciones — edítala para verlo).
                        </p>
                    </div>
                </div>
                @if (Str::startsWith($webhookUrl, 'http://localhost') || Str::contains($webhookUrl, '.test'))
                    <p style="margin:8px 0 0; font-size:12px; color:#b45309;">
                        Esta URL apunta a tu entorno local — Meta no puede alcanzarla. Configura el webhook hasta que el sitio esté publicado en un dominio real (Hostinger).
                    </p>
                @endif
            </div>

            {{-- Table --}}
            <main class="table-container-clients-manager" style="margin-top:20px;">
                <div class="table-scroll">
                    <table class="clients-manager-table">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>NÚMERO</th>
                                <th>PHONE NUMBER ID</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="whatsappAccountsTableBody">
                            @forelse ($whatsappAccounts as $account)
                                <tr class="whatsapp-account-row" data-id="{{ $account->id }}">
                                    <td>
                                        <p class="pm-name">{{ $account->name }}</p>
                                    </td>
                                    <td class="pm-type">
                                        {{ $account->phone_number }}
                                    </td>
                                    <td class="pm-type">
                                        {{ $account->phone_number_id ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($account->is_active)
                                            <span class="status-badge status-active">Activa</span>
                                        @else
                                            <span class="status-badge status-inactive">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="actions-container">
                                            @permiso('whatsapp', 'edit')
                                            <button type="button" class="action-btn btn-edit-whatsapp-account"
                                                data-id="{{ $account->id }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                            @permiso('whatsapp', 'delete')
                                            <button type="button" class="action-btn btn-delete-whatsapp-account"
                                                data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                                                title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                    <line x1="10" x2="10" y1="11" y2="17" />
                                                    <line x1="14" x2="14" y1="11" y2="17" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                        No hay cuentas de WhatsApp registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>
        </section>
    </div>
@endsection
@include('admin.whatsapp-accounts.partials._modal_form')
@include('admin.whatsapp-accounts.partials._modal_delete')
@include('admin.whatsapp-accounts.partials._scripts')
