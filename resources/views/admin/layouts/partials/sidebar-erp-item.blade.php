{{-- Enlace de un módulo nuevo del ERP (config/erp_nav.php) dentro de su grupo del sidebar.
     Recibe $perm; $erpNavByPerm viene del sidebar. Se oculta si la ruta no existe o no hay permiso. --}}
@php $erpItem = $erpNavByPerm[$perm] ?? null; @endphp
@if ($erpItem)
    <a class="sidebar-nav-item {{ request()->routeIs(\Illuminate\Support\Str::beforeLast($erpItem['route'], '.') . '.*') ? 'active' : '' }}" data-section="operaciones-modulo"
        href="{{ $erpItem['url'] }}" data-label="{{ $erpItem['label'] }}">
        <div class="sidebar-nav-item-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $erpItem['icon'] !!}</svg>
            <span class="sidebar-nav-item-label">{{ $erpItem['label'] }}</span>
        </div>
    </a>
@endif
