@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@section('title')
    Órdenes de Compra - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div class="header-of-purchase-orders-section">
        <div class="content-of-header-of-purchase-orders-section">
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Órdenes de Compra</strong>
            </p>
            <h1>Órdenes de Compra</h1>
            <p class="breadcrumb-clients-manager">Gestiona las órdenes de compra a proveedores</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'purchase-orders.index',
                'columnDefs' => [
                    'proveedor' => 'Proveedor',
                    'productos' => 'Productos',
                    'total' => 'Total',
                    'estado' => 'Estado',
                    'fecha' => 'Fecha',
                ],
            ])
            <a href="{{ route('admin.purchase-orders.create') }}"
                class="button-primary size-adjustment"
                style="background:#ff6213;border-color:#ff6213;text-decoration:none;
                       display:flex;align-items:center;gap:6px;">
                + Nueva Orden
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        @php
            $statCards = [
                ['icon' => 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0', 'color' => '#ff6213', 'bg' => '#fff3ec', 'value' => $stats['total'],    'label' => 'Total OCs',   'sub' => 'este mes'],
                ['icon' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10', 'color' => '#d97706', 'bg' => '#fffbeb', 'value' => $stats['pending'],  'label' => 'Pendientes',  'sub' => 'en espera'],
                ['icon' => 'M22 11.08V12a10 10 0 1 1-5.93-9.14 M9 11l3 3L22 4', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'value' => $stats['accepted'], 'label' => 'Aceptadas',   'sub' => 'confirmadas'],
                ['icon' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10 M9.5 9l5 5M14.5 9l-5 5', 'color' => '#dc2626', 'bg' => '#fef2f2', 'value' => $stats['rejected'], 'label' => 'Rechazadas',  'sub' => 'canceladas'],
            ];
        @endphp
        @foreach ($statCards as $card)
        <div class="stat-card-of-purchase-orders-section">
            <div style="width:48px;height:48px;background:{{ $card['bg'] }};border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;
                border:1px solid {{ $card['color'] }}30;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="{{ $card['color'] }}" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="{{ $card['icon'] }}"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;color:#111827;">{{ $card['value'] }}</h2>
                <p style="margin:0;font-size:14px;font-weight:600;color:#374151;">{{ $card['label'] }}</p>
                <p style="margin:0;font-size:12px;color:#9ca3af;">{{ $card['sub'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;">
        <div class="filters-clients-manager-search" style="flex:1;">
            <span class="search-icon-clients-manager">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
            </span>
            <input type="text" id="poSearch" placeholder="Buscar por folio o proveedor..."
                value="{{ request('search') }}">
        </div>
        <div class="filters-clients-manager-select">
            <select id="poStatusFilter">
                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Todos los estados</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pendientes</option>
                <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Aceptadas</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazadas</option>
            </select>
        </div>
        <button type="button" id="poDateBtn"
            style="display:flex;align-items:center;gap:6px;padding:8px 16px;
                   border:1.5px solid #d1d5db;background:white;color:#374151;
                   border-radius:8px;font-weight:500;cursor:pointer;font-size:14px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6"/>
                <line x1="8" x2="8" y1="2" y2="6"/>
                <line x1="3" x2="21" y1="10" y2="10"/>
            </svg>
            Fecha
        </button>
        <button type="button" id="poFilterBtn"
            style="display:flex;align-items:center;gap:6px;padding:8px 16px;
                   border:1.5px solid #ff6213;background:white;color:#ff6213;
                   border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            Filtrar
        </button>
    </div>

    {{-- Status tabs --}}
    <div style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;margin-bottom:20px;">
        @php
            $tabs = [
                ['label' => 'Todas',      'value' => 'all',      'count' => $stats['total']],
                ['label' => 'Pendientes', 'value' => 'pending',  'count' => $stats['pending']],
                ['label' => 'Aceptadas',  'value' => 'accepted', 'count' => $stats['accepted']],
                ['label' => 'Rechazadas', 'value' => 'rejected', 'count' => $stats['rejected']],
            ];
            $activeTab = request('status', 'all');
        @endphp
        @foreach ($tabs as $tab)
        <a href="{{ route('admin.purchase-orders.index', array_merge(request()->query(), ['status' => $tab['value']])) }}"
            style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;
                   border-bottom:2px solid {{ $activeTab === $tab['value'] ? '#ff6213' : 'transparent' }};
                   color:{{ $activeTab === $tab['value'] ? '#ff6213' : '#6b7280' }};
                   margin-bottom:-2px;">
            {{ $tab['label'] }} ({{ $tab['count'] }})
        </a>
        @endforeach
    </div>

    {{-- Table --}}
    <main class="table-container-clients-manager">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>FOLIO</th>
                    <th data-col="proveedor">PROVEEDOR</th>
                    <th data-col="productos">PRODUCTOS</th>
                    <th data-col="total">TOTAL</th>
                    <th data-col="estado">ESTADO</th>
                    <th data-col="fecha">FECHA</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @forelse ($orders as $order)
                    @php
                        $statusConfig = match($order->status) {
                            'pending'  => ['label' => 'Pendiente',  'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#d9780640'],
                            'accepted' => ['label' => 'Aceptada',   'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#16a34a40'],
                            'rejected' => ['label' => 'Rechazada',  'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#dc262640'],
                            default    => ['label' => 'Pendiente',  'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#d9780640'],
                        };
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;"
                        data-search="{{ strtolower($order->po_number . ' ' . $order->supplier->company_name) }}"
                        data-status="{{ $order->status }}">
                        <td style="padding:14px 16px;">
                            <p style="margin:0;font-weight:700;color:#ff6213;font-size:14px;">
                                {{ $order->po_number }}
                            </p>
                        </td>
                        <td style="padding:14px 16px;" data-col="proveedor">
                            <p style="margin:0;font-weight:600;font-size:14px;color:#111827;">
                                {{ strtoupper($order->supplier->company_name) }}
                            </p>
                        </td>
                        <td style="padding:14px 16px;" data-col="productos">
                            <p style="margin:0;font-size:13px;color:#6b7280;">
                                {{ $order->items_count }} producto{{ $order->items_count !== 1 ? 's' : '' }}
                            </p>
                        </td>
                        <td style="padding:14px 16px;" data-col="total">
                            <p style="margin:0;font-weight:700;font-size:14px;color:#111827;">
                                ${{ number_format($order->total, 2) }}
                            </p>
                        </td>
                        <td style="padding:14px 16px;" data-col="estado">
                            <span style="display:inline-block;padding:4px 12px;border-radius:20px;
                                font-size:12px;font-weight:600;
                                background:{{ $statusConfig['bg'] }};
                                color:{{ $statusConfig['color'] }};
                                border:1px solid {{ $statusConfig['border'] }};">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td style="padding:14px 16px;" data-col="fecha">
                            <p style="margin:0;font-size:13px;color:#374151;">
                                {{ $order->order_date->format('j M Y') }}
                            </p>
                        </td>
                        <td style="padding:14px 16px;">
                            <div class="header-right-user-manager">
                                {{-- Ver --}}
                                <a href="{{ route('admin.purchase-orders.show', $order->id) }}"
                                    class="table-users-manager-action-btn edit" title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                {{-- Editar --}}
                                <a href="{{ route('admin.purchase-orders.edit', $order->id) }}"
                                    class="table-users-manager-action-btn edit" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </a>
                                {{-- PDF --}}
                                <a href="{{ route('admin.purchase-orders.pdf', $order->id) }}"
                                    class="table-users-manager-action-btn edit" title="Exportar PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                        <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                        <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                    </svg>
                                </a>
                                {{-- Eliminar --}}
                                <button type="button"
                                    class="table-users-manager-action-btn delete btn-delete-po"
                                    data-id="{{ $order->id }}"
                                    data-folio="{{ $order->po_number }}"
                                    data-supplier="{{ $order->supplier->company_name }}"
                                    title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" x2="10" y1="11" y2="17"/>
                                        <line x1="14" x2="14" y1="11" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            No hay órdenes de compra registradas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
                <p style="margin:0;font-size:13px;color:#6b7280;">
                    Mostrando <strong>{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</strong>
                    de <strong>{{ $orders->total() }}</strong> órdenes
                </p>
            </div>
            {{ $orders->links('admin.components.pagination') }}
        @endif
    </main>
</section>

{{-- Delete modal --}}
<div id="deletePoModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <path d="M12 9v4"/><path d="M12 17h.01"/>
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar orden?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. No puedes eliminar órdenes aceptadas.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delPoAvatar">O</div>
            <div>
                <p class="del-confirm-user-name" id="delPoFolio">PO-0000-0000</p>
                <p class="del-confirm-user-email" id="delPoSupplier">Proveedor</p>
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delPoCancel">Cancelar</button>
            <form id="deletePoForm" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="button-primary size-adjustment delete-confirmation-modal-button">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete modal
    const deletePoModal = document.getElementById('deletePoModal');
    const deletePoForm  = document.getElementById('deletePoForm');
    const delPoCancel   = document.getElementById('delPoCancel');
    const deletePoUrl   = '{{ url('/admin/ordenes-compra/eliminar') }}';

    document.querySelectorAll('.btn-delete-po').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delPoFolio').textContent    = btn.dataset.folio;
            document.getElementById('delPoSupplier').textContent = btn.dataset.supplier;
            document.getElementById('delPoAvatar').textContent   = btn.dataset.folio.charAt(0);
            deletePoForm.action = deletePoUrl + '/' + btn.dataset.id;
            deletePoModal.classList.add('active');
        });
    });

    delPoCancel.addEventListener('click', () => deletePoModal.classList.remove('active'));
    deletePoModal.addEventListener('click', (e) => {
        if (e.target === deletePoModal) deletePoModal.classList.remove('active');
    });

    // Search filter
    document.getElementById('poSearch').addEventListener('input', function () {
        const s = this.value.toLowerCase();
        document.querySelectorAll('tbody tr[data-search]').forEach(row => {
            row.style.display = row.dataset.search.includes(s) ? '' : 'none';
        });
    });
</script>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'purchase-orders.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
