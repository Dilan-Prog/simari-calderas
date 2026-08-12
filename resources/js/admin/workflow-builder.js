import Sortable from 'sortablejs';

/**
 * Builder de pasos de Workflow (admin > automatizaciones > editar).
 *
 * Lógica movida desde el <script> inline de
 * resources/views/admin/workflows/partials/_step_form_scripts.blade.php
 * (misma funcionalidad, sin cambios de comportamiento):
 *   - abrir/cerrar el modal "+ Agregar paso" / "+ Sub-paso" / "Editar"
 *   - mostrar/ocultar sub-campos según step_type / action_type
 *   - fetch POST/PUT/DELETE contra WorkflowStepController (store/update/destroy)
 *
 * Se agrega además el reordenamiento por drag&drop (SortableJS, mismo
 * patrón que pipeline-board.js) contra WorkflowStepController::reorder
 * (POST .../pasos/reorder con { step_ids: [...] }), ruta que ya existía
 * en routes/admin.php pero no tenía consumidor en el frontend.
 */
(function () {
    'use strict';

    var stepsRoot = document.getElementById('stepsList');
    if (!stepsRoot) return;

    // La vista inyecta la URL base real (admin.workflow-steps.*) en un
    // data-attribute del contenedor para no hardcodear el prefijo de ruta
    // en el JS (mismo criterio que pipeline-board.js con data-move-stage-url-template).
    var stepBaseUrl = stepsRoot.dataset.stepsBaseUrl || '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var modal = document.getElementById('stepModal');
    var modalTitle = document.getElementById('stepModalTitle');
    var modalErrors = document.getElementById('stepModalErrors');

    var stepIdInput = document.getElementById('stepId');
    var stepParentIdInput = document.getElementById('stepParentId');
    var stepTypeSelect = document.getElementById('stepType');
    var stepActionTypeSelect = document.getElementById('stepActionType');
    var stepActionConfigTextarea = document.getElementById('stepActionConfig');
    var stepActionConfigHint = document.getElementById('stepActionConfigHint');
    var stepBranchConditionTextarea = document.getElementById('stepBranchCondition');
    var stepWaitConfigTextarea = document.getElementById('stepWaitConfig');

    var fieldActionType = document.getElementById('fieldActionType');
    var fieldActionConfig = document.getElementById('fieldActionConfig');
    var fieldBranchCondition = document.getElementById('fieldBranchCondition');
    var fieldWaitConfig = document.getElementById('fieldWaitConfig');

    // Placeholders/ejemplo de action_config por action_type.
    var actionConfigExamples = {
        move_deal_stage: '{"stage_id": 5}',
        create_task: '{"title":"...","assigned_to":1}',
        send_email: '{"template_id": 3}',
        update_field: '{"field":"status","value":"contacted"}',
        add_tag: '{"tag":"vip"}',
    };

    function updateActionConfigHint() {
        var actionType = stepActionTypeSelect.value;
        var example = actionConfigExamples[actionType] || '{}';
        stepActionConfigTextarea.placeholder = example;
        stepActionConfigHint.innerHTML = 'Ejemplo para "' + stepActionTypeSelect.options[stepActionTypeSelect.selectedIndex].text + '": <code>' + example + '</code>';
    }

    // Muestra/oculta sub-campos según el step_type elegido.
    function updateVisibleFields() {
        var type = stepTypeSelect.value;

        fieldActionType.style.display = type === 'action' ? '' : 'none';
        fieldActionConfig.style.display = (type === 'action') ? '' : 'none';
        fieldBranchCondition.style.display = type === 'condition' ? '' : 'none';
        fieldWaitConfig.style.display = type === 'wait' ? '' : 'none';

        if (type === 'action') {
            updateActionConfigHint();
        }
    }

    stepTypeSelect.addEventListener('change', updateVisibleFields);
    stepActionTypeSelect.addEventListener('change', updateActionConfigHint);

    function clearModalErrors() {
        modalErrors.style.display = 'none';
        modalErrors.innerHTML = '';
    }

    function resetModalForm() {
        stepIdInput.value = '';
        stepParentIdInput.value = '';
        stepTypeSelect.value = 'action';
        stepActionTypeSelect.value = 'move_deal_stage';
        stepActionConfigTextarea.value = '';
        stepBranchConditionTextarea.value = '';
        stepWaitConfigTextarea.value = '';
        clearModalErrors();
        updateVisibleFields();
    }

    function openModal() {
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    // Abrir modal en modo "crear" (paso raíz)
    var btnAddStep = document.getElementById('btnAddStep');
    if (btnAddStep) {
        btnAddStep.addEventListener('click', function () {
            resetModalForm();
            modalTitle.textContent = 'Agregar paso';
            openModal();
        });
    }

    // Abrir modal en modo "crear sub-paso" (parent_step_id fijo)
    document.querySelectorAll('.wf-step-btn-add-child').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetModalForm();
            stepParentIdInput.value = btn.dataset.parentId;
            modalTitle.textContent = 'Agregar sub-paso';
            openModal();
        });
    });

    // Abrir modal en modo "editar"
    document.querySelectorAll('.wf-step-btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetModalForm();
            stepIdInput.value = btn.dataset.stepId;
            stepTypeSelect.value = btn.dataset.stepType || 'action';
            stepActionTypeSelect.value = btn.dataset.actionType || 'move_deal_stage';
            stepActionConfigTextarea.value = btn.dataset.actionConfig || '';
            stepBranchConditionTextarea.value = btn.dataset.branchCondition || '';
            if (stepTypeSelect.value === 'wait') {
                stepWaitConfigTextarea.value = btn.dataset.actionConfig || '';
            }
            updateVisibleFields();
            modalTitle.textContent = 'Editar paso';
            openModal();
        });
    });

    var closeStepModalBtn = document.getElementById('closeStepModal');
    var cancelStepModalBtn = document.getElementById('cancelStepModal');
    if (closeStepModalBtn) closeStepModalBtn.addEventListener('click', closeModal);
    if (cancelStepModalBtn) cancelStepModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Parsea un textarea JSON opcional. Devuelve {ok, value} — value es
    // null si el textarea está vacío, o un objeto si el JSON es válido.
    function parseJsonField(textarea, label) {
        var raw = textarea.value.trim();
        if (!raw) return { ok: true, value: null };
        try {
            return { ok: true, value: JSON.parse(raw) };
        } catch (e) {
            return { ok: false, error: label + ': el JSON no es válido (' + e.message + ')' };
        }
    }

    var saveStepBtn = document.getElementById('saveStepBtn');
    if (saveStepBtn) {
        saveStepBtn.addEventListener('click', async function () {
            clearModalErrors();

            var type = stepTypeSelect.value;
            var payload = {
                step_type: type,
                parent_step_id: stepParentIdInput.value || null,
                action_type: null,
                action_config: null,
                branch_condition: null,
            };

            var parseErrors = [];

            if (type === 'action') {
                payload.action_type = stepActionTypeSelect.value;
                var actionConfigResult = parseJsonField(stepActionConfigTextarea, 'Configuración de la acción');
                if (!actionConfigResult.ok) {
                    parseErrors.push(actionConfigResult.error);
                } else {
                    payload.action_config = actionConfigResult.value;
                }
            } else if (type === 'wait') {
                var waitConfigResult = parseJsonField(stepWaitConfigTextarea, 'Duración de la espera');
                if (!waitConfigResult.ok) {
                    parseErrors.push(waitConfigResult.error);
                } else {
                    payload.action_config = waitConfigResult.value;
                }
            } else if (type === 'condition') {
                var branchResult = parseJsonField(stepBranchConditionTextarea, 'Condición de la rama');
                if (!branchResult.ok) {
                    parseErrors.push(branchResult.error);
                } else {
                    payload.branch_condition = branchResult.value;
                }
            }

            if (parseErrors.length) {
                modalErrors.innerHTML = parseErrors.map(function (m) { return '<p>' + m + '</p>'; }).join('');
                modalErrors.style.display = 'block';
                return;
            }

            var stepId = stepIdInput.value;
            var isEdit = !!stepId;
            var url = isEdit ? (stepBaseUrl + '/' + stepId) : stepBaseUrl;
            var method = isEdit ? 'PUT' : 'POST';

            try {
                var response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.status === 419) {
                    modalErrors.innerHTML = '<p>Tu sesión expiró. Recarga la página e intenta de nuevo.</p>';
                    modalErrors.style.display = 'block';
                    return;
                }

                var data = await response.json();

                if (response.ok) {
                    closeModal();
                    setTimeout(function () { window.location.reload(); }, 150);
                } else if (response.status === 422) {
                    var errorList = Object.values(data.errors || {}).flat();
                    modalErrors.innerHTML = errorList.map(function (m) { return '<p>' + m + '</p>'; }).join('');
                    modalErrors.style.display = 'block';
                } else {
                    modalErrors.innerHTML = '<p>' + (data.message || 'No se pudo guardar el paso.') + '</p>';
                    modalErrors.style.display = 'block';
                }
            } catch (err) {
                console.error('Error:', err);
                modalErrors.innerHTML = '<p>Ocurrió un error de red. Intenta de nuevo.</p>';
                modalErrors.style.display = 'block';
            }
        });
    }

    // Borrar paso
    document.querySelectorAll('.wf-step-btn-delete').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (!confirm('¿Borrar este paso? Si tiene sub-pasos, revisa que no queden huérfanos.')) return;

            var stepId = btn.dataset.stepId;

            try {
                var response = await fetch(stepBaseUrl + '/' + stepId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                var data = await response.json();

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'No se pudo borrar el paso.');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Ocurrió un error de red. Intenta de nuevo.');
            }
        });
    });

    // ── Reordenamiento drag&drop (SortableJS) ──────────────────────────
    //
    // Cada nivel de anidamiento (la lista raíz #stepsList y cada
    // .wf-step-children de un paso "condición") es su propio contenedor
    // Sortable independiente: al no configurar `group`, SortableJS no
    // permite arrastrar tarjetas entre contenedores distintos, así que un
    // sub-paso no puede "escapar" de su condición padre por accidente.
    // WorkflowStepController::reorder recalcula el campo `order` según el
    // índice de cada id dentro del array recibido, sin tocar parent_step_id.
    function reorderableContainers() {
        var containers = [stepsRoot];
        document.querySelectorAll('.wf-step-children').forEach(function (el) {
            containers.push(el);
        });
        return containers;
    }

    function collectStepIds(container) {
        return Array.from(container.children)
            .filter(function (el) { return el.classList && el.classList.contains('wf-step-card'); })
            .map(function (el) { return el.getAttribute('data-step-id'); })
            .filter(Boolean);
    }

    function sendReorder(stepIds) {
        if (!stepBaseUrl || stepIds.length === 0) return;

        // La ruta de reorder cuelga de admin.workflow-steps.reorder, un
        // nivel arriba de .../pasos (mismo prefijo que store/update/destroy).
        fetch(stepBaseUrl + '/reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ step_ids: stepIds }),
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Error al reordenar los pasos (status ' + response.status + ').');
                }
            })
            .catch(function (err) {
                console.error(err);
                alert('Ocurrió un error de red al reordenar los pasos. Recarga la página.');
            });
    }

    reorderableContainers().forEach(function (container) {
        new Sortable(container, {
            animation: 150,
            draggable: '.wf-step-card',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            chosenClass: 'sortable-chosen',
            onEnd: function (evt) {
                // group no está configurado, así que evt.from === evt.to
                // siempre; si el índice no cambió, no hay nada que enviar.
                if (evt.oldIndex === evt.newIndex) return;
                sendReorder(collectStepIds(evt.to));
            },
        });
    });
})();
