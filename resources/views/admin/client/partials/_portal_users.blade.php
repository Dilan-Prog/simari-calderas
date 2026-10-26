{{-- Usuarios del portal: equipo que el titular dio de alta en /customer. Var: $customer --}}
@php
    $puMembers = $customer->portalUsers()->with('role')->orderBy('first_name')->orderBy('last_name')->get();
    $puRoles = $customer->portalRoles()->withCount('users')->orderByDesc('is_system')->orderBy('name')->get();
    $puAuth = auth()->user();
    $puCanEdit = $puAuth && ($puAuth->isAdmin() || $puAuth->hasPermission('clients', 'edit'));
@endphp

@push('styles')
<style>
    .pu-note { margin:0 0 14px; font-size:13px; color:#6b7280; }
    .pu-group + .pu-group { margin-top:22px; }
    .pu-group h3 { margin:0 0 8px; font-size:14px; color:#111827; }
    .pu-group h3 span { color:#9ca3af; font-weight:400; font-size:12px; margin-left:6px; }
    .pu-table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
    .pu-table th { text-align:left; font-size:11px; letter-spacing:.05em; color:#6b7280; padding:9px 12px; background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
    .pu-table td { padding:9px 12px; font-size:13px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .pu-table tr:last-child td { border-bottom:0; }
    .pu-table small { display:block; color:#9ca3af; font-size:12px; }
    .pu-owner td { background:#fffaf5; }
    .pu-badge { display:inline-block; font-size:12px; padding:2px 10px; border-radius:999px; background:#f3f4f6; color:#374151; white-space:nowrap; }
    .pu-badge.on { background:#dcfce7; color:#166534; }
    .pu-badge.off { background:#fee2e2; color:#991b1b; }
    .pu-badge.owner { background:#fff1e8; color:#c2410c; font-weight:600; }
    .pu-actions { display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end; }
    .pu-actions form { margin:0; }
    .pu-btn { border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:8px; padding:5px 11px; font-size:12.5px; cursor:pointer; }
    .pu-btn:hover { background:#f9fafb; }
    .pu-btn.danger { border-color:#fecaca; color:#b91c1c; }
    .pu-btn.danger:hover { background:#fef2f2; }
    .pu-btn.primary { background:#ea580c; border-color:#ea580c; color:#fff; }
    .pu-btn.primary:hover { background:#c2410c; }
    .pu-empty { padding:22px; text-align:center; color:#9ca3af; font-size:13px; background:#fff; border:1px dashed #e5e7eb; border-radius:10px; }
    .pu-dialog { border:0; border-radius:14px; padding:0; width:min(420px, calc(100vw - 32px)); box-shadow:0 20px 50px rgba(0,0,0,.25); }
    .pu-dialog::backdrop { background:rgba(17,24,39,.45); }
    .pu-dialog form { padding:20px; display:flex; flex-direction:column; gap:12px; }
    .pu-dialog h4 { margin:0; font-size:16px; color:#111827; }
    .pu-dialog p { margin:0; font-size:13px; color:#4b5563; }
    .pu-dialog label { font-size:12.5px; color:#374151; display:flex; flex-direction:column; gap:4px; }
    .pu-dialog input[type=password] { border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; font-size:14px; }
    .pu-dialog-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:4px; }
</style>
@endpush

{{-- ═════ Tab: Usuarios del portal ═════ --}}
<div class="tab-content-show-client" id="portal-users">
    <p class="pu-note">
        La cuenta titular es el propio cliente. Los usuarios del equipo los da de alta el titular desde su portal
        (Usuarios y roles); aquí el personal puede activarlos, desactivarlos, cambiarles la contraseña o eliminarlos.
    </p>

    <div class="pu-group">
        <h3>Usuarios <span>{{ $puMembers->count() }} del equipo</span></h3>
        <div class="table-scroll">
            <table class="pu-table">
                <thead>
                    <tr>
                        <th>NOMBRE</th><th>CORREO</th><th>ROL</th><th>ESTADO</th><th>ÚLTIMO ACCESO</th><th>ALTA</th>
                        @if ($puCanEdit)<th style="text-align:right">ACCIONES</th>@endif
                    </tr>
                </thead>
                <tbody>
                    <tr class="pu-owner">
                        <td>{{ trim($customer->first_name . ' ' . $customer->last_name) ?: '—' }}<small>Titular de la cuenta</small></td>
                        <td>{{ $customer->email ?: '—' }}</td>
                        <td><span class="pu-badge owner">Titular</span></td>
                        <td>
                            @if ($customer->portal_access)
                                <span class="pu-badge on">Acceso al portal activo</span>
                            @else
                                <span class="pu-badge off">Sin acceso al portal</span>
                            @endif
                        </td>
                        <td>—</td>
                        <td>{{ $customer->created_at?->format('d/m/Y') ?? '—' }}</td>
                        @if ($puCanEdit)<td></td>@endif
                    </tr>
                    @foreach ($puMembers as $m)
                        <tr>
                            <td>{{ $m->full_name }}@if ($m->phone)<small>{{ $m->phone }}</small>@endif</td>
                            <td>{{ $m->email }}</td>
                            <td>{{ $m->role?->name ?? 'Sin rol' }}</td>
                            <td>
                                @if ($m->isActive())
                                    <span class="pu-badge on">Activo</span>
                                @else
                                    <span class="pu-badge off">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $m->last_login_at ? $m->last_login_at->format('d/m/Y H:i') : 'Nunca' }}</td>
                            <td>{{ $m->created_at?->format('d/m/Y') ?? '—' }}</td>
                            @if ($puCanEdit)
                                <td>
                                    <div class="pu-actions">
                                        <form method="POST" action="{{ route('admin.clients.portal-users.toggle', [$customer->id, $m->id]) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="pu-btn">{{ $m->isActive() ? 'Desactivar' : 'Activar' }}</button>
                                        </form>
                                        <button type="button" class="pu-btn" data-pu-password
                                            data-action="{{ route('admin.clients.portal-users.password', [$customer->id, $m->id]) }}"
                                            data-name="{{ $m->full_name }}">Cambiar contraseña</button>
                                        <button type="button" class="pu-btn danger" data-pu-delete
                                            data-action="{{ route('admin.clients.portal-users.destroy', [$customer->id, $m->id]) }}"
                                            data-name="{{ $m->full_name }}">Eliminar</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($puMembers->isEmpty())
            <div class="pu-empty" style="margin-top:10px">Este cliente aún no ha agregado usuarios al portal</div>
        @endif
    </div>

    <div class="pu-group">
        <h3>Roles del portal <span>{{ $puRoles->count() }}</span></h3>
        @if ($puRoles->isEmpty())
            <div class="pu-empty">Este cliente aún no tiene roles en su portal.</div>
        @else
            <div class="table-scroll">
                <table class="pu-table">
                    <thead><tr><th>ROL</th><th>DESCRIPCIÓN</th><th style="text-align:right">USUARIOS</th></tr></thead>
                    <tbody>
                        @foreach ($puRoles as $r)
                            <tr>
                                <td>{{ $r->name }}@if ($r->is_system)<small>Rol del sistema</small>@endif</td>
                                <td>{{ $r->description ?: '—' }}</td>
                                <td style="text-align:right">{{ $r->users_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($puCanEdit)
        <dialog class="pu-dialog" id="puPasswordDialog">
            <form method="POST" action="">
                @csrf @method('PUT')
                <h4>Cambiar contraseña</h4>
                <p>Escribe la nueva contraseña de <strong data-pu-name></strong>. Debe tener al menos 8 caracteres.</p>
                <label>Nueva contraseña
                    <input type="password" name="password" minlength="8" required autocomplete="new-password">
                </label>
                <label>Confirmar contraseña
                    <input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                </label>
                <div class="pu-dialog-actions">
                    <button type="button" class="pu-btn" data-pu-close>Cancelar</button>
                    <button type="submit" class="pu-btn primary">Guardar contraseña</button>
                </div>
            </form>
        </dialog>

        <dialog class="pu-dialog" id="puDeleteDialog">
            <form method="POST" action="">
                @csrf @method('DELETE')
                <h4>Eliminar usuario</h4>
                <p>¿Eliminar a <strong data-pu-name></strong>? Perderá el acceso al portal de inmediato. Esta acción no se puede deshacer.</p>
                <div class="pu-dialog-actions">
                    <button type="button" class="pu-btn" data-pu-close>Cancelar</button>
                    <button type="submit" class="pu-btn danger">Eliminar</button>
                </div>
            </form>
        </dialog>

        <script>
            (function () {
                const wire = (btnSel, dialogId) => {
                    const dlg = document.getElementById(dialogId);
                    if (!dlg) return;
                    document.querySelectorAll(btnSel).forEach(btn => btn.addEventListener('click', () => {
                        const form = dlg.querySelector('form');
                        form.action = btn.dataset.action;
                        dlg.querySelector('[data-pu-name]').textContent = btn.dataset.name;
                        form.querySelectorAll('input[type=password]').forEach(i => i.value = '');
                        dlg.showModal();
                    }));
                    dlg.querySelectorAll('[data-pu-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
                };
                wire('[data-pu-password]', 'puPasswordDialog');
                wire('[data-pu-delete]', 'puDeleteDialog');
            })();
        </script>
    @endif
</div>
