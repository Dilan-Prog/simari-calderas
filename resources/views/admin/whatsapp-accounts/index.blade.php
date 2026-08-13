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
