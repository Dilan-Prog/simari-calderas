@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón que
        // Métodos de Pago): store()=POST base, update()=PUT base/{id},
        // destroy()=DELETE base/{id}, reorderStages()=POST base/{id}/reorder-stages,
        // deleteStage()=DELETE pipelines/stages/{stage}.
        const pipelineBaseUrl = '{{ url('/admin/pipelines') }}';
        const pipelineModal = document.getElementById('pipelineModal');
        const pipelineForm = document.getElementById('pipelineForm');
        const pipelineErrors = document.getElementById('pipeline-modal-errors');
        const pipelineStagesBuilder = document.getElementById('pipelineStagesBuilder');
        const pipelineStagesBuilderSection = document.getElementById('pipelineStagesBuilderSection');
        const pipelineStageRowTemplate = document.getElementById('pipelineStageRowTemplate');
        const pipelineChannelField = document.getElementById('pipelineChannelField');
        const pipelineChannelReadonly = document.getElementById('pipelineChannelReadonly');
        const pipelineChannelReadonlyValue = document.getElementById('pipelineChannelReadonlyValue');
        const pipelineChannelSelect = document.getElementById('pipelineChannel');
        const pipelineChannelLabels = {
            deals: 'Negocios',
            whatsapp: 'Embudo de Venta de WhatsApp',
        };
        let currentPipelineId = null;
        let isPipelineEditMode = false;

        const closePipelineModal = () => {
            pipelineModal.classList.remove('active');
        };

        const clearPipelineFieldErrors = () => {
            document.querySelectorAll('#pipelineForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#pipelineForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };

        // Builder de etapas iniciales — inputs repetibles agregados en JS
        // vanilla (sin librerías), clonando <template id="pipelineStageRowTemplate">.
        // Solo se usa en modo creación; update() del controlador no acepta "stages".
        const addPipelineStageRow = () => {
            const fragment = pipelineStageRowTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.pipeline-stage-row');
            row.querySelector('.btn-remove-pipeline-stage').addEventListener('click', () => row.remove());
            pipelineStagesBuilder.appendChild(row);
        };

        document.getElementById('btnAddPipelineStage').addEventListener('click', addPipelineStageRow);

        const resetPipelineStagesBuilder = () => {
            pipelineStagesBuilder.innerHTML = '';
        };

        const collectPipelineStagesFromBuilder = () => {
            const rows = pipelineStagesBuilder.querySelectorAll('.pipeline-stage-row');
            const stages = [];
            rows.forEach((row, index) => {
                const name = row.querySelector('.pipeline-stage-name').value.trim();
                if (!name) return;
                const probabilityRaw = row.querySelector('.pipeline-stage-probability').value;
                stages.push({
                    name,
                    order: index,
                    probability: probabilityRaw === '' ? null : Number(probabilityRaw),
                    is_won: row.querySelector('.pipeline-stage-is-won').checked,
                    is_lost: row.querySelector('.pipeline-stage-is-lost').checked,
                });
            });
            return stages;
        };

        const resetPipelineForm = () => {
            pipelineForm.reset();
            pipelineErrors.style.display = 'none';
            pipelineErrors.innerHTML = '';
            clearPipelineFieldErrors();
            currentPipelineId = null;
            isPipelineEditMode = false;
            document.getElementById('pipelineIsActive').checked = true;
            document.getElementById('pipelineModalTitle').textContent = 'Nuevo Pipeline';
            document.getElementById('pipelineSubmitBtn').textContent = 'Crear Pipeline';
            pipelineStagesBuilderSection.style.display = 'block';
            resetPipelineStagesBuilder();
            pipelineChannelSelect.value = 'deals';
            pipelineChannelField.style.display = 'block';
            pipelineChannelReadonly.style.display = 'none';
        };

        // Open create
        document.getElementById('btnNewPipeline').addEventListener('click', () => {
            resetPipelineForm();
            addPipelineStageRow();
            pipelineModal.classList.add('active');
        });

        // Close
        document.getElementById('closePipelineModal').addEventListener('click', closePipelineModal);
        document.getElementById('cancelPipelineModal').addEventListener('click', closePipelineModal);
        pipelineModal.addEventListener('click', (e) => {
            if (e.target === pipelineModal) closePipelineModal();
        });

        const showPipelineFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const fieldIdByName = {
            name: 'pipelineName',
            is_default: 'pipelineIsDefault',
            is_active: 'pipelineIsActive',
        };

        // Submit (create/update)
        pipelineForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearPipelineFieldErrors();
            pipelineErrors.style.display = 'none';
            pipelineErrors.innerHTML = '';

            const payload = {
                name: document.getElementById('pipelineName').value.trim(),
                is_default: document.getElementById('pipelineIsDefault').checked,
                is_active: document.getElementById('pipelineIsActive').checked,
            };

            if (!isPipelineEditMode) {
                payload.channel = pipelineChannelSelect.value;
                payload.stages = collectPipelineStagesFromBuilder();
            }

            const url = isPipelineEditMode ? `${pipelineBaseUrl}/${currentPipelineId}` : pipelineBaseUrl;
            const method = isPipelineEditMode ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.status === 419) {
                    pipelineErrors.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    pipelineErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closePipelineModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    pipelineErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    pipelineErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (fieldIdByName[field]) {
                            showPipelineFieldError(fieldIdByName[field], data.errors[field][0]);
                        }
                    });
                } else {
                    pipelineErrors.innerHTML = `<p>${data.message ?? 'No se pudo guardar el pipeline.'}</p>`;
                    pipelineErrors.style.display = 'block';
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit — no hay endpoint GET /editar en el controlador, así que los
        // valores actuales se leen directo de los data-* del botón de la fila.
        document.querySelectorAll('.btn-edit-pipeline').forEach(btn => {
            btn.addEventListener('click', () => {
                resetPipelineForm();
                isPipelineEditMode = true;
                currentPipelineId = btn.dataset.id;

                document.getElementById('pipelineModalTitle').textContent = 'Editar Pipeline';
                document.getElementById('pipelineSubmitBtn').textContent = 'Guardar Cambios';
                pipelineStagesBuilderSection.style.display = 'none';

                document.getElementById('pipelineName').value = btn.dataset.name ?? '';
                document.getElementById('pipelineIsDefault').checked = btn.dataset.isDefault === '1';
                document.getElementById('pipelineIsActive').checked = btn.dataset.isActive === '1';

                // El tipo de pipeline es inmutable: se muestra como texto de
                // solo lectura en vez del <select>, y nunca se manda en el payload.
                pipelineChannelField.style.display = 'none';
                pipelineChannelReadonly.style.display = 'block';
                pipelineChannelReadonlyValue.textContent = pipelineChannelLabels[btn.dataset.channel] ?? btn.dataset.channel ?? '—';

                pipelineModal.classList.add('active');
            });
        });

        // Toggle panel de etapas por pipeline
        document.querySelectorAll('.btn-toggle-stages').forEach(btn => {
            btn.addEventListener('click', () => {
                const panel = document.getElementById(`pipelineStagesPanel-${btn.dataset.id}`);
                if (!panel) return;
                const isOpen = panel.style.display !== 'none';
                panel.style.display = isOpen ? 'none' : 'block';
                btn.classList.toggle('is-open', !isOpen);
            });
        });

        // Delete pipeline
        const deletePipelineModal = document.getElementById('deletePipelineModal');
        let deletePipelineId = null;
        let deleteTargetType = null; // 'pipeline' | 'stage'

        document.querySelectorAll('.btn-delete-pipeline').forEach(btn => {
            btn.addEventListener('click', () => {
                deletePipelineId = btn.dataset.id;
                deleteTargetType = 'pipeline';
                document.getElementById('deletePipelineTitle').textContent = '¿Eliminar pipeline?';
                document.getElementById('delPipelineName').textContent = btn.dataset.name;
                document.getElementById('delPipelineAvatar').textContent = btn.dataset.name.charAt(0).toUpperCase();
                deletePipelineModal.classList.add('active');
            });
        });

        // Delete stage
        document.querySelectorAll('.btn-delete-stage').forEach(btn => {
            btn.addEventListener('click', () => {
                deletePipelineId = btn.dataset.id;
                deleteTargetType = 'stage';
                document.getElementById('deletePipelineTitle').textContent = '¿Eliminar etapa?';
                document.getElementById('delPipelineName').textContent = btn.dataset.name;
                document.getElementById('delPipelineAvatar').textContent = btn.dataset.name.charAt(0).toUpperCase();
                deletePipelineModal.classList.add('active');
            });
        });

        document.getElementById('delPipelineCancel').addEventListener('click', () =>
            deletePipelineModal.classList.remove('active'));
        deletePipelineModal.addEventListener('click', (e) => {
            if (e.target === deletePipelineModal) deletePipelineModal.classList.remove('active');
        });

        document.getElementById('delPipelineConfirm').addEventListener('click', async () => {
            const url = deleteTargetType === 'stage' ?
                `${pipelineBaseUrl}/stages/${deletePipelineId}` :
                `${pipelineBaseUrl}/${deletePipelineId}`;

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    deletePipelineModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar.');
                    deletePipelineModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Reorder de etapas — la vista actual solo lista las etapas en orden
        // (sin drag&drop, no es prioritario según el diseño del panel), pero
        // se deja lista la función de fetch para cuando el kanban de Negocios
        // (u otra vista futura) necesite invocarla.
        const reorderPipelineStages = async (pipelineId, stageIds) => {
            try {
                const response = await fetch(`${pipelineBaseUrl}/${pipelineId}/reorder-stages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        stage_ids: stageIds
                    }),
                });
                return await response.json();
            } catch (err) {
                console.error('Error:', err);
                return {
                    success: false
                };
            }
        };
    </script>
@endpush
