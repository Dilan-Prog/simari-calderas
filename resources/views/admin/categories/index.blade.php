@extends('admin.layouts.master')
@section('title')
    Categorías de Productos - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Categorías</strong>
            </p>
            <h1 style="margin:0 0 4px;">Gestión de Categorías</h1>
            <p class="breadcrumb-clients-manager main">Jerarquía de 3 niveles para el catálogo de productos</p>
        </div>
        <button type="button" class="button-primary size-adjustment" id="btnNewCategory"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            + Nueva Categoría
        </button>
    </div>

    {{-- Stats cards --}}
    @php
        $total      = $categories->total();
        $allCats    = \App\Models\Category::withCount('products')->get();
        $activas    = $allCats->where('is_active', true)->count();
        $inactivas  = $allCats->where('is_active', false)->count();
        $principales = $allCats->whereNull('parent_id')->count();
        $subs       = $allCats->whereNotNull('parent_id')->where('level', 2)->count();
        $hijas      = $allCats->where('level', 3)->count();
        $niveles    = collect([$principales, $subs, $hijas])->filter()->count();
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#ff6213;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                    <path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                    <path d="M3 5a2 2 0 0 0 2 2h3"/><path d="M3 3v13a2 2 0 0 0 2 2h3"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $total }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Total categorías</p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#16a34a;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="m9 11 3 3L22 4"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $activas }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Activas</p>
                <p class="breadcrumb-clients-manager" style="margin:0;font-size:11px;color:#6b7280;">
                    {{ $principales }} principales · {{ $subs }} sub · {{ $hijas }} hija
                </p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#6b7280;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $inactivas }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Inactivas</p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#3b82f6;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" x2="18" y1="20" y2="10"/>
                    <line x1="12" x2="12" y1="20" y2="4"/>
                    <line x1="6" x2="6" y1="20" y2="14"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">3/3</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Niveles configurados</p>
            </div>
        </div>
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
            <input type="text" id="categorySearch" placeholder="Buscar por nombre o slug...">
        </div>
        <div class="filters-clients-manager-select">
            <select id="categoryLevelFilter">
                <option value="all">Todos los niveles</option>
                <option value="1">Categorías Principales</option>
                <option value="2">Subcategorías</option>
                <option value="3">Categorías Hijas</option>
            </select>
        </div>
        <div class="filters-clients-manager-select">
            <select id="categoryStatusFilter">
                <option value="all">Todos los estados</option>
                <option value="active">Activas</option>
                <option value="inactive">Inactivas</option>
            </select>
        </div>
        <button type="button" id="btnFilter"
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
                    <th class="">CATEGORÍA</th>
                    <th>NIVEL</th>
                    <th>SLUG</th>
                    <th>DESCRIPCIÓN</th>
                    <th>ORDEN</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table" id="categoriesTable">
                <tbody>
                @php
                    // Build tree — only show root categories, children rendered inline
                    $allForTree = \App\Models\Category::with(['children.children'])
                        ->whereNull('parent_id')
                        ->orderBy('sort_order')->orderBy('name')
                        ->get();

                    function renderCategoryRow($cat, $level = 1) {
                        $indent     = ($level - 1) * 28;
                        $levelLabel = match($level) { 1 => 'Principal', 2 => 'Subcategoría', default => 'Categoría hija' };
                        $levelColor = match($level) { 1 => '#ff6213', 2 => '#3b82f6', default => '#8b5cf6' };
                        $levelBg    = match($level) { 1 => '#fff3ec', 2 => '#eff6ff', default => '#f5f3ff' };
                        $statusClass = $cat->is_active ? 'status' : 'status-inactive';
                        $statusLabel = $cat->is_active ? 'Activa' : 'Inactiva';
                        $hasChildren = $level < 3 && $cat->children->count() > 0;
                        $icon = $level === 3 ? 'dot' : 'folder';
                        $iconColor = match($level) { 1 => '#ff6213', 2 => '#ff6213', default => '#8b5cf6' };
                        $dataLevel = $level;
                        $dataStatus = $cat->is_active ? 'active' : 'inactive';
                        $dataName = strtolower($cat->name);
                        $childId = 'children-' . $cat->id;
                        echo '<tr class="cat-row"
                            data-level="'.$dataLevel.'"
                            data-status="'.$dataStatus.'"
                            data-name="'.$dataName.'"
                            style="border-bottom:1px solid #f1f5f9;">';
                        echo '<td style="padding:12px 16px;padding-left:'.($indent + 16).'px;">';
                        echo '<div style="display:flex;align-items:center;gap:8px;">';
                        if ($level > 1) {
                            echo '<span style="color:#cbd5e1;font-size:12px;">—</span>';
                        }
                        if ($icon === 'folder') {
                            $toggle = $hasChildren ? 'data-toggle="'.$childId.'"' : '';
                            echo '<span '.$toggle.' style="cursor:'.($hasChildren ? 'pointer' : 'default').';color:'.$iconColor.';">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
                            echo '</span>';
                        } else {
                            echo '<span style="width:10px;height:10px;border-radius:50%;background:'.$iconColor.';display:inline-block;flex-shrink:0;"></span>';
                        }
                        echo '<span style="font-weight:'.($level === 1 ? '700' : '500').';font-size:14px;">'.e($cat->name).'</span>';
                        echo '</div></td>';
                        echo '<td style="padding:12px 16px;">';
                        echo '<span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:'.$levelBg.';color:'.$levelColor.';border:1px solid '.$levelColor.'40;">'.$levelLabel.'</span>';
                        echo '</td>';
                        echo '<td style="padding:12px 16px;">';
                        echo '<span style="display:inline-block;padding:3px 10px;background:#f1f5f9;border-radius:6px;font-size:12px;font-family:monospace;color:#475569;">'.e($cat->slug).'</span>';
                        echo '</td>';
                        echo '<td style="padding:12px 16px;max-width:260px;">';
                        echo '<p style="margin:0;font-size:13px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'.e(\Illuminate\Support\Str::limit($cat->description ?? '—', 55)).'</p>';
                        echo '</td>';
                        echo '<td style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">'.$cat->sort_order.'</td>';
                        echo '<td style="padding:12px 16px;">';
                        echo '<span class="users-manager-badge '.$statusClass.'" data-status="'.$dataStatus.'">'.$statusLabel.'</span>';
                        echo '</td>';
                        echo '<td style="padding:12px 16px;">';
                        echo '<div class="header-right-user-manager">';
                        echo '<button type="button" class="table-users-manager-action-btn edit btn-edit-category" data-id="'.$cat->id.'">';
                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>';
                        echo '</button>';
                        echo '<button type="button" class="table-users-manager-action-btn delete btn-delete-category" data-id="'.$cat->id.'" data-name="'.e($cat->name).'" data-slug="'.e($cat->slug).'">';
                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>';
                        echo '</button>';
                        echo '</div></td>';
                        echo '</tr>';
                        if ($hasChildren) {
                            foreach ($cat->children->sortBy('sort_order') as $child) {
                                renderCategoryRow($child, $level + 1);
                            }
                        }
                    }
                @endphp

                @foreach ($allForTree as $rootCat)
                    @php renderCategoryRow($rootCat, 1); @endphp
                @endforeach

                </tbody>
            </table>
        </div>
    </main>

    {{-- Pagination --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
        <p class="breadcrumb-clients-manager">
            Mostrando <strong>{{ $categories->firstItem() }}–{{ $categories->lastItem() }}</strong>
            de <strong>{{ $categories->total() }}</strong> categorías
        </p>
        <div style="display:flex;gap:4px;align-items:center;">
            <a href="{{ $categories->url(1) }}"
                class="table-users-manager-action-btn edit {{ $categories->onFirstPage() ? 'disabled' : '' }}"
                style="font-size:12px;padding:6px 10px;">«</a>
            <a href="{{ $categories->previousPageUrl() ?? '#' }}"
                class="table-users-manager-action-btn edit {{ $categories->onFirstPage() ? 'disabled' : '' }}"
                style="font-size:12px;padding:6px 10px;">‹</a>
            @foreach ($categories->getUrlRange(
                max(1, $categories->currentPage() - 2),
                min($categories->lastPage(), $categories->currentPage() + 2)
            ) as $page => $url)
                <a href="{{ $url }}"
                    class="{{ $page == $categories->currentPage() ? 'button-primary' : 'table-users-manager-action-btn edit' }}"
                    style="font-size:12px;padding:6px 10px;min-width:32px;text-align:center;">
                    {{ $page }}
                </a>
            @endforeach
            <a href="{{ $categories->nextPageUrl() ?? '#' }}"
                class="table-users-manager-action-btn edit {{ !$categories->hasMorePages() ? 'disabled' : '' }}"
                style="font-size:12px;padding:6px 10px;">›</a>
            <a href="{{ $categories->url($categories->lastPage()) }}"
                class="table-users-manager-action-btn edit {{ !$categories->hasMorePages() ? 'disabled' : '' }}"
                style="font-size:12px;padding:6px 10px;">»</a>
        </div>
    </div>

    {{-- Legend --}}
    <div style="margin-top:16px;display:flex;align-items:center;gap:12px;font-size:13px;color:#6b7280;">
        <strong>Leyenda:</strong>
        <span class="principal-categorie-from-table">Principal</span>
        <span class="subcategorie-categorie-from-table">Subcategoría</span>
        <span class="children-categorie-from-table">Categoría hija</span>
        <span style="color:#6b7280;">· Haz click en 📁 para expandir/colapsar</span>
    </div>

</section>

@include('admin.categories.partials._create_edit_modal')
@include('admin.categories.partials._delete_modal')
@include('admin.categories.partials._scripts')
</div>
@endsection
