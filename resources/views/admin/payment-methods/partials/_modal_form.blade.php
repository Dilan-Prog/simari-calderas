{{-- Crear/editar método de pago — un solo modal reutilizado para ambos
     modos (el JS de _scripts.blade.php rellena los campos si es edición),
     mismo shell .del-confirm-overlay/.del-confirm-box que el resto del
     admin (ver resources/css/admin/components/ui-kit.css), ampliado con
     .pm-modal-box (dos columnas: formulario + vista previa en vivo). --}}
<div id="paymentMethodModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box pm-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="5" rx="2" />
                    <line x1="2" x2="22" y1="10" y2="10" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="paymentMethodModalTitle">Nuevo Método de Pago</h2>
                <p class="del-confirm-desc">Define cómo pueden pagarte tus clientes.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closePaymentMethodModal">✕</button>
        </div>

        <div id="payment-method-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body pm-modal-body" id="paymentMethodForm">
            @csrf

            <div class="pm-modal-columns">
                {{-- ── Columna principal ── --}}
                <div class="pm-modal-main">

                    {{-- Sección 1 — Tipo de método --}}
                    <div class="pm-section">
                        <h3 class="pm-section-title">Tipo de método <span style="color:red">*</span></h3>
                        <input type="hidden" name="type" id="pmType" value="transferencia">
                        <div class="pm-type-grid">
                            <button type="button" class="pm-type-card active" data-type="transferencia">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 21h18" />
                                        <path d="M3 10h18" />
                                        <path d="M5 6l7-3 7 3" />
                                        <path d="M4 10v11" />
                                        <path d="M20 10v11" />
                                        <path d="M8 14v3" />
                                        <path d="M12 14v3" />
                                        <path d="M16 14v3" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Transferencia bancaria</span>
                                <span class="pm-type-hint">SPEI a cuenta bancaria</span>
                            </button>

                            <button type="button" class="pm-type-card" data-type="pasarela">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="5" rx="2" />
                                        <line x1="2" x2="22" y1="10" y2="10" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Pasarela de pago</span>
                                <span class="pm-type-hint">Tarjeta vía procesador en línea</span>
                            </button>

                            <button type="button" class="pm-type-card" data-type="referencia">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z" />
                                        <path d="M8 7h8" />
                                        <path d="M8 11h8" />
                                        <path d="M8 15h5" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Pago por referencia</span>
                                <span class="pm-type-hint">OXXO, tiendas de conveniencia</span>
                            </button>

                            <button type="button" class="pm-type-card" data-type="credito">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="7" rx="2" ry="2" />
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Crédito empresarial</span>
                                <span class="pm-type-hint">Pago a plazo con línea autorizada</span>
                            </button>

                            <button type="button" class="pm-type-card" data-type="digital">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <rect width="14" height="20" x="5" y="2" rx="2" ry="2" />
                                        <path d="M12 18h.01" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Billetera digital</span>
                                <span class="pm-type-hint">PayPal, Mercado Pago y similares</span>
                            </button>

                            <button type="button" class="pm-type-card" data-type="otro">
                                <span class="pm-type-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="1" />
                                        <circle cx="19" cy="12" r="1" />
                                        <circle cx="5" cy="12" r="1" />
                                    </svg>
                                </span>
                                <span class="pm-type-name">Otro</span>
                                <span class="pm-type-hint">Cualquier otro método</span>
                            </button>
                        </div>
                    </div>

                    {{-- Sección 2 — Identificación --}}
                    <div class="pm-section">
                        <h3 class="pm-section-title">Identificación</h3>
                        <div class="user-manager-form user-manager-form-3 ap-field-grid">
                            <div class="ap-field-group">
                                <label class="supliers-manager-slider-label">
                                    Nombre <span style="color:red">*</span>
                                </label>
                                <input type="text" class="users-manager-input" name="name" id="pmName" maxlength="100"
                                    placeholder="Ej: Transferencia BBVA">
                            </div>
                            <div class="ap-field-group">
                                <label class="supliers-manager-slider-label">Orden</label>
                                <input type="number" class="users-manager-input" name="sort_order" id="pmSortOrder"
                                    min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="ap-field-group" style="margin-top:12px;">
                            <label class="supliers-manager-slider-label">Descripción</label>
                            <textarea class="users-manager-input" name="description" id="pmDescription" rows="2"
                                maxlength="140" placeholder="Frase corta visible para el cliente al elegir este método"></textarea>
                            <p class="pm-char-count" id="pmDescriptionCount">140 caracteres restantes</p>
                        </div>
                    </div>

                    {{-- Sección 3 — Detalle según tipo --}}
                    <div class="pm-section">
                        <h3 class="pm-section-title">Detalle del método</h3>

                        {{-- transferencia --}}
                        <div class="config-fields" data-type="transferencia">
                            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Moneda</label>
                                    <select class="users-manager-select" name="details[moneda]" id="pmDetailsMoneda">
                                        <option value="MXN">MXN</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                                <div class="ap-field-group" style="grid-column: span 2;">
                                    <label class="supliers-manager-slider-label">SWIFT / ABA (opcional)</label>
                                    <input type="text" class="users-manager-input" name="details[swift_aba]"
                                        id="pmDetailsSwiftAba" maxlength="34" placeholder="Solo para pagos internacionales">
                                </div>
                            </div>
                            <div class="user-manager-form user-manager-form-3 ap-field-grid" style="margin-top:12px;">
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Banco</label>
                                    <input type="text" class="users-manager-input" name="bank_name" id="pmBankName"
                                        maxlength="100" placeholder="Ej: BBVA">
                                </div>
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">CLABE</label>
                                    <input type="text" class="users-manager-input" name="clabe" id="pmClabe"
                                        maxlength="18" placeholder="18 dígitos">
                                </div>
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Número de cuenta</label>
                                    <input type="text" class="users-manager-input" name="account_number"
                                        id="pmAccountNumber" maxlength="30" placeholder="Opcional si ya diste CLABE">
                                </div>
                            </div>
                            <div class="users-manager-email-camp" style="margin-top:12px;">
                                <label class="supliers-manager-slider-label">Beneficiario</label>
                                <input type="text" class="users-manager-input" name="beneficiary_name"
                                    id="pmBeneficiaryName" maxlength="150"
                                    placeholder="Nombre a quien está a nombre la cuenta">
                            </div>
                            <p class="ap-readonly-note">
                                Captura Banco + CLABE, o al menos el número de cuenta.
                            </p>
                        </div>

                        {{-- pasarela / digital (mismos campos) --}}
                        <div class="config-fields" data-type="pasarela">
                            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Procesador</label>
                                    <select class="users-manager-select" name="details[processor]"
                                        id="pmDetailsProcessor">
                                        <option value="">Selecciona...</option>
                                        <option value="stripe">Stripe</option>
                                        <option value="conekta">Conekta</option>
                                        <option value="mercadopago">Mercado Pago</option>
                                        <option value="openpay">Openpay</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="clip">Clip</option>
                                    </select>
                                </div>
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Comisión (%)</label>
                                    <input type="number" class="users-manager-input" name="details[comision_percent]"
                                        id="pmDetailsComisionPercent" min="0" step="0.01" placeholder="Ej: 3.6">
                                </div>
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Comisión fija (MXN)</label>
                                    <input type="number" class="users-manager-input" name="details[comision_fija]"
                                        id="pmDetailsComisionFija" min="0" step="0.01" placeholder="Ej: 3.00">
                                </div>
                            </div>
                            <div class="users-manager-email-camp" style="margin-top:12px;">
                                <label class="supliers-manager-slider-label">Llave pública / Merchant ID</label>
                                <input type="text" class="users-manager-input" name="details[llave_publica]"
                                    id="pmDetailsLlavePublica" maxlength="190" placeholder="pk_live_... / merchant id">
                            </div>
                            <div class="ap-field-group" style="margin-top:12px;">
                                <label class="supliers-manager-slider-label">Meses sin intereses disponibles</label>
                                <div class="pm-pill-row">
                                    @foreach ([3, 6, 9, 12, 18] as $msi)
                                        <input type="checkbox" class="pm-pill-input" name="details[msi][]"
                                            id="pmMsi{{ $msi }}" value="{{ $msi }}">
                                        <label class="pm-pill" for="pmMsi{{ $msi }}">{{ $msi }} MSI</label>
                                    @endforeach
                                </div>
                            </div>
                            <label class="ap-primary-toggle" for="pmDetailsCapturaManual" style="margin-top:12px;">
                                <input type="checkbox" id="pmDetailsCapturaManual" name="details[captura_manual]">
                                <div>
                                    <strong>Autorizar y cobrar después</strong>
                                    <span>La transacción se autoriza al momento y se captura manualmente más
                                        tarde.</span>
                                </div>
                            </label>
                        </div>

                        {{-- referencia --}}
                        <div class="config-fields" data-type="referencia">
                            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                                <div class="ap-field-group" style="grid-column: span 2;">
                                    <label class="supliers-manager-slider-label">Convenio</label>
                                    <input type="text" class="users-manager-input" name="details[convenio]"
                                        id="pmDetailsConvenio" maxlength="150" placeholder="Ej: OXXO Pay · Conekta">
                                </div>
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Vigencia de la referencia</label>
                                    <select class="users-manager-select" name="details[vigencia]" id="pmDetailsVigencia">
                                        <option value="24h">24 horas</option>
                                        <option value="48h">48 horas</option>
                                        <option value="72h">72 horas</option>
                                        <option value="7dias">7 días</option>
                                    </select>
                                </div>
                            </div>
                            <div class="users-manager-email-camp" style="margin-top:12px;">
                                <label class="supliers-manager-slider-label">Instrucciones para el cliente</label>
                                <textarea class="users-manager-input" name="details[instrucciones]"
                                    id="pmDetailsInstrucciones" rows="3"
                                    placeholder="Ej: Acude a cualquier OXXO y proporciona el código de referencia..."></textarea>
                            </div>
                        </div>

                        {{-- credito --}}
                        <div class="config-fields" data-type="credito">
                            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                                <div class="ap-field-group">
                                    <label class="supliers-manager-slider-label">Plazo de crédito</label>
                                    <select class="users-manager-select" name="details[plazo_credito]"
                                        id="pmDetailsPlazoCredito">
                                        <option value="15">15 días</option>
                                        <option value="30">30 días</option>
                                        <option value="45">45 días</option>
                                        <option value="60">60 días</option>
                                    </select>
                                </div>
                                <div class="ap-field-group" style="grid-column: span 2;">
                                    <label class="supliers-manager-slider-label">Línea de crédito máxima (MXN)</label>
                                    <input type="number" class="users-manager-input" name="details[linea_credito]"
                                        id="pmDetailsLineaCredito" min="0" step="0.01" placeholder="Ej: 50000.00">
                                </div>
                            </div>
                            <label class="ap-primary-toggle" for="pmDetailsRequiereAprobacion" style="margin-top:12px;">
                                <input type="checkbox" id="pmDetailsRequiereAprobacion" name="details[requiere_aprobacion]">
                                <div>
                                    <strong>Requiere aprobación</strong>
                                    <span>El pedido queda pendiente de autorización antes de procesarse.</span>
                                </div>
                            </label>
                        </div>

                        {{-- otro --}}
                        <div class="config-fields" data-type="otro">
                            <p class="pm-config-note">Este tipo no tiene campos adicionales de configuración.</p>
                        </div>
                    </div>

                    {{-- Sección 4 — Reglas de disponibilidad --}}
                    <div class="pm-section">
                        <h3 class="pm-section-title">Reglas de disponibilidad</h3>

                        <div class="ap-field-group">
                            <label class="supliers-manager-slider-label">Canales donde está disponible</label>
                            <div class="pm-pill-row">
                                <input type="checkbox" class="pm-pill-input" name="channels[]" id="pmChannelOnline"
                                    value="online">
                                <label class="pm-pill" for="pmChannelOnline">Tienda en línea</label>

                                <input type="checkbox" class="pm-pill-input" name="channels[]" id="pmChannelCotizaciones"
                                    value="cotizaciones">
                                <label class="pm-pill" for="pmChannelCotizaciones">Cotizaciones</label>

                                <input type="checkbox" class="pm-pill-input" name="channels[]" id="pmChannelMostrador"
                                    value="mostrador">
                                <label class="pm-pill" for="pmChannelMostrador">Mostrador</label>
                            </div>
                            <p class="ap-readonly-note">Configuración a futuro — todavía no se integra a ningún flujo
                                automático.</p>
                        </div>

                        <div class="user-manager-form user-manager-form-3 ap-field-grid" style="margin-top:12px;">
                            <div class="ap-field-group">
                                <label class="supliers-manager-slider-label">Monto mínimo (MXN)</label>
                                <input type="number" class="users-manager-input" name="min_amount" id="pmMinAmount"
                                    min="0" step="0.01" placeholder="Sin mínimo">
                            </div>
                            <div class="ap-field-group">
                                <label class="supliers-manager-slider-label">Monto máximo (MXN)</label>
                                <input type="number" class="users-manager-input" name="max_amount" id="pmMaxAmount"
                                    min="0" step="0.01" placeholder="Sin máximo">
                            </div>
                        </div>

                        <label class="ap-primary-toggle" for="pmAllowsInvoicing" style="margin-top:12px;">
                            <input type="checkbox" id="pmAllowsInvoicing" name="allows_invoicing">
                            <div>
                                <strong>Permite facturación CFDI</strong>
                                <span>El cliente puede solicitar factura al pagar con este método.</span>
                            </div>
                        </label>

                        <label class="ap-primary-toggle" for="pmIsActive" style="margin-top:12px;">
                            <input type="checkbox" id="pmIsActive" name="is_active" checked>
                            <div>
                                <strong>Activo</strong>
                                <span>Los métodos inactivos no se ofrecen a los clientes al pagar.</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- ── Columna de vista previa ── --}}
                <aside class="pm-preview-panel" id="pmPreviewPanel">
                    <p class="pm-preview-label">Vista previa</p>
                    <div class="pm-preview-card">
                        <p class="pm-preview-name" id="pmPreviewName">Nombre del método</p>
                        <p class="pm-preview-type" id="pmPreviewType">Transferencia bancaria</p>
                        <p class="pm-preview-desc" id="pmPreviewDesc" style="display:none;"></p>
                        <p class="pm-preview-detail" id="pmPreviewDetail"></p>
                        <span class="pm-preview-invoice-badge" id="pmPreviewInvoiceBadge" style="display:none;">
                            Factura CFDI disponible
                        </span>
                    </div>

                    <p class="pm-preview-label">Validación</p>
                    <ul class="pm-checklist" id="pmChecklist">
                        <li class="pm-checklist-item" data-check="name">
                            <span class="pm-checklist-icon">○</span> Nombre capturado
                        </li>
                        <li class="pm-checklist-item" data-check="detail">
                            <span class="pm-checklist-icon">○</span> Detalle de tipo completo
                        </li>
                        <li class="pm-checklist-item" data-check="channels">
                            <span class="pm-checklist-icon">○</span> Al menos un canal seleccionado
                        </li>
                    </ul>
                </aside>
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelPaymentMethodModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="pmSubmitBtn">Crear Método</button>
            </div>
        </form>
    </div>
</div>
