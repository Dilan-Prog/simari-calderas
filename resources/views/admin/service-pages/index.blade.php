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
            <p class="breadcrumb-clients-manager main">Jerarquía de hasta 3 niveles: Hub &rsaquo; Categoría &rsaquo; Servicio</p>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'service-pages.index',
                'columnDefs' => ['tipo' => 'Tipo', 'precio' => 'Precio', 'orden' => 'Orden', 'estado' => 'Estado'],
            ])
            <form method="POST" action="{{ route('admin.service-pages.quick-create') }}" style="display:inline;">
                @csrf
                <button type="submit" class="button-primary size-adjustment"
                    style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
                    + Nuevo Servicio
                </button>
            </form>
        </div>
    </div>

    {{-- Filters --}}
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
        <div class="filters-clients-manager-search" style="flex:1;min-width:220px;">
            <span class="search-icon-clients-manager">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
            </span>
            <input type="text" id="servicePageSearch" placeholder="Buscar por nombre o slug...">
        </div>
        <div class="filters-clients-manager-select">
            <select id="servicePageLevelFilter">
                <option value="all">Todos los niveles</option>
                <option value="hub">Hub</option>
                <option value="category">Categorías</option>
                <option value="service">Servicios</option>
            </select>
        </div>
        <div class="filters-clients-manager-select">
            <select id="servicePageStatusFilter">
                <option value="all">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>
        <button type="button" id="btnServicePageFilter"
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

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>SERVICIO</th>
                    <th data-col="tipo">TIPO</th>
                    <th data-col="precio">PRECIO</th>
                    <th data-col="orden">ORDEN</th>
                    <th data-col="estado">ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table" id="servicePagesTable">
                <tbody>
                @php
                    // Árbol renderizado con PHP crudo (echo) -- mismo patrón que
                    // admin/categories/index.blade.php::renderCategoryRow(), ya
                    // que Blade no reprocesa directivas (@permiso, etc.) dentro de
                    // un bloque de PHP crudo; se llama auth() directamente.
                    function renderServicePageRow($page, $level = 1) {
                        $indent = ($level - 1) * 28;
                        $typeMeta = match ($page->page_type) {
                            'hub'      => ['label' => 'Hub', 'color' => '#ff6213', 'bg' => '#fff3ec'],
                            'category' => ['label' => 'Categoría', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                            default    => ['label' => 'Servicio', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                        };
                        $statusClass = $page->is_active ? 'status' : 'status-inactive';
                        $statusLabel = $page->is_active ? 'Activo' : 'Inactivo';
                        $hasChildren = $page->children->count() > 0;
                        $icon = $page->page_type === 'service' ? 'dot' : 'folder';
                        $dataStatus = $page->is_active ? 'active' : 'inactive';
                        $dataName = strtolower($page->name . ' ' . $page->slug);
                        $childId = 'children-' . $page->id;

                        echo '<tr class="svcpage-row"
                            data-level="'.$level.'"
                            data-page-type="'.$page->page_type.'"
                            data-status="'.$dataStatus.'"
                            data-name="'.e($dataName).'"
                            style="border-bottom:1px solid #f1f5f9;">';
                        echo '<td style="padding:12px 16px;padding-left:'.($indent + 16).'px;">';
                        echo '<div style="display:flex;align-items:center;gap:8px;">';
                        if ($level > 1) {
                            echo '<span style="color:#cbd5e1;font-size:12px;">—</span>';
                        }
                        if ($icon === 'folder') {
                            $toggle = $hasChildren ? 'data-toggle="'.$childId.'"' : '';
                            echo '<span '.$toggle.' style="cursor:'.($hasChildren ? 'pointer' : 'default').';color:'.$typeMeta['color'].';">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
                            echo '</span>';
                        } else {
                            echo '<span style="width:10px;height:10px;border-radius:50%;background:'.$typeMeta['color'].';display:inline-block;flex-shrink:0;"></span>';
                        }
                        echo '<div style="display:flex;flex-direction:column;">';
                        echo '<span style="font-weight:'.($level === 1 ? '700' : '500').';font-size:14px;">'.e($page->name).'</span>';
                        echo '<span style="font-size:12px;color:#6b7280;">'.e($page->publicPath()).'</span>';
                        echo '</div></div></td>';
                        echo '<td data-col="tipo" style="padding:12px 16px;">';
                        echo '<span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:'.$typeMeta['bg'].';color:'.$typeMeta['color'].';border:1px solid '.$typeMeta['color'].'40;">'.$typeMeta['label'].'</span>';
                        echo '</td>';
                        echo '<td data-col="precio" style="padding:12px 16px;">';
                        echo $page->price ? '$' . number_format($page->price, 2) . ' ' . e($page->currency) : '—';
                        echo '</td>';
                        echo '<td data-col="orden" style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">'.$page->sort_order.'</td>';
                        echo '<td data-col="estado" style="padding:12px 16px;">';
                        echo '<span class="users-manager-badge '.$statusClass.'">'.$statusLabel.'</span>';
                        echo '</td>';
                        echo '<td style="padding:12px 16px;">';
                        echo '<div class="header-right-user-manager">';
                        echo '<a href="'.e(url($page->publicPath())).'" target="_blank" class="table-users-manager-action-btn" title="Ver página pública">';
                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
                        echo '</a>';
                        $user = auth()->user();
                        if ($user && $user->hasPermission('service-pages', 'edit')) {
                            echo '<a href="'.route('admin.service-pages.live-editor', $page).'" class="table-users-manager-action-btn edit" title="Editar en el editor en vivo">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>';
                            echo '</a>';
                        }
                        if ($user && $user->hasPermission('service-pages', 'delete')) {
                            echo '<button type="button" class="table-users-manager-action-btn delete btn-delete-service-page" data-id="'.$page->id.'" data-name="'.e($page->name).'">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>';
                            echo '</button>';
                        }
                        echo '</div></td>';
                        echo '</tr>';
                        if ($hasChildren) {
                            foreach ($page->children->sortBy('sort_order') as $child) {
                                renderServicePageRow($child, $level + 1);
                            }
                        }
                    }
                @endphp

                @forelse ($allForTree as $rootPage)
                    @php renderServicePageRow($rootPage, 1); @endphp
                @empty
                    <tr>
                        <td colspan="6" style="padding:32px 16px;text-align:center;color:#6b7280;">
                            No hay páginas de servicio creadas todavía.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
        <p class="breadcrumb-clients-manager">
            <strong>{{ $total }}</strong> {{ \Illuminate\Support\Str::plural('página', $total) }} de servicio en total
        </p>
    </div>

    {{-- Legend --}}
    <div style="margin-top:16px;display:flex;align-items:center;gap:12px;font-size:13px;color:#6b7280;flex-wrap:wrap;">
        <strong>Leyenda:</strong>
        <span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:#fff3ec;color:#ff6213;border:1px solid #ff621340;">Hub</span>
        <span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:#eff6ff;color:#3b82f6;border:1px solid #3b82f640;">Categoría</span>
        <span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:#f5f3ff;color:#8b5cf6;border:1px solid #8b5cf640;">Servicio</span>
        <span style="color:#6b7280;">&middot; Haz click en 📁 para expandir/colapsar</span>
    </div>

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

    // Búsqueda y filtros -- 100% client-side sobre las filas ya renderizadas,
    // mismo patrón que admin/categories/partials/_scripts.blade.php.
    const servicePageSearch = document.getElementById('servicePageSearch');
    const servicePageLevelFilter = document.getElementById('servicePageLevelFilter');
    const servicePageStatusFilter = document.getElementById('servicePageStatusFilter');
    const btnServicePageFilter = document.getElementById('btnServicePageFilter');

    const filterServicePages = () => {
        const search = servicePageSearch.value.toLowerCase().trim();
        const type = servicePageLevelFilter.value;
        const status = servicePageStatusFilter.value;

        document.querySelectorAll('.svcpage-row').forEach(row => {
            const name = row.dataset.name ?? '';
            const rowType = row.dataset.pageType ?? '';
            const rowStatus = row.dataset.status ?? '';

            const matchSearch = !search || name.includes(search);
            const matchType = type === 'all' || rowType === type;
            const matchStatus = status === 'all' || rowStatus === status;

            row.style.display = matchSearch && matchType && matchStatus ? '' : 'none';
        });
    };

    servicePageSearch.addEventListener('input', filterServicePages);
    servicePageLevelFilter.addEventListener('change', filterServicePages);
    servicePageStatusFilter.addEventListener('change', filterServicePages);
    btnServicePageFilter.addEventListener('click', filterServicePages);

    document.querySelectorAll('#servicePagesTable [data-toggle]').forEach(function (el) {
        el.addEventListener('click', function () {
            const parentRow = this.closest('tr');
            const allRows = Array.from(document.querySelectorAll('#servicePagesTable tbody tr'));
            const parentIndex = allRows.indexOf(parentRow);
            const parentLevel = parseInt(parentRow.dataset.level);

            let i = parentIndex + 1;
            while (i < allRows.length) {
                const rowLevel = parseInt(allRows[i].dataset.level);
                if (rowLevel <= parentLevel) break;
                const current = allRows[i];
                const isHidden = current.style.display === 'none';
                current.style.display = isHidden ? '' : 'none';
                i++;
            }
        });
    });
</script>
<script src="{{ asset('js/admin/column-visibility.js') }}"></script>
<script>
    initColumnVisibility({
        tableKey: 'service-pages.index',
        savedColumns: @json($visibleColumns),
        saveUrl: '{{ route('admin.column-preferences.update') }}',
    });
</script>
@endpush
@include('admin.components.center-toast')
</div>
@endsection
