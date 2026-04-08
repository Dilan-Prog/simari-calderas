{{--
    partial.blade.php — Vista parcial de Usuarios
    Devuelve solo el contenido (sin layout) para carga dinámica vía fetch desde dashboard.blade.php
--}}
<div class="container user-manager">

    {{-- Sección principal --}}
    <section class="users-manager-section">

        <header class="users-manager-main">
            <div>
                <h1>Gestión de Usuarios</h1>
                <p class="breadcrumb-users-manager main">
                    Administrar roles y permisos del sistema
                </p>
            </div>
            <button class="button-primary size-adjustment" id="openUserModal">
                + Nuevo Usuario
            </button>
        </header>

        <main class="table-container-users-manager">
            <table class="users-manager-table">
                <thead>
                    <tr>
                        <th>USUARIO</th>
                        <th>EMAIL</th>
                        <th>ROL</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-row-user-manager">
                        <td class="user-manager-table-cell">
                            <div class="avatar-user-manager">M</div>
                            <div>
                                <p class="users-manager-name-user">María González</p>
                                <span class="users-manager-date-user">9 feb 2024</span>
                            </div>
                        </td>
                        <td><p class="breadcrumb-users-manager main">maria.gonzalez@simari.com</p></td>
                        <td><span class="users-manager-badge role-admin">Administrador</span></td>
                        <td><span class="users-manager-badge status">Activo</span></td>
                        <td>
                            <div class="header-right-user-manager">
                                <button class="table-users-manager-action-btn edit" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </button>
                                <button class="table-users-manager-action-btn delete" title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
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

                    @for ($i = 0; $i < 12; $i++)
                    <tr class="table-row-user-manager">
                        <td class="user-manager-table-cell">
                            <div class="avatar-user-manager">A</div>
                            <div>
                                <p class="users-manager-name-user">Ana Torres</p>
                                <span class="users-manager-date-user">9 may 2024</span>
                            </div>
                        </td>
                        <td><p class="breadcrumb-users-manager main">ana.torres@simari.com</p></td>
                        <td><span class="users-manager-badge role-employee">Empleado</span></td>
                        <td><span class="users-manager-badge status">Activo</span></td>
                        <td>
                            <div class="header-right-user-manager">
                                <button class="table-users-manager-action-btn edit" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </button>
                                <button class="table-users-manager-action-btn delete" title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
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
                    @endfor
                </tbody>
            </table>
        </main>

    </section>

    {{-- ═══════════════════════════
         MODAL: Crear usuario
    ═══════════════════════════ --}}
    <div id="userModal" class="user-manager-modal">
        <div class="user-manager-modal-content">

            @if ($errors->any())
                <div style="background:#fee2e2;border:1px solid #f87171;border-radius:6px;padding:12px 16px;margin:12px 16px 0;">
                    <ul style="margin:0;padding-left:16px;color:#b91c1c;font-size:0.875rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="user-manager-modal-header">
                <h2>Nuevo Usuario</h2>
                <button class="table-users-manager-action-btn cancel" id="closeUserModal">✕</button>
            </div>

            <div class="user-manager-avatar-upload-container" style="padding:20px 20px 0">
                <div class="user-manager-avatar-upload">
                    <span class="user-manager-avatar-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                    </span>
                </div>
                <button type="button" class="user-manager-avatar-upload-btn">+</button>
            </div>

            <form class="user-manager-modal-body"
                  action="{{ route('admin.users.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <h3>Información Personal</h3>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Nombre*</label>
                        <input class="users-manager-input" type="text" name="first_name"
                               value="{{ old('first_name') }}" placeholder="Ej. Juan">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Apellidos*</label>
                        <input class="users-manager-input" type="text" name="last_name"
                               value="{{ old('last_name') }}" placeholder="Ej. Pérez López">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Fecha de Nacimiento</label>
                        <input class="users-manager-input" type="date" name="birthdate"
                               value="{{ old('birthdate') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">RFC*</label>
                        <input class="users-manager-input" type="text" name="rfc"
                               value="{{ old('rfc') }}" placeholder="XAXX010101000">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">CURP</label>
                        <input class="users-manager-input" type="text" name="curp"
                               value="{{ old('curp') }}" placeholder="XEXX010101HNEXXXA4">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Número de Seguridad Social</label>
                        <input class="users-manager-input" type="text" name="social_segurity_number"
                               value="{{ old('social_segurity_number') }}" placeholder="12345678901">
                    </div>
                </div>

                <h3>Contacto</h3>
                <div class="user-manager-form">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label email">Email*</label>
                        <input class="users-manager-input" type="email" name="email"
                               value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Teléfono*</label>
                        <input class="users-manager-input" type="text" name="phone"
                               value="{{ old('phone') }}" placeholder="(449) 123-4567">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Puesto / Cargo</label>
                        <input class="users-manager-input" type="text" name="position"
                               value="{{ old('position') }}" placeholder="Ej. Técnico de Mantenimiento">
                    </div>
                </div>

                <h3>Contacto de Emergencia</h3>
                <div class="user-manager-form user-manager-form-3">
                    <div>
                        <label class="supliers-manager-slider-label">Nombre del Contacto</label>
                        <input class="users-manager-input" type="text" name="emergency_contact_name"
                               value="{{ old('emergency_contact_name') }}" placeholder="Ej. Juan Pérez">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Teléfono</label>
                        <input class="users-manager-input" type="text" name="emergency_phone"
                               value="{{ old('emergency_phone') }}" placeholder="(449) 123-4567">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Parentesco</label>
                        <input class="users-manager-input" type="text" name="relationship"
                               value="{{ old('relationship') }}" placeholder="Ej. Hermano/a">
                    </div>
                </div>

                <h3>Acceso al Sistema</h3>
                <div class="user-manager-form access-sistem-form">
                    <div>
                        <label class="supliers-manager-slider-label">Rol*</label>
                        <select class="users-manager-select" name="role_id">
                            <option value="">Seleccionar</option>
                            @isset($roles)
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name_role ?? $role->name }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado*</label>
                        <select class="users-manager-select" name="status">
                            <option value="">Seleccionar</option>
                            <option value="active"    {{ old('status') === 'active'    ? 'selected' : '' }}>Activo</option>
                            <option value="inactive"  {{ old('status') === 'inactive'  ? 'selected' : '' }}>Inactivo</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspendido</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Contraseña*</label>
                        <input class="users-manager-input password" type="password" name="password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Confirmar Contraseña*</label>
                        <input class="users-manager-input password" type="password" name="password_confirmation">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                </div>

                {{-- Permisos por módulo --}}
                <h3>Permisos por Módulo</h3>
                <div class="user-manager-permissions-grid">
                    @php
                        $modulos = [
                            ['icon' => 'layout-dashboard', 'label' => 'Dashboard',        'checked' => true],
                            ['icon' => 'building-2',       'label' => 'Clientes',          'checked' => false],
                            ['icon' => 'user-cog',         'label' => 'Proveedores',        'checked' => false],
                            ['icon' => 'shopping-bag',     'label' => 'Productos',          'checked' => false],
                            ['icon' => 'folder-tree',      'label' => 'Categorías',         'checked' => false],
                            ['icon' => 'shopping-cart',    'label' => 'Órdenes',            'checked' => false],
                            ['icon' => 'wrench',           'label' => 'Servicios Técnicos', 'checked' => false],
                            ['icon' => 'archive',          'label' => 'Inventario',         'checked' => false],
                            ['icon' => 'truck',            'label' => 'Envíos',             'checked' => false],
                            ['icon' => 'package',          'label' => 'Paqueterías',        'checked' => false],
                            ['icon' => 'credit-card',      'label' => 'Métodos de Pago',    'checked' => false],
                            ['icon' => 'mail',             'label' => 'Email Marketing',    'checked' => false],
                            ['icon' => 'chart-column',     'label' => 'Analíticas',         'checked' => false],
                            ['icon' => 'activity',         'label' => 'Auditoría Sistema',  'checked' => false],
                        ];
                    @endphp
                    @foreach ($modulos as $mod)
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                                </svg>
                                <span>{{ $mod['label'] }}</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox" {{ $mod['checked'] ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelUserModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit"
                            class="button-primary size-adjustment create-user">Guardar Usuario</button>
                </div>

            </form>
        </div>
    </div>

    <script>
    (function () {
        const modal     = document.getElementById('userModal');
        const openBtn   = document.getElementById('openUserModal');
        const closeBtn  = document.getElementById('closeUserModal');
        const cancelBtn = document.getElementById('cancelUserModal');

        if (!modal) return;

        function openModal()  { modal.style.display = 'flex'; }
        function closeModal() { modal.style.display = 'none'; }

        openBtn  && openBtn.addEventListener('click', openModal);
        closeBtn && closeBtn.addEventListener('click', closeModal);
        cancelBtn && cancelBtn.addEventListener('click', closeModal);

        window.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        @if ($errors->any())
            openModal();
        @endif
    })();
    </script>

</div>
