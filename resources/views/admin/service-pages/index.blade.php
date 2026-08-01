@extends('admin.layouts.master')
@section('title')
    Páginas de Servicio - Admin
@endsection

@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Páginas de Servicio</strong>
            </p>
            <h1 style="margin:0 0 4px;">Páginas de Servicio</h1>
            <p class="breadcrumb-clients-manager main">Catálogo de servicios con su propia página pública (SEO, secciones)</p>
        </div>
        <a href="{{ route('admin.service-pages.create') }}" class="button-primary size-adjustment"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            + Nuevo Servicio
        </a>
    </div>

    <form method="GET" style="margin-bottom:16px;max-width:360px;">
        <input type="text" name="q" class="users-manager-input" placeholder="Buscar por nombre..." value="{{ request('q') }}">
    </form>

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th>PRECIO</th>
                    <th>ORDEN</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @forelse ($servicePages as $servicePage)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 16px;">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-weight:700;font-size:14px;">{{ $servicePage->name }}</span>
                                <span style="font-size:12px;color:#6b7280;">/servicio/{{ $servicePage->slug }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            {{ $servicePage->price ? '$' . number_format($servicePage->price, 2) . ' ' . $servicePage->currency : '—' }}
                        </td>
                        <td style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">
                            {{ $servicePage->sort_order }}
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="users-manager-badge {{ $servicePage->is_active ? 'status' : 'status-inactive' }}">
                                {{ $servicePage->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="header-right-user-manager">
                                <a href="{{ route('service-page.show', $servicePage->slug) }}" target="_blank"
                                    class="table-users-manager-action-btn" title="Ver página pública">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('admin.service-pages.edit', $servicePage) }}"
                                    class="table-users-manager-action-btn edit" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </a>
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-service-page"
                                    data-id="{{ $servicePage->id }}" data-name="{{ $servicePage->name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:32px 16px;text-align:center;color:#6b7280;">
                            No hay páginas de servicio creadas todavía.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

    {{ $servicePages->links('admin.components.pagination') }}

</section>

{{-- Delete confirm --}}
<div id="deleteServicePageModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <path d="M12 9v4"/><path d="M12 17h.01"/>
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar servicio?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. Se eliminarán también sus secciones.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delServicePageAvatar">S</div>
            <div><p class="del-confirm-user-name" id="delServicePageName">Nombre</p></div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delServicePageCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button" id="delServicePageConfirm">Eliminar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const deleteServicePageModal = document.getElementById('deleteServicePageModal');
    let deleteServicePageId = null;

    document.querySelectorAll('.btn-delete-service-page').forEach(function (btn) {
        btn.addEventListener('click', function () {
            deleteServicePageId = btn.dataset.id;
            document.getElementById('delServicePageName').textContent = btn.dataset.name;
            document.getElementById('delServicePageAvatar').textContent = btn.dataset.name.charAt(0).toUpperCase();
            deleteServicePageModal.classList.add('active');
        });
    });

    document.getElementById('delServicePageCancel').addEventListener('click', () => deleteServicePageModal.classList.remove('active'));
    deleteServicePageModal.addEventListener('click', (e) => {
        if (e.target === deleteServicePageModal) deleteServicePageModal.classList.remove('active');
    });

    document.getElementById('delServicePageConfirm').addEventListener('click', async function () {
        try {
            const response = await fetch(`{{ url('/admin/servicios-web') }}/${deleteServicePageId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _method: 'DELETE' }),
            });
            if (response.ok || response.redirected) {
                window.location.reload();
            } else {
                showCenterToast('No se pudo eliminar el servicio.', 'error');
            }
        } catch (err) {
            showCenterToast('Error de conexión al eliminar.', 'error');
        }
    });
</script>
@endpush
@include('admin.components.center-toast')
</div>
@endsection
