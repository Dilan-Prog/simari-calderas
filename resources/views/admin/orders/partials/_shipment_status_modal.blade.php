{{--
    Modal de cambio de estatus del módulo Envíos — mismo patrón exacto que
    admin.orders.partials._status_modal (shell estático, resources/js/admin/
    shipments.js rellena todo el contenido dependiente del envío al abrir,
    leyendo los data-attributes del botón .js-open-shipment-status-modal que
    disparó la apertura). El JS nunca decide qué transiciones son válidas:
    siempre viene ya calculado server-side en data-allowed
    (ShipmentStatus::allowedTransitions()).

    "_shipment_form" (hidden, value="status") distingue los errores de ESTE
    formulario de los del formulario de registro de envío
    (_shipment_card.blade.php usa value="register") — ambos viven en la
    misma página show.blade.php y old()/$errors son globales a la sesión.

    Única excepción dinámica: si updateStatus() regresó aquí con errores de
    validación, este partial se auto-abre y reconstruye la selección previa
    reutilizando el mismo flujo de clicks que ya maneja shipments.js (ver
    bloque @if($errors->any()) al final).
--}}
<div class="orders-status-modal-overlay" id="shipmentStatusModalOverlay">
    <div class="orders-status-modal">
        <div class="orders-status-modal-header">
            <div>
                <p class="orders-status-modal-title">Cambiar estatus del envío</p>
                <p class="orders-status-modal-sub">
                    Estatus actual: <span id="shipmentStatusModalCurrent"></span>
                </p>
            </div>
            <button type="button" class="orders-status-modal-close" aria-label="Cerrar"
                onclick="document.getElementById('shipmentStatusCancelBtn').click()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="orders-status-modal-body">
            @if ($errors->any() && old('_shipment_form') === 'status')
                <div style="background:#fdecec;border:1px solid #f8c9c9;color:#c81e1e;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="" id="shipmentStatusForm">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_shipment_form" value="status">
                <input type="hidden" name="status" id="shipmentStatusPick" value="{{ old('_shipment_form') === 'status' ? old('status') : '' }}">

                {{-- JS inyecta un .orders-status-option por cada estatus en data-allowed --}}
                <div class="orders-status-options" id="shipmentStatusOptionsList"></div>

                <div class="orders-status-field" id="shipmentMotivoWrap" style="display:none;">
                    <label for="shipmentMotivo">Motivo de la incidencia</label>
                    <select name="motivo" id="shipmentMotivo">
                        <option value="">Selecciona un motivo…</option>
                        @foreach ($shipmentMotivos as $motivoKey => $motivoLabel)
                            <option value="{{ $motivoKey }}" @selected(old('_shipment_form') === 'status' && old('motivo') === $motivoKey)>{{ $motivoLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="orders-status-field">
                    <label for="shipmentStatusNote" id="shipmentStatusNoteLabel">Nota de seguimiento (opcional)</label>
                    <textarea name="note" id="shipmentStatusNote" rows="3">{{ old('_shipment_form') === 'status' ? old('note') : '' }}</textarea>
                </div>

                <div class="orders-status-field-hint is-warning" id="shipmentIncidenciaWarning" style="display:none;">
                    La incidencia detiene el avance del envío. La orden permanece en Enviado hasta que se resuelva.
                </div>
            </form>
        </div>

        <div class="orders-status-modal-footer">
            <button type="button" class="button-secondary size-adjustment" id="shipmentStatusCancelBtn">Cancelar</button>
            <button type="submit" form="shipmentStatusForm" class="button-primary size-adjustment" id="shipmentStatusApplyBtn" disabled>Aplicar cambio</button>
        </div>
    </div>
</div>

@if ($errors->any() && old('_shipment_form') === 'status')
    @isset($shipment)
        {{-- Trigger oculto con los mismos data-attributes que el botón real —
             reutiliza openShipmentStatusModal() de shipments.js para
             reconstruir la lista de opciones real (data-allowed ya calculado
             server-side) en vez de duplicar esa lógica aquí. --}}
        <button type="button" class="js-open-shipment-status-modal" id="shipmentStatusErrorRecoveryTrigger" style="display:none;"
            data-shipment-id="{{ $shipment->id }}"
            data-current-status="{{ $shipment->status }}"
            data-allowed='@json($shipmentAllowedTransitions)'
            data-update-url="{{ route('admin.shipments.update-status', $shipment) }}"></button>
    @endisset

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var recoveryTrigger = document.getElementById('shipmentStatusErrorRecoveryTrigger');
            if (recoveryTrigger) {
                recoveryTrigger.click();
            } else {
                var overlay = document.getElementById('shipmentStatusModalOverlay');
                if (overlay) overlay.classList.add('is-open');
            }

            @if (old('status'))
                var pickedStatus = @json(old('status'));
                var optionEl = document.querySelector('#shipmentStatusOptionsList .orders-status-option[data-status="' + pickedStatus + '"]');
                if (optionEl) optionEl.click();
            @endif

            @if (old('motivo'))
                var motivoSelect = document.getElementById('shipmentMotivo');
                if (motivoSelect) {
                    motivoSelect.value = @json(old('motivo'));
                    motivoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            @endif

            @if (old('note'))
                var noteField = document.getElementById('shipmentStatusNote');
                if (noteField) {
                    noteField.value = @json(old('note'));
                    noteField.dispatchEvent(new Event('input', { bubbles: true }));
                }
            @endif
        });
    </script>
@endif
