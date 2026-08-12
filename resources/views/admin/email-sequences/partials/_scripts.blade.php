{{--
    Lógica del builder de pasos: agregar un paso nuevo (fetch POST contra
    EmailSequenceController::addStep) y quitar un paso existente (fetch
    DELETE contra EmailSequenceController::removeStep). El controlador
    responde con un redirect normal (no JSON), así que tras cada fetch
    exitoso simplemente recargamos la página para reflejar el estado
    persistido en servidor.
--}}
@push('scripts')
<script>
(function () {
    const stepsList     = document.getElementById('stepsList');
    const btnAddStep    = document.getElementById('btnAddStep');
    const stepsEmptyMsg = document.getElementById('stepsEmptyMsg');

    if (!stepsList || !btnAddStep) {
        return;
    }

    const addStepUrl      = stepsList.dataset.addStepUrl;
    const removeStepBase  = stepsList.dataset.removeStepBaseUrl;
    const csrfToken       = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;

    const templateOptions = @json($templates->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values());

    function buildNewStepRow() {
        const row = document.createElement('div');
        row.className = 'es-step-card';
        row.id = 'newStepRow';

        const optionsHtml = templateOptions
            .map((t) => `<option value="${t.id}">${t.name}</option>`)
            .join('');

        row.innerHTML = `
            <span class="es-step-order">+</span>
            <div class="es-step-fields">
                <div class="es-step-field">
                    <label>Plantilla</label>
                    <select class="es-step-select" id="newStepTemplateId">
                        <option value="">Selecciona una plantilla</option>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="es-step-field">
                    <label>Días de espera</label>
                    <input type="number" class="es-step-number" id="newStepDelayDays" value="0" min="0">
                </div>
            </div>
            <button type="button" class="es-btn-add-step" id="saveNewStepBtn">Guardar</button>
            <button type="button" class="es-step-btn-remove" id="cancelNewStepBtn">Cancelar</button>
        `;

        return row;
    }

    btnAddStep.addEventListener('click', function () {
        if (document.getElementById('newStepRow')) {
            return;
        }

        if (stepsEmptyMsg) {
            stepsEmptyMsg.remove();
        }

        const row = buildNewStepRow();
        stepsList.appendChild(row);
        btnAddStep.disabled = true;

        document.getElementById('cancelNewStepBtn').addEventListener('click', function () {
            row.remove();
            btnAddStep.disabled = false;
        });

        document.getElementById('saveNewStepBtn').addEventListener('click', function () {
            const templateId = document.getElementById('newStepTemplateId').value;
            const delayDays  = document.getElementById('newStepDelayDays').value || 0;

            if (!templateId) {
                alert('Selecciona una plantilla para el paso.');
                return;
            }

            const formData = new FormData();
            formData.append('template_id', templateId);
            formData.append('delay_days', delayDays);
            formData.append('_token', csrfToken);

            fetch(addStepUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('No se pudo agregar el paso.');
                    }
                    window.location.reload();
                })
                .catch((error) => {
                    alert(error.message || 'No se pudo agregar el paso.');
                });
        });
    });

    stepsList.addEventListener('click', function (event) {
        const btn = event.target.closest('.es-step-btn-remove[data-step-id]');
        if (!btn) {
            return;
        }

        if (!confirm('¿Quitar este paso de la secuencia?')) {
            return;
        }

        const stepId = btn.dataset.stepId;

        fetch(`${removeStepBase}/${stepId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-HTTP-Method-Override': 'DELETE',
            },
            body: (() => {
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('_method', 'DELETE');
                return formData;
            })(),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('No se pudo quitar el paso.');
                }
                window.location.reload();
            })
            .catch((error) => {
                alert(error.message || 'No se pudo quitar el paso.');
            });
    });
})();
</script>
@endpush
