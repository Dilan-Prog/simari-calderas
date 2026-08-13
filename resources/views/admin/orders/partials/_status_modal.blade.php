{{--
    Modal compartido de cambio de estatus del módulo Órdenes — un único
    overlay por página, incluido tanto desde index.blade.php (una fila) como
    desde show.blade.php (el botón del header). Shell estático: todo el
    contenido dependiente del pedido (folio, estatus actual, opciones
    permitidas) lo rellena resources/js/admin/orders.js al abrir, leyendo los
    data-attributes del botón .js-open-status-modal que disparó la apertura
    — el JS nunca recalcula qué transiciones son válidas, siempre viene de
    StoreOrderStatus::allowedTransitions() ya evaluado server-side.

    Única excepción dinámica: si updateStatus() regresó aquí con errores de
    validación (back()->withErrors()->withInput()), este partial se auto-abre
    y reconstruye la selección previa reutilizando el mismo flujo de clicks
    que ya maneja orders.js (ver bloque @if($errors->any()) al final).
--}}
<div class="orders-status-modal-overlay" id="orderStatusModalOverlay">
    <div class="orders-status-modal">
        <div class="orders-status-modal-header">
            <div>
                <p class="orders-status-modal-title">Cambiar estatus</p>
                <p class="orders-status-modal-sub">
                    Folio <strong id="orderStatusModalFolio"></strong>
                    · Estatus actual: <span id="orderStatusModalCurrent"></span>
                </p>
            </div>
            <button type="button" class="orders-status-modal-close" aria-label="Cerrar"
                onclick="document.getElementById('orderStatusCancelBtn').click()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="orders-status-modal-body">
            @if ($errors->any())
                <div style="background:#fdecec;border:1px solid #f8c9c9;color:#c81e1e;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="" id="orderStatusForm">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" id="orderStatusPick" value="{{ old('status') }}">

                {{-- JS inyecta un .orders-status-option por cada estatus en data-allowed --}}
                <div class="orders-status-options" id="orderStatusOptionsList"></div>

                <div class="orders-status-field" id="orderStatusMotivoWrap" style="display:none;">
                    <label for="orderStatusMotivo">Motivo de cancelación</label>
                    {{-- Catálogo fijo: se renderiza aquí para que el select ya
                         funcione si el modal se auto-abre por errores de
                         validación; en el flujo normal (click en un trigger)
                         orders.js reconstruye este mismo contenido al mostrar
                         el wrap, de forma idempotente. --}}
                    <select name="motivo" id="orderStatusMotivo">
                        <option value="">Selecciona un motivo…</option>
                        @foreach (\App\Support\StoreOrderStatus::MOTIVOS as $motivoKey => $motivoLabel)
                            <option value="{{ $motivoKey }}" @selected(old('motivo') === $motivoKey)>{{ $motivoLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="orders-status-field">
                    <label for="orderStatusNote" id="orderStatusNoteLabel">Nota interna (opcional)</label>
                    <textarea name="note" id="orderStatusNote" rows="3">{{ old('note') }}</textarea>
                    <p class="orders-status-field-hint is-warning" id="orderStatusNoteHint" style="display:none;">
                        Obligatoria porque estás retrocediendo el estatus de la orden.
                    </p>
                </div>

                <p class="orders-status-field-hint">Acción reservada al rol Administrador.</p>
            </form>
        </div>

        <div class="orders-status-modal-footer">
            <button type="button" class="button-secondary size-adjustment" id="orderStatusCancelBtn">Cancelar</button>
            <button type="submit" form="orderStatusForm" class="button-primary size-adjustment" id="orderStatusApplyBtn" disabled>Aplicar cambio</button>
        </div>
    </div>
</div>

@if ($errors->any())
    @isset($order)
        {{-- Trigger oculto con los mismos data-attributes que cualquier fila —
             reutiliza openStatusModal() de orders.js para reconstruir la
             lista de opciones real (data-allowed ya calculado server-side)
             en vez de duplicar esa lógica aquí. Solo disponible cuando el
             partial se incluye desde show.blade.php (index no tiene un
             $order singular en contexto). --}}
        <button type="button" class="js-open-status-modal" id="orderStatusErrorRecoveryTrigger" style="display:none;"
            data-order-id="{{ $order->id }}"
            data-folio="{{ $order->order_number }}"
            data-current-status="{{ $order->status }}"
            data-allowed='@json(\App\Support\StoreOrderStatus::allowedTransitions($order->status))'
            data-update-url="{{ route('admin.orders.update-status', $order) }}"></button>
    @endisset

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var recoveryTrigger = document.getElementById('orderStatusErrorRecoveryTrigger');
            if (recoveryTrigger) {
                recoveryTrigger.click();
            } else {
                var overlay = document.getElementById('orderStatusModalOverlay');
                if (overlay) overlay.classList.add('is-open');
            }

            @if (old('status'))
                var pickedStatus = @json(old('status'));
                var optionEl = document.querySelector('#orderStatusOptionsList .orders-status-option[data-status="' + pickedStatus + '"]');
                if (optionEl) optionEl.click();
            @endif

            @if (old('motivo'))
                var motivoSelect = document.getElementById('orderStatusMotivo');
                if (motivoSelect) {
                    motivoSelect.value = @json(old('motivo'));
                    motivoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            @endif

            @if (old('note'))
                var noteField = document.getElementById('orderStatusNote');
                if (noteField) {
                    noteField.value = @json(old('note'));
                    noteField.dispatchEvent(new Event('input', { bubbles: true }));
                }
            @endif
        });
    </script>
@endif
