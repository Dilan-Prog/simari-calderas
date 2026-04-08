<div class="admin-main">

    {{-- Cabecera superior --}}
    <header class="admin-top-header">
        <div class="admin-breadcrumb">
            <strong>Panel de Control</strong>
            <span class="bc-sep">/</span>
            <span class="bc-current" id="breadcrumbSection">Usuarios</span>
        </div>
        <div class="admin-header-actions">
            <button class="admin-notif-btn" title="Notificaciones">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                </svg>
                <span class="admin-notif-dot"></span>
            </button>
            <div class="admin-header-avatar">AD</div>
        </div>
    </header>

    {{-- Contenido dinámico --}}
    <main class="admin-content-area" id="mainContent">
        <div class="admin-loading-state" id="loadingState">
            <div class="admin-spinner"></div>
            <span>Cargando...</span>
        </div>
    </main>

</div>
{{-- /ÁREA PRINCIPAL --}}