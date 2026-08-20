@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/shipping-rules.css')
@endpush
@section('title')
    Reglas de Envío - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Reglas de Envío
                    </p>
                    <h1>Reglas de Envío</h1>
                    <p class="breadcrumb-clients-manager main">Define costos de envío y umbrales de envío gratis por
                        marca o categoría</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    @permiso('shipping-rules', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewShippingRule">
                        + Nueva regla
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
                                <th>APLICA A</th>
                                <th>COSTO DE ENVÍO</th>
                                <th>ENVÍO GRATIS DESDE</th>
                                <th>ACTIVA</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="shippingRulesTableBody">
                            @forelse ($shippingRules as $shippingRule)
                                <tr class="shipping-rule-row" data-id="{{ $shippingRule->id }}">
                                    <td>
                                        @if ($shippingRule->brand_id)
                                            <span class="sr-scope-type">Marca</span>
                                            <p class="sr-scope-label">{{ $shippingRule->brand->name ?? '—' }}</p>
                                        @else
                                            <span class="sr-scope-type">Categoría</span>
                                            <p class="sr-scope-label">{{ $shippingRule->category->name ?? '—' }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $shippingRule->shipping_cost !== null ? '$' . number_format((float) $shippingRule->shipping_cost, 2) : '—' }}
                                    </td>
                                    <td>
                                        {{ $shippingRule->free_shipping_threshold !== null ? '$' . number_format((float) $shippingRule->free_shipping_threshold, 2) : '—' }}
                                    </td>
                                    <td>
                                        <span
                                            class="brand-status-badge {{ $shippingRule->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $shippingRule->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-container">
                                            @permiso('shipping-rules', 'edit')
                                            <button type="button" class="action-btn btn-edit-shipping-rule"
                                                data-id="{{ $shippingRule->id }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                            @permiso('shipping-rules', 'delete')
                                            <button type="button" class="action-btn btn-delete-shipping-rule"
                                                data-id="{{ $shippingRule->id }}"
                                                data-name="{{ $shippingRule->brand->name ?? $shippingRule->category->name ?? 'esta regla' }}"
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
                                        Aún no hay reglas de envío configuradas.
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
@include('admin.shipping-rules.partials._scripts')
@include('admin.shipping-rules.partials._modal_delete')
@include('admin.shipping-rules.partials._modal_form')
