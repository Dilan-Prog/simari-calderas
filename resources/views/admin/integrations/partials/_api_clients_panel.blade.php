{{-- Panel "API / N8N" — clientes de API (App\Models\ApiClient) + emisión/
     revocación de sus tokens Sanctum. Formularios planos (sin modal/JS),
     mismo criterio simple que el formulario de SMTP -- este panel no
     necesita la complejidad de Webhooks/WhatsApp (headers dinámicos, test
     de conexión en vivo, etc.). --}}
<div class="integr-panel" id="integrPanel-api-clients">
    <div class="integr-panel-card">
        <div class="integr-panel-header">
            <div class="integr-panel-avatar">N8</div>
            <div class="integr-panel-header-text">
                <div class="integr-panel-title-row">
                    <h2>API / N8N</h2>
                    <span class="integr-badge {{ $apiClientsConfigured ? 'is-on' : '' }}">
                        {{ $apiClientsConfigured ? 'Conectada' : 'Sin configurar' }}
                    </span>
                </div>
                <p class="integr-panel-desc">
                    Clientes de API externos (N8N u otros) autenticados con tokens Bearer (Sanctum).
                    Cada token tiene abilities propias — solo puede hacer lo que se le habilite aquí.
                </p>
            </div>
        </div>

        <div class="pform-panel" style="margin-bottom:16px;">
            <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Ruta base de la API</p>
            <code style="display:block; padding:10px 12px; background:#f2f3f5; border-radius:6px; word-break:break-all; font-size:13px;">{{ $apiBaseUrl }}</code>
            <p class="pform-hint" style="margin-top:8px;">
                Cada endpoint de abajo es relativo a esta ruta (ej. <code>GET /customers</code> = <code>{{ $apiBaseUrl }}/customers</code>).
                Encabezado requerido: <code>Authorization: Bearer {token}</code>.
            </p>
        </div>

        @if (session('plain_text_token'))
            <div class="pform-panel" style="border-left:4px solid #10b981; margin-bottom:16px;">
                <p style="margin:0 0 8px; color:#047857; font-weight:600;">
                    Token generado — cópialo ahora, no se volverá a mostrar:
                </p>
                <code style="display:block; padding:10px 12px; background:#f2f3f5; border-radius:6px; word-break:break-all; font-size:13px;">{{ session('plain_text_token') }}</code>
            </div>
        @endif

        <header class="clients-manager-main" style="margin-bottom:4px;">
            <div>
                <h2 class="pform-panel-title" style="margin:0;">Clientes de API</h2>
                <p class="pform-hint">Cada uno representa una integración externa (ej. "N8N — producción").</p>
            </div>
        </header>

        <main class="table-container-clients-manager" style="margin-top:16px;">
            <div class="table-scroll">
                <table class="clients-manager-table">
                    <thead>
                        <tr>
                            <th>NOMBRE</th>
                            <th>TOKENS</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apiClients as $client)
                            <tr>
                                <td>
                                    <p class="pm-name">{{ $client->name }}</p>
                                    @if ($client->description)
                                        <p class="pform-hint" style="margin:2px 0 0;">{{ $client->description }}</p>
                                    @endif
                                </td>
                                <td>{{ $client->tokens_count }}</td>
                                <td>
                                    <span class="integr-badge {{ $client->is_active ? 'is-on' : '' }}">
                                        {{ $client->is_active ? 'Activo' : 'Desactivado' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-container">
                                        <form method="POST" action="{{ route('admin.integrations.api-clients.toggle', $client) }}" style="display:inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="button-secondary size-adjustment">
                                                {{ $client->is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.integrations.api-clients.destroy', $client) }}" style="display:inline;"
                                            onsubmit="return confirm('¿Eliminar &quot;{{ $client->name }}&quot; y todos sus tokens? Esto no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button-secondary size-adjustment" style="color:#b91c1c;">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" style="background:#fafafa; padding:16px 20px;">
                                    {{-- Tokens existentes de este cliente --}}
                                    @if ($client->tokens->isNotEmpty())
                                        <p class="pform-hint" style="margin:0 0 8px; font-weight:600;">Tokens de "{{ $client->name }}"</p>
                                        <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:14px;">
                                            @foreach ($client->tokens as $token)
                                                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 10px; background:#fff; border:1px solid #e5e7eb; border-radius:6px;">
                                                    <span style="font-size:13px;">
                                                        <strong>{{ $token->name }}</strong>
                                                        — {{ implode(', ', $token->abilities ?? []) }}
                                                    </span>
                                                    <form method="POST" action="{{ route('admin.integrations.api-clients.tokens.destroy', [$client, $token->id]) }}"
                                                        onsubmit="return confirm('¿Revocar este token? Cualquier integración que lo use dejará de funcionar de inmediato.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn" title="Revocar" style="color:#b91c1c;">✕</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Nuevo token para este cliente --}}
                                    <details>
                                        <summary style="cursor:pointer; font-size:13px; font-weight:600; color:#374151;">+ Generar token nuevo</summary>
                                        <form method="POST" action="{{ route('admin.integrations.api-clients.tokens.store', $client) }}" style="margin-top:10px;">
                                            @csrf
                                            <div class="ap-field-group" style="max-width:320px;">
                                                <label class="supliers-manager-slider-label">Nombre del token</label>
                                                <input type="text" class="users-manager-input" name="token_name" maxlength="100"
                                                    placeholder="Ej: N8N producción" required>
                                            </div>
                                            <div class="ap-field-group" style="margin-top:10px;">
                                                <label class="supliers-manager-slider-label">Abilities</label>
                                                @foreach ($apiAbilities as $groupLabel => $group)
                                                    <p style="margin:10px 0 4px; font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">{{ $groupLabel }}</p>
                                                    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:6px;">
                                                        @foreach ($group as $ability => $label)
                                                            <label style="display:flex; align-items:flex-start; gap:6px; font-size:13px; font-weight:400;">
                                                                <input type="checkbox" name="abilities[]" value="{{ $ability }}" style="margin-top:3px;">
                                                                <span>
                                                                    {{ $label }}
                                                                    @foreach (($apiEndpoints[$ability] ?? []) as $endpoint)
                                                                        <code style="display:block; font-size:11px; color:#9ca3af;">{{ $endpoint }}</code>
                                                                    @endforeach
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="submit" class="button-primary size-adjustment" style="margin-top:12px;">
                                                Generar token
                                            </button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:40px; color:#6b7280;">
                                    No hay clientes de API registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>

        <div class="pform-panel-wrap" style="margin-top:20px;">
            <div class="pform-panel">
                <h2 class="pform-panel-title">Nuevo cliente de API</h2>
                <form method="POST" action="{{ route('admin.integrations.api-clients.store') }}">
                    @csrf
                    <div class="user-manager-form user-manager-form-3 ap-field-grid">
                        <div class="ap-field-group">
                            <label class="supliers-manager-slider-label">Nombre <span style="color:red">*</span></label>
                            <input type="text" class="users-manager-input" name="name" maxlength="150"
                                placeholder="Ej: N8N" required>
                        </div>
                        <div class="ap-field-group">
                            <label class="supliers-manager-slider-label">Descripción</label>
                            <input type="text" class="users-manager-input" name="description" maxlength="2000"
                                placeholder="Opcional">
                        </div>
                    </div>
                    <button type="submit" class="button-primary size-adjustment" style="margin-top:12px;">
                        Crear cliente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
