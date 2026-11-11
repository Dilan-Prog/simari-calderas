{{-- Ventas > Bandeja de leads, dividida en dos bandejas:
       · Inbox de entrada: WhatsApp, llamadas, formularios, manuales y las integraciones futuras.
       · Inbox de correo: los clientes que escriben por correo.
     Ambas son la misma pantalla (admin.leads-inbox.index) con ?inbox=entrada|correo y comparten el permiso
     'leads-inbox'. Recibe $erpNavByPerm del sidebar; se oculta si no hay permiso. --}}
@php $erpItem = $erpNavByPerm['leads-inbox'] ?? null; @endphp
@if ($erpItem)
    @php
        $onInbox = request()->routeIs('admin.leads-inbox.*');
        // El controlador deja la bandeja activa en la petición; en la ficha de un lead se deduce de su origen.
        $curInbox = request()->attributes->get('leads_inbox');
        if (! $curInbox && $onInbox) {
            $routeLead = request()->route('lead');
            $curInbox = $routeLead instanceof \App\Models\Lead ? \App\Models\Lead::inboxOf($routeLead->source) : 'entrada';
        }
        $subItems = [
            'entrada' => [
                'label' => 'Inbox de entrada',
                'icon'  => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            ],
            'correo' => [
                'label' => 'Inbox de correo',
                'icon'  => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            ],
        ];
    @endphp
    <div class="sidebar-subgroup {{ $onInbox ? 'is-open' : '' }}" data-subgroup>
        <button type="button" class="sidebar-nav-item sidebar-subgroup-head {{ $onInbox ? 'has-active' : '' }}" data-subgroup-toggle
            aria-expanded="{{ $onInbox ? 'true' : 'false' }}" data-label="{{ $erpItem['label'] }}">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $erpItem['icon'] !!}</svg>
                <span class="sidebar-nav-item-label">{{ $erpItem['label'] }}</span>
            </div>
            <svg class="sidebar-subgroup-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
        </button>
        <div class="sidebar-subgroup-body">
            @foreach ($subItems as $key => $sub)
                <a class="sidebar-nav-item sidebar-nav-sub {{ $onInbox && $curInbox === $key ? 'active' : '' }}"
                    href="{{ route('admin.leads-inbox.index', ['inbox' => $key]) }}"
                    data-section="leads-inbox-{{ $key }}" data-label="{{ $sub['label'] }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sub['icon'] !!}</svg>
                        <span class="sidebar-nav-item-label">{{ $sub['label'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <style>
        .sidebar-subgroup { display: flex; flex-direction: column; }
        .sidebar-subgroup-head.has-active { color: #ffffff; }
        .sidebar-subgroup-chevron { flex-shrink: 0; transition: transform .2s ease; }
        .sidebar-subgroup.is-open .sidebar-subgroup-chevron { transform: rotate(90deg); }
        .sidebar-subgroup-body { display: none; flex-direction: column; gap: 2px; padding-left: 16px; margin-top: 2px; }
        .sidebar-subgroup.is-open .sidebar-subgroup-body { display: flex; }
        /* Mismo renglón separador que el resto de los módulos del grupo (el último hijo no lo llevaba). */
        .sidebar-nav-group-body .sidebar-subgroup .sidebar-nav-item { border-bottom: 1px solid #374151; }
        /* Menú plegado (solo íconos): se aplana, sin el encabezado, igual que los grupos. */
        .admin-sidebar.collapsed .sidebar-subgroup-head { display: none; }
        .admin-sidebar.collapsed .sidebar-subgroup-body { display: flex !important; padding-left: 0; margin-top: 0; }
        .admin-sidebar.collapsed .sidebar-nav-group-body .sidebar-subgroup .sidebar-nav-item { border-bottom: none; }
    </style>
    <script>
        (function () {
            document.querySelectorAll('[data-subgroup-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var box = btn.closest('[data-subgroup]');
                    var open = box.classList.toggle('is-open');
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            });
        })();
    </script>
@endif
