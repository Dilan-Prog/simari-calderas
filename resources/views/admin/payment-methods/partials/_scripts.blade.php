@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón que
        // Marcas): store()=POST base, update()=PUT base/{id} (via _method
        // en FormData), edit()=GET base/{id}/editar, destroy()=DELETE base/{id}.
        const paymentMethodUrl = '{{ url('/admin/metodos-de-pago') }}';
        const paymentMethodModal = document.getElementById('paymentMethodModal');
        const paymentMethodForm = document.getElementById('paymentMethodForm');
        const paymentMethodErrors = document.getElementById('payment-method-modal-errors');
        let currentPaymentMethodId = null;
        let isPaymentMethodEditMode = false;

        const closePaymentMethodModal = () => {
            paymentMethodModal.classList.remove('active');
        };

        // Etiquetas usadas en la vista previa en vivo (columna derecha del
        // modal) — reflejan $typeLabels/procesadores del backend, solo para
        // texto legible, no afectan el envío del formulario.
        const PM_TYPE_LABELS = {
            transferencia: 'Transferencia bancaria',
            pasarela: 'Pasarela de pago',
            referencia: 'Pago por referencia',
            credito: 'Crédito empresarial',
            digital: 'Billetera digital',
            otro: 'Otro',
        };

        const PM_PROCESSOR_LABELS = {
            stripe: 'Stripe',
            conekta: 'Conekta',
            mercadopago: 'Mercado Pago',
            openpay: 'Openpay',
            paypal: 'PayPal',
            clip: 'Clip',
        };

        const PM_VIGENCIA_LABELS = {
            '24h': '24 horas',
            '48h': '48 horas',
            '72h': '72 horas',
            '7dias': '7 días',
        };

        // "digital" comparte exactamente los mismos campos que "pasarela" —
        // un solo bloque .config-fields[data-type="pasarela"] se usa para
        // ambos, este helper resuelve el alias.
        const pmEffectiveType = (type) => (type === 'digital' ? 'pasarela' : type);

        // Los campos bancarios/de detalle solo aplican al tipo activo — se
        // ocultan (no se deshabilitan del todo) para los demás, pero el
        // backend igual acepta que vayan vacíos (mismo criterio que ya
        // usaba togglePmBankFields()).
        const syncPmConfigFields = () => {
            const effectiveType = pmEffectiveType(document.getElementById('pmType').value);
            document.querySelectorAll('.config-fields').forEach(block => {
                block.style.display = block.dataset.type === effectiveType ? 'block' : 'none';
            });
        };

        const updatePmDescriptionCount = () => {
            const el = document.getElementById('pmDescription');
            const countEl = document.getElementById('pmDescriptionCount');
            const remaining = 140 - (el.value ? el.value.length : 0);
            countEl.textContent = `${remaining} caracteres restantes`;
            countEl.classList.toggle('is-limit', remaining <= 10);
        };

        const pmDetailSummary = (rawType) => {
            const type = pmEffectiveType(rawType);
            switch (type) {
                case 'transferencia': {
                    const bank = document.getElementById('pmBankName').value.trim();
                    const clabe = document.getElementById('pmClabe').value.trim();
                    const account = document.getElementById('pmAccountNumber').value.trim();
                    if (bank && clabe) return `${bank} · CLABE terminación ${clabe.slice(-4)}`;
                    if (bank) return bank;
                    if (clabe) return `CLABE terminación ${clabe.slice(-4)}`;
                    if (account) return `Cuenta terminación ${account.slice(-4)}`;
                    return 'Agrega banco, CLABE o número de cuenta';
                }
                case 'pasarela': {
                    const processor = document.getElementById('pmDetailsProcessor').value;
                    const msi = Array.from(document.querySelectorAll('input[name="details[msi][]"]:checked'))
                        .map(el => Number(el.value));
                    let text = processor ? `Vía ${PM_PROCESSOR_LABELS[processor] ?? processor}` : 'Selecciona un procesador';
                    if (msi.length) text += ` · hasta ${Math.max(...msi)} MSI`;
                    return text;
                }
                case 'referencia': {
                    const convenio = document.getElementById('pmDetailsConvenio').value.trim();
                    const vigencia = document.getElementById('pmDetailsVigencia').value;
                    let text = convenio || 'Sin convenio definido';
                    if (vigencia) text += ` · vigencia ${PM_VIGENCIA_LABELS[vigencia] ?? vigencia}`;
                    return text;
                }
                case 'credito': {
                    const plazo = document.getElementById('pmDetailsPlazoCredito').value;
                    const linea = document.getElementById('pmDetailsLineaCredito').value;
                    let text = plazo ? `Plazo a ${plazo} días` : 'Define un plazo de crédito';
                    if (linea) text += ` · línea hasta $${Number(linea).toLocaleString('es-MX')}`;
                    return text;
                }
                default:
                    return 'Sin configuración adicional';
            }
        };

        const pmIsDetailComplete = (rawType) => {
            const type = pmEffectiveType(rawType);
            switch (type) {
                case 'transferencia':
                    return !!(document.getElementById('pmBankName').value.trim() && document.getElementById('pmClabe').value.trim()) ||
                        !!document.getElementById('pmAccountNumber').value.trim();
                case 'pasarela':
                    return !!document.getElementById('pmDetailsProcessor').value;
                case 'referencia':
                    return !!document.getElementById('pmDetailsConvenio').value.trim();
                case 'credito':
                    return !!document.getElementById('pmDetailsPlazoCredito').value;
                default:
                    return true;
            }
        };

        const updatePmChecklist = (type, name) => {
            const items = {
                name: !!name,
                detail: pmIsDetailComplete(type),
                channels: document.querySelectorAll('input[name="channels[]"]:checked').length > 0,
            };
            Object.keys(items).forEach(key => {
                const el = document.querySelector(`.pm-checklist-item[data-check="${key}"]`);
                if (!el) return;
                el.classList.toggle('is-done', items[key]);
                const icon = el.querySelector('.pm-checklist-icon');
                if (icon) icon.textContent = items[key] ? '✓' : '○';
            });
        };

        // Panel de vista previa en vivo — mismo patrón vanilla-JS que
        // updateIvaBreakdown() en create_product/_scripts_create.blade.php:
        // listeners input/change sobre el formulario completo actualizan
        // textContent, sin librería de reactividad.
        const updatePmPreview = () => {
            const type = document.getElementById('pmType').value || 'transferencia';
            const name = document.getElementById('pmName').value.trim();
            const desc = document.getElementById('pmDescription').value.trim();

            document.getElementById('pmPreviewName').textContent = name || 'Nombre del método';
            document.getElementById('pmPreviewType').textContent = PM_TYPE_LABELS[type] ?? type;

            const descEl = document.getElementById('pmPreviewDesc');
            descEl.textContent = desc;
            descEl.style.display = desc ? '' : 'none';

            document.getElementById('pmPreviewDetail').textContent = pmDetailSummary(type);

            const allowsInvoicing = document.getElementById('pmAllowsInvoicing').checked;
            document.getElementById('pmPreviewInvoiceBadge').style.display = allowsInvoicing ? '' : 'none';

            updatePmChecklist(type, name);
        };

        const resetPaymentMethodForm = () => {
            paymentMethodForm.reset();
            document.getElementById('pmType').value = 'transferencia';
            document.querySelectorAll('.pm-type-card').forEach(card => {
                card.classList.toggle('active', card.dataset.type === 'transferencia');
            });
            document.getElementById('pmDetailsMoneda').value = 'MXN';
            document.getElementById('pmDetailsVigencia').value = '24h';
            document.getElementById('pmIsActive').checked = true;
            paymentMethodErrors.style.display = 'none';
            paymentMethodErrors.innerHTML = '';
            currentPaymentMethodId = null;
            isPaymentMethodEditMode = false;
            document.getElementById('paymentMethodModalTitle').textContent = 'Nuevo Método de Pago';
            document.getElementById('pmSubmitBtn').textContent = 'Crear Método';
            syncPmConfigFields();
            updatePmDescriptionCount();
            updatePmPreview();
        };

        // Selección exclusiva de tarjeta de tipo (Sección 1) — mismo patrón
        // que .pform-badge-card/.pform-avail-btn en product-form.css: quita
        // "active" de las hermanas, la agrega a la clicada, sincroniza el
        // input oculto #pmType.
        document.querySelectorAll('.pm-type-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.pm-type-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                document.getElementById('pmType').value = card.dataset.type;
                syncPmConfigFields();
                updatePmPreview();
            });
        });

        // Cualquier cambio en el formulario refresca el contador de
        // descripción + el panel de vista previa/checklist.
        paymentMethodForm.addEventListener('input', () => {
            updatePmDescriptionCount();
            updatePmPreview();
        });
        paymentMethodForm.addEventListener('change', () => {
            updatePmDescriptionCount();
            updatePmPreview();
        });

        // Open create
        document.getElementById('btnNewPaymentMethod').addEventListener('click', () => {
            resetPaymentMethodForm();
            paymentMethodModal.classList.add('active');
        });

        // Close
        document.getElementById('closePaymentMethodModal').addEventListener('click', closePaymentMethodModal);
        document.getElementById('cancelPaymentMethodModal').addEventListener('click', closePaymentMethodModal);
        paymentMethodModal.addEventListener('click', (e) => {
            if (e.target === paymentMethodModal) closePaymentMethodModal();
        });

        const showPmFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const clearPmFieldErrors = () => {
            document.querySelectorAll('#paymentMethodForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#paymentMethodForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };

        // Mapa usado para mostrar errores de validación del servidor junto
        // al campo correspondiente — incluye las claves anidadas que
        // Laravel devuelve tal cual (details.processor, channels, etc.).
        const fieldIdByName = {
            type: 'pmType',
            name: 'pmName',
            sort_order: 'pmSortOrder',
            description: 'pmDescription',
            bank_name: 'pmBankName',
            clabe: 'pmClabe',
            account_number: 'pmAccountNumber',
            beneficiary_name: 'pmBeneficiaryName',
            min_amount: 'pmMinAmount',
            max_amount: 'pmMaxAmount',
            allows_invoicing: 'pmAllowsInvoicing',
            channels: 'pmChannelOnline',
            'details.moneda': 'pmDetailsMoneda',
            'details.swift_aba': 'pmDetailsSwiftAba',
            'details.processor': 'pmDetailsProcessor',
            'details.comision_percent': 'pmDetailsComisionPercent',
            'details.comision_fija': 'pmDetailsComisionFija',
            'details.llave_publica': 'pmDetailsLlavePublica',
            'details.convenio': 'pmDetailsConvenio',
            'details.vigencia': 'pmDetailsVigencia',
            'details.instrucciones': 'pmDetailsInstrucciones',
            'details.plazo_credito': 'pmDetailsPlazoCredito',
            'details.linea_credito': 'pmDetailsLineaCredito',
        };

        // Submit (create/update)
        paymentMethodForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearPmFieldErrors();
            paymentMethodErrors.style.display = 'none';
            paymentMethodErrors.innerHTML = '';

            const formData = new FormData(paymentMethodForm);
            // Los checkboxes no marcados no se envían en FormData — se fija
            // explícitamente para que el backend siempre reciba el valor.
            formData.set('is_active', document.getElementById('pmIsActive').checked ? '1' : '0');
            formData.set('allows_invoicing', document.getElementById('pmAllowsInvoicing').checked ? '1' : '0');

            const url = isPaymentMethodEditMode ?
                `${paymentMethodUrl}/${currentPaymentMethodId}` :
                paymentMethodUrl;

            if (isPaymentMethodEditMode) formData.append('_method', 'PUT');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.status === 419) {
                    paymentMethodErrors.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    paymentMethodErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closePaymentMethodModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    paymentMethodErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    paymentMethodErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (fieldIdByName[field]) {
                            showPmFieldError(fieldIdByName[field], data.errors[field][0]);
                        }
                    });
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit
        document.querySelectorAll('.btn-edit-payment-method').forEach(btn => {
            btn.addEventListener('click', () => {
                currentPaymentMethodId = btn.dataset.id;
                isPaymentMethodEditMode = true;

                fetch(`${paymentMethodUrl}/${currentPaymentMethodId}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(pm => {
                        resetPaymentMethodForm();
                        isPaymentMethodEditMode = true;
                        currentPaymentMethodId = pm.id;

                        document.getElementById('paymentMethodModalTitle').textContent = 'Editar Método de Pago';
                        document.getElementById('pmSubmitBtn').textContent = 'Guardar Cambios';

                        const type = pm.type ?? 'transferencia';
                        document.getElementById('pmType').value = type;
                        document.querySelectorAll('.pm-type-card').forEach(card => {
                            card.classList.toggle('active', card.dataset.type === type);
                        });

                        document.getElementById('pmName').value = pm.name ?? '';
                        document.getElementById('pmSortOrder').value = pm.sort_order ?? 0;
                        document.getElementById('pmDescription').value = pm.description ?? '';
                        document.getElementById('pmBankName').value = pm.bank_name ?? '';
                        document.getElementById('pmClabe').value = pm.clabe ?? '';
                        document.getElementById('pmAccountNumber').value = pm.account_number ?? '';
                        document.getElementById('pmBeneficiaryName').value = pm.beneficiary_name ?? '';
                        document.getElementById('pmMinAmount').value = pm.min_amount ?? '';
                        document.getElementById('pmMaxAmount').value = pm.max_amount ?? '';
                        document.getElementById('pmAllowsInvoicing').checked = !!pm.allows_invoicing;
                        document.getElementById('pmIsActive').checked = !!pm.is_active;

                        const details = pm.details ?? {};
                        document.getElementById('pmDetailsMoneda').value = details.moneda ?? 'MXN';
                        document.getElementById('pmDetailsSwiftAba').value = details.swift_aba ?? '';
                        document.getElementById('pmDetailsProcessor').value = details.processor ?? '';
                        document.getElementById('pmDetailsComisionPercent').value = details.comision_percent ?? '';
                        document.getElementById('pmDetailsComisionFija').value = details.comision_fija ?? '';
                        document.getElementById('pmDetailsLlavePublica').value = details.llave_publica ?? '';
                        document.getElementById('pmDetailsCapturaManual').checked = !!details.captura_manual;

                        const msiValues = Array.isArray(details.msi) ? details.msi.map(String) : [];
                        document.querySelectorAll('input[name="details[msi][]"]').forEach(cb => {
                            cb.checked = msiValues.includes(cb.value);
                        });

                        document.getElementById('pmDetailsConvenio').value = details.convenio ?? '';
                        document.getElementById('pmDetailsVigencia').value = details.vigencia ?? '24h';
                        document.getElementById('pmDetailsInstrucciones').value = details.instrucciones ?? '';
                        document.getElementById('pmDetailsPlazoCredito').value = details.plazo_credito ?? '30';
                        document.getElementById('pmDetailsLineaCredito').value = details.linea_credito ?? '';
                        document.getElementById('pmDetailsRequiereAprobacion').checked = !!details.requiere_aprobacion;

                        const channels = Array.isArray(pm.channels) ? pm.channels : [];
                        document.querySelectorAll('input[name="channels[]"]').forEach(cb => {
                            cb.checked = channels.includes(cb.value);
                        });

                        syncPmConfigFields();
                        updatePmDescriptionCount();
                        updatePmPreview();

                        paymentMethodModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // Delete
        const deletePaymentMethodModal = document.getElementById('deletePaymentMethodModal');
        let deletePaymentMethodId = null;

        document.querySelectorAll('.btn-delete-payment-method').forEach(btn => {
            btn.addEventListener('click', () => {
                deletePaymentMethodId = btn.dataset.id;
                document.getElementById('delPaymentMethodName').textContent = btn.dataset.name;
                document.getElementById('delPaymentMethodAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deletePaymentMethodModal.classList.add('active');
            });
        });

        document.getElementById('delPaymentMethodCancel').addEventListener('click', () =>
            deletePaymentMethodModal.classList.remove('active'));
        deletePaymentMethodModal.addEventListener('click', (e) => {
            if (e.target === deletePaymentMethodModal) deletePaymentMethodModal.classList.remove('active');
        });

        document.getElementById('delPaymentMethodConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${paymentMethodUrl}/${deletePaymentMethodId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    deletePaymentMethodModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar el método de pago.');
                    deletePaymentMethodModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Estado inicial al cargar la página (antes de abrir el modal por
        // primera vez), para que el bloque de tipo/preview no arranque vacío.
        syncPmConfigFields();
        updatePmDescriptionCount();
        updatePmPreview();
    </script>
@endpush
