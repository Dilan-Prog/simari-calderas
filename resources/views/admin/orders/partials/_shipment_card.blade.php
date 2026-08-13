{{--
    Card "Envío" incluida en admin.orders.show (columna principal, justo
    después de "Dirección de envío"). Tres estados según $shipment:

      (a) null            -> mensaje vacío + botón que revela el formulario.
      (b) formulario       -> siempre en el DOM (oculto con [hidden] hasta que
                               se pide, o reabierto automáticamente si la
                               última validación de admin.shipments.store
                               falló — ver bloque @if($errors->any()) abajo).
      (c) $shipment existe -> info de paquetería, guía, facts, timeline de 4
                               pasos, banner de incidencia y bitácora.

    Variables recibidas vía @include (no se re-derivan aquí): $order,
    $shipment, $shipmentCarriers, $shipmentUsers, $shipmentAllowedTransitions,
    $shipmentStatusMeta, $shipmentMotivos.

    "_shipment_form" (hidden, value="register") distingue los errores de ESTE
    formulario de los del modal de cambio de estatus
    (_shipment_status_modal.blade.php usa value="status") — ambos viven en la
    misma página y old()/$errors son globales a la sesión, mismo criterio que
    admin/delivery/partials/_modal_create.blade.php con "_delivery_form".
--}}
<div class="orders-card">
    <div class="orders-card-header">
        Envío
        @if ($shipment)
            <button type="button" class="js-open-shipment-status-modal orders-status-trigger" title="Cambiar estatus"
                data-shipment-id="{{ $shipment->id }}"
                data-current-status="{{ $shipment->status }}"
                data-allowed='@json($shipmentAllowedTransitions)'
                data-update-url="{{ route('admin.shipments.update-status', $shipment) }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
            </button>
        @endif
    </div>

    <div class="orders-card-body">

        @if (!$shipment)
            {{-- (a) Estado vacío --}}
            <div id="shipmentEmptyState" style="text-align:center;color:#9ca3af;font-size:13px;padding:28px 18px;">
                <p style="margin:0 0 14px;">Aún no se registra guía para esta orden.</p>
                <button type="button" class="button-primary size-adjustment" id="shipmentShowFormBtn">Registrar envío</button>
            </div>
        @endif

        @if (!$shipment)
            {{-- (b) Formulario de registro --}}
            <form id="shipmentRegisterForm" method="POST" action="{{ route('admin.shipments.store', $order) }}" hidden>
                @csrf
                <input type="hidden" name="_shipment_form" value="register">

                <div class="shipment-form-grid">
                    <div class="shipment-form-field">
                        <label for="shipmentFormCarrier">Paquetería *</label>
                        <select name="carrier_id" id="shipmentFormCarrier" class="users-manager-select @error('carrier_id') is-invalid @enderror" required>
                            <option value="">Selecciona…</option>
                            @foreach ($shipmentCarriers as $c)
                                <option value="{{ $c->id }}" data-url="{{ $c->tracking_url_template }}" @selected(old('carrier_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('carrier_id') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormGuia">Guía *</label>
                        <input type="text" name="guia" id="shipmentFormGuia" class="users-manager-input @error('guia') is-invalid @enderror" value="{{ old('guia') }}" placeholder="Número de guía" required>
                        <p class="shipment-form-hint" id="shipmentFormGuiaHint">Ingresa la guía proporcionada por la paquetería (mínimo 8 caracteres).</p>
                        @error('guia') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormFecha">Fecha de envío</label>
                        <input type="date" name="fecha_envio" id="shipmentFormFecha" class="users-manager-input @error('fecha_envio') is-invalid @enderror" value="{{ old('fecha_envio') }}">
                        @error('fecha_envio') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormEta">Entrega estimada (ETA)</label>
                        <input type="date" name="eta" id="shipmentFormEta" class="users-manager-input @error('eta') is-invalid @enderror" value="{{ old('eta') }}">
                        @error('eta') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormBultos">Bultos</label>
                        <input type="number" name="bultos" id="shipmentFormBultos" min="1" value="{{ old('bultos', 1) }}" class="users-manager-input @error('bultos') is-invalid @enderror">
                        @error('bultos') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormPeso">Peso (kg)</label>
                        <input type="text" name="peso" id="shipmentFormPeso" value="{{ old('peso') }}" class="users-manager-input @error('peso') is-invalid @enderror" placeholder="0.00">
                        @error('peso') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormCosto">Costo de envío</label>
                        <input type="text" name="costo" id="shipmentFormCosto" value="{{ old('costo') }}" class="users-manager-input @error('costo') is-invalid @enderror" placeholder="0.00">
                        @error('costo') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="shipment-form-field">
                        <label for="shipmentFormAutorizo">Autorizó</label>
                        <select name="authorized_by_user_id" id="shipmentFormAutorizo" class="users-manager-select @error('authorized_by_user_id') is-invalid @enderror">
                            <option value="">Selecciona…</option>
                            @foreach ($shipmentUsers as $u)
                                <option value="{{ $u->id }}" @selected(old('authorized_by_user_id') == $u->id)>{{ $u->full_name }}</option>
                            @endforeach
                        </select>
                        @error('authorized_by_user_id') <span class="field-error-msg">{{ $message }}</span> @enderror
                    </div>
                </div>

                <p class="shipment-form-link-preview" id="shipmentFormLinkPreview"></p>

                <label class="shipment-form-checkbox">
                    <input type="checkbox" name="avanzar" id="shipmentFormAvanzar" value="1" @checked(old('avanzar'))>
                    Avanzar la orden a Enviado al guardar
                </label>

                <div style="margin-top:16px;display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="button-secondary size-adjustment" id="shipmentFormCancelBtn">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment" id="shipmentFormSubmitBtn" disabled>Guardar envío</button>
                </div>
            </form>
        @endif

        @if ($shipment)
            {{-- (c) Envío registrado --}}
            <div class="shipment-carrier-block">
                @if ($shipment->carrier)
                    <span class="shipment-carrier-chip-lg" style="background:{{ $shipment->carrier->bg_color ?? '#f1f2f4' }};color:{{ $shipment->carrier->text_color ?? '#6b7280' }};">{{ $shipment->carrier->initials }}</span>
                    <div style="flex:1;min-width:0;">
                        <p class="shipment-carrier-name">{{ $shipment->carrier->name }}</p>
                        @if ($shipment->carrier->meta_line)
                            <p class="shipment-carrier-meta">{{ $shipment->carrier->meta_line }}</p>
                        @endif
                    </div>
                @else
                    <span style="color:#9ca3af;flex:1;">Sin transportista asignado</span>
                @endif
                @php $cMeta = $shipmentStatusMeta[$shipment->status] ?? []; @endphp
                <span class="shipments-status-pill" style="color:{{ $cMeta['color'] ?? '#6b7280' }};background:{{ $cMeta['bg'] ?? '#f1f2f4' }};">
                    <span class="shipments-status-pill-dot"></span>{{ $cMeta['label'] ?? $shipment->status }}
                </span>
            </div>

            <div class="shipment-guia-row">
                <div>
                    <span class="shipment-guia-label">Guía</span>
                    <span class="shipment-guia-value">{{ $shipment->guia }}</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="button-secondary size-adjustment" id="shipmentCopyGuiaBtn" data-guia="{{ $shipment->guia }}">Copiar</button>
                    @if ($shipment->carrier?->tracking_url_template)
                        <a href="{{ $shipment->carrier->tracking_url_template }}{{ str_replace(' ', '', $shipment->guia) }}" target="_blank" rel="noopener" class="button-secondary size-adjustment">Ver en paquetería →</a>
                    @endif
                </div>
            </div>

            <div class="shipment-facts-grid">
                <div class="shipment-fact">
                    <span class="shipment-fact-label">Fecha de envío</span>
                    <span class="shipment-fact-value">{{ $shipment->fecha_envio?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="shipment-fact">
                    <span class="shipment-fact-label">Entrega estimada</span>
                    <span class="shipment-fact-value">{{ $shipment->eta?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="shipment-fact">
                    <span class="shipment-fact-label">Bultos</span>
                    <span class="shipment-fact-value">{{ $shipment->bultos ?? '—' }}</span>
                </div>
                <div class="shipment-fact">
                    <span class="shipment-fact-label">Costo</span>
                    <span class="shipment-fact-value">{{ $shipment->costo !== null ? '$' . number_format($shipment->costo, 2) : '—' }}</span>
                </div>
            </div>

            {{-- Timeline de 4 pasos. Si el estatus actual es 'incidencia', el
                 flujo se "congela" en 'en_transito' (última posición de FLOW
                 antes de la salida lateral) y el último paso ('entregado') se
                 pinta con un estado "estancado" (!) en vez de pendiente. --}}
            @php
                $flow = ['preparando', 'enviado', 'en_transito', 'entregado'];
                $isIncidencia = $shipment->status === 'incidencia';
                $idx = $isIncidencia ? array_search('en_transito', $flow, true) : array_search($shipment->status, $flow, true);
            @endphp
            <div class="shipment-timeline">
                @foreach ($flow as $flowIndex => $flowStatus)
                    @php
                        $flowMeta = $shipmentStatusMeta[$flowStatus] ?? [];
                        $isStalled = $isIncidencia && $flowStatus === 'entregado';
                        $stepClass = '';
                        if ($isStalled) {
                            $stepClass = 'is-stalled';
                        } elseif ($flowIndex < $idx) {
                            $stepClass = 'is-done';
                        } elseif ($flowIndex === $idx) {
                            $stepClass = 'is-current';
                        }
                    @endphp
                    <div class="shipment-timeline-step {{ $stepClass }}">
                        <div class="shipment-timeline-circle">
                            @if ($stepClass === 'is-done')
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @elseif ($isStalled)
                                !
                            @else
                                {{ $flowIndex + 1 }}
                            @endif
                        </div>
                        <span class="shipment-timeline-label">{{ $flowMeta['label'] ?? ucfirst($flowStatus) }}</span>
                    </div>
                @endforeach
            </div>

            @if ($isIncidencia)
                <div class="shipment-incidencia-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <div>
                        <strong>Incidencia.</strong>
                        @if ($shipment->motivo)
                            Motivo: {{ $shipmentMotivos[$shipment->motivo] ?? $shipment->motivo }}.
                        @endif
                        @if ($shipment->incidencia_note)
                            {{ $shipment->incidencia_note }}
                        @endif
                    </div>
                </div>
            @endif

            <p class="orders-card-row-label" style="margin:18px 0 8px;font-weight:700;font-size:12.5px;color:#111827;">Actividad del envío</p>
            <div class="shipment-activity">
                @forelse ($shipment->statusLogs as $log)
                    @php $logMeta = $shipmentStatusMeta[$log->to_status] ?? []; @endphp
                    <div class="shipment-activity-item">
                        <span class="shipment-activity-dot" style="background:{{ $logMeta['color'] ?? '#9ca3af' }};"></span>
                        <div>
                            <p class="shipment-activity-text">
                                Estatus actualizado a <strong>{{ $logMeta['label'] ?? $log->to_status }}</strong>
                                @if ($log->motivo)
                                    — {{ $shipmentMotivos[$log->motivo] ?? $log->motivo }}
                                @endif
                            </p>
                            @if ($log->note)
                                <p class="shipment-activity-text" style="color:#6b7280;">{{ $log->note }}</p>
                            @endif
                            <p class="shipment-activity-meta">{{ $log->changedBy->full_name ?? 'Sistema' }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p style="font-size:12.5px;color:#9ca3af;">Sin movimientos registrados todavía.</p>
                @endforelse
            </div>

            @if (in_array($shipment->status, ['preparando', 'enviado'], true))
                <form method="POST" action="{{ route('admin.shipments.destroy', $shipment) }}" onsubmit="return confirm('¿Eliminar este envío?')" style="margin-top:16px;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="button-secondary size-adjustment" style="color:#c81e1e;border-color:#f8c9c9;">Eliminar envío</button>
                </form>
            @endif
        @endif

    </div>
</div>

@if (!$shipment && $errors->any() && old('_shipment_form') === 'register')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('shipmentRegisterForm');
            var empty = document.getElementById('shipmentEmptyState');
            if (form) form.hidden = false;
            if (empty) empty.hidden = true;
        });
    </script>
@endif
