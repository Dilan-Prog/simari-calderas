@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/payment-methods.css')
@endpush
@section('title')
    Métodos de Pago - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Métodos de Pago
                    </p>
                    <h1>Métodos de Pago</h1>
                    <p class="breadcrumb-clients-manager main">Administra las formas de pago disponibles para tus
                        clientes</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    @permiso('payment-methods', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewPaymentMethod">
                        + Nuevo método de pago
                    </button>
                    @endpermiso
                </div>
            </header>

            {{-- Table --}}
            <main class="table-container-clients-manager" style="margin-top:20px;">
                <div class="table-scroll">
                    <table class="clients-manager-table">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>TIPO</th>
                                <th>DETALLE</th>
                                <th>ACTIVO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="paymentMethodsTableBody">
                            @forelse ($paymentMethods as $paymentMethod)
                                <tr class="payment-method-row" data-id="{{ $paymentMethod->id }}">
                                    <td>
                                        <p class="pm-name">{{ $paymentMethod->name }}</p>
                                    </td>
                                    <td class="pm-type">
                                        @php
                                            $typeLabels = [
                                                'transferencia' => 'Transferencia bancaria',
                                                'pasarela' => 'Pasarela de pago',
                                                'referencia' => 'Pago por referencia',
                                                'credito' => 'Crédito empresarial',
                                                'digital' => 'Billetera digital',
                                                'otro' => 'Otro',
                                            ];
                                        @endphp
                                        {{ $typeLabels[$paymentMethod->type] ?? $paymentMethod->type }}
                                    </td>
                                    <td class="pm-bank-summary">
                                        @php
                                            $processorLabels = [
                                                'stripe' => 'Stripe',
                                                'conekta' => 'Conekta',
                                                'mercadopago' => 'Mercado Pago',
                                                'openpay' => 'Openpay',
                                                'paypal' => 'PayPal',
                                                'clip' => 'Clip',
                                            ];
                                            $details = $paymentMethod->details ?? [];
                                        @endphp
                                        @switch($paymentMethod->type)
                                            @case('transferencia')
                                                @if ($paymentMethod->bank_name || $paymentMethod->clabe)
                                                    {{ $paymentMethod->bank_name ?? '—' }}
                                                    @if ($paymentMethod->clabe)
                                                        <br><span class="pm-clabe">CLABE: {{ $paymentMethod->clabe }}</span>
                                                    @endif
                                                @elseif ($paymentMethod->account_number)
                                                    Cuenta: {{ $paymentMethod->account_number }}
                                                @else
                                                    —
                                                @endif
                                                @break
                                            @case('pasarela')
                                            @case('digital')
                                                {{ $processorLabels[$details['processor'] ?? null] ?? ($details['processor'] ?? '—') }}
                                                @break
                                            @case('referencia')
                                                {{ $details['convenio'] ?? '—' }}
                                                @break
                                            @case('credito')
                                                @if (!empty($details['plazo_credito']))
                                                    Plazo a {{ $details['plazo_credito'] }} días
                                                @else
                                                    —
                                                @endif
                                                @break
                                            @default
                                                —
                                        @endswitch
                                    </td>
                                    <td>
                                        <span
                                            class="brand-status-badge {{ $paymentMethod->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $paymentMethod->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-container">
                                            @permiso('payment-methods', 'edit')
                                            <button type="button" class="action-btn btn-edit-payment-method"
                                                data-id="{{ $paymentMethod->id }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                            @permiso('payment-methods', 'delete')
                                            <button type="button" class="action-btn btn-delete-payment-method"
                                                data-id="{{ $paymentMethod->id }}" data-name="{{ $paymentMethod->name }}"
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
                                        No hay métodos de pago registrados.
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
@include('admin.payment-methods.partials._scripts')
@include('admin.payment-methods.partials._modal_delete')
@include('admin.payment-methods.partials._modal_form')
