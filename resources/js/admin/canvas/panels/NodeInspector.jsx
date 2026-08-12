import { useEffect, useState } from 'react';

const TRIGGER_TYPE_OPTIONS = {
    deal: 'Negociación',
    contact: 'Contacto',
    company: 'Empresa',
    date_based: 'Basado en fecha',
};

/**
 * NodeInspector
 *
 * Panel lateral para editar el step seleccionado en el canvas. La forma del
 * formulario depende de `step.step_type`:
 *   - 'trigger'   -> select de tipo + textarea JSON crudo para enrollment_trigger
 *   - 'action'    -> select de action_type + textarea JSON crudo para action_config
 *   - 'condition' -> field / operator / value simples que se combinan en branch_condition
 *   - 'wait'      -> amount (number) + unit (select) que se combinan en action_config
 *
 * Props:
 *   step:          objeto step actual (o null -> el panel no renderiza nada)
 *   catalog:       { action_types: {key: {label, example_config}}, condition_operators: {key: label}, wait_units: {key: label} }
 *   onSave:        (stepId, payload) => void
 *   onSaveTrigger: ({ type, enrollment_trigger }) => void — se invoca en vez de
 *                  onSave cuando step.step_type === 'trigger'.
 *   onDelete:      (stepId) => void
 *   onClose:       () => void
 */
const STEP_TYPE_LABELS = {
    trigger: 'Trigger',
    action: 'Acción',
    condition: 'Condición',
    wait: 'Espera',
};

function initialsForStepType(stepType) {
    if (stepType === 'trigger') return 'TR';
    if (stepType === 'action') return 'AC';
    if (stepType === 'condition') return 'CO';
    if (stepType === 'wait') return 'ES';
    return '--';
}

export default function NodeInspector({ step, catalog, onSave, onSaveTrigger, onDelete, onClose }) {
    const [inspectorTab, setInspectorTab] = useState('params');
    const [actionType, setActionType] = useState('');
    const [actionConfigText, setActionConfigText] = useState('');
    const [conditionField, setConditionField] = useState('');
    const [conditionOperator, setConditionOperator] = useState('');
    const [conditionValue, setConditionValue] = useState('');
    const [waitAmount, setWaitAmount] = useState('');
    const [waitUnit, setWaitUnit] = useState('');
    const [triggerType, setTriggerType] = useState('');
    const [triggerConfigText, setTriggerConfigText] = useState('');
    const [error, setError] = useState('');

    // Re-inicializa el formulario cada vez que cambia el step seleccionado.
    useEffect(() => {
        if (!step) return;

        setError('');
        setInspectorTab('params');

        if (step.step_type === 'action') {
            setActionType(step.action_type || '');
            setActionConfigText(
                step.action_config != null ? JSON.stringify(step.action_config, null, 2) : ''
            );
        } else if (step.step_type === 'condition') {
            const cond = step.branch_condition || {};
            setConditionField(cond.field ?? '');
            setConditionOperator(cond.operator ?? '');
            setConditionValue(cond.value ?? '');
        } else if (step.step_type === 'wait') {
            const cfg = step.action_config || {};
            setWaitAmount(cfg.amount ?? '');
            setWaitUnit(cfg.unit ?? '');
        } else if (step.step_type === 'trigger') {
            setTriggerType(step.type || step.workflowType || '');
            setTriggerConfigText(
                step.enrollment_trigger != null ? JSON.stringify(step.enrollment_trigger, null, 2) : ''
            );
        }
    }, [step]);

    if (!step) return null;

    const actionTypes = catalog?.action_types || {};
    const conditionOperators = catalog?.condition_operators || {};
    const waitUnits = catalog?.wait_units || {};

    function handleActionTypeChange(e) {
        const selected = e.target.value;
        setActionType(selected);
        setError('');

        // Sólo autocompleta con el ejemplo si el usuario todavía no escribió nada.
        if (actionConfigText.trim() === '') {
            const example = actionTypes[selected]?.example_config;
            if (example !== undefined) {
                setActionConfigText(JSON.stringify(example, null, 2));
            }
        }
    }

    function fillExample() {
        const example = actionTypes[actionType]?.example_config;
        if (example !== undefined) {
            setActionConfigText(JSON.stringify(example, null, 2));
        }
    }

    function handleSave() {
        setError('');

        if (step.step_type === 'trigger') {
            let parsedTrigger = {};
            const trimmed = triggerConfigText.trim();
            if (trimmed !== '') {
                try {
                    parsedTrigger = JSON.parse(trimmed);
                } catch (e) {
                    setError('El JSON del trigger no es válido. Revísalo antes de guardar.');
                    return;
                }
            }
            onSaveTrigger({
                type: triggerType,
                enrollment_trigger: parsedTrigger,
            });
            return;
        }

        if (step.step_type === 'action') {
            let parsedConfig = {};
            const trimmed = actionConfigText.trim();
            if (trimmed !== '') {
                try {
                    parsedConfig = JSON.parse(trimmed);
                } catch (e) {
                    setError('El JSON de configuración no es válido. Revísalo antes de guardar.');
                    return;
                }
            }
            onSave(step.id, {
                action_type: actionType,
                action_config: parsedConfig,
            });
            return;
        }

        if (step.step_type === 'condition') {
            if (!conditionField.trim()) {
                setError('El campo de la condición no puede estar vacío.');
                return;
            }
            if (!conditionOperator) {
                setError('Selecciona un operador para la condición.');
                return;
            }
            onSave(step.id, {
                branch_condition: {
                    field: conditionField,
                    operator: conditionOperator,
                    value: conditionValue,
                },
            });
            return;
        }

        if (step.step_type === 'wait') {
            const amountNumber = Number(waitAmount);
            if (waitAmount === '' || Number.isNaN(amountNumber)) {
                setError('Indica una cantidad numérica válida para la espera.');
                return;
            }
            if (!waitUnit) {
                setError('Selecciona una unidad de tiempo.');
                return;
            }
            onSave(step.id, {
                action_config: {
                    unit: waitUnit,
                    amount: amountNumber,
                },
            });
            return;
        }
    }

    function handleDelete() {
        if (confirm('¿Eliminar este paso? Esta acción no se puede deshacer.')) {
            onDelete(step.id);
        }
    }

    const typeLabel = STEP_TYPE_LABELS[step.step_type] || step.step_type;

    return (
        <div className="wf-canvas-inspector wf-inspector">
            <div className="wf-inspector-header">
                <span className="wf-inspector-avatar">{initialsForStepType(step.step_type)}</span>
                <div className="wf-inspector-titles">
                    <div className="wf-inspector-title">Editar paso</div>
                    <div className="wf-inspector-subtitle">{typeLabel} · #{step.id}</div>
                </div>
                <button
                    type="button"
                    className="wf-inspector-close"
                    onClick={onClose}
                    aria-label="Cerrar"
                    title="Cerrar"
                >
                    ×
                </button>
            </div>

            <div className="wf-inspector-tabs">
                {[
                    ['params', 'Parámetros'],
                    ['credentials', 'Credenciales'],
                    ['input', 'Input'],
                    ['output', 'Output'],
                ].map(([key, label]) => (
                    <button
                        type="button"
                        key={key}
                        className={`wf-inspector-tab ${inspectorTab === key ? 'is-active' : ''}`}
                        onClick={() => setInspectorTab(key)}
                    >
                        {label}
                    </button>
                ))}
            </div>

            <div className="wf-inspector-body">
                {inspectorTab !== 'params' && (
                    <div className="wf-inspector-empty-tab">
                        {inspectorTab === 'credentials' && 'Este tipo de nodo no requiere credenciales.'}
                        {inspectorTab === 'input' && 'El input de este paso aparecerá aquí después de ejecutar una prueba.'}
                        {inspectorTab === 'output' && 'El output de este paso aparecerá aquí después de ejecutar una prueba.'}
                    </div>
                )}

                {inspectorTab === 'params' && step.step_type === 'trigger' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-trigger-type">
                            Tipo
                        </label>
                        <select
                            id="wf-trigger-type"
                            className="wf-field-input"
                            value={triggerType}
                            onChange={(e) => setTriggerType(e.target.value)}
                        >
                            <option value="">-- Selecciona --</option>
                            {Object.entries(TRIGGER_TYPE_OPTIONS).map(([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ))}
                        </select>

                        <label className="wf-field-label" htmlFor="wf-trigger-config">
                            Trigger de inscripción (JSON)
                        </label>
                        <textarea
                            id="wf-trigger-config"
                            className="wf-field-input wf-field-textarea"
                            rows={10}
                            value={triggerConfigText}
                            onChange={(e) => setTriggerConfigText(e.target.value)}
                            spellCheck={false}
                        />
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'action' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-action-type">
                            Tipo de acción
                        </label>
                        <select
                            id="wf-action-type"
                            className="wf-field-input"
                            value={actionType}
                            onChange={handleActionTypeChange}
                        >
                            <option value="">-- Selecciona --</option>
                            {Object.entries(actionTypes).map(([key, def]) => (
                                <option key={key} value={key}>
                                    {def.label}
                                </option>
                            ))}
                        </select>

                        <div className="wf-field-row-header">
                            <label className="wf-field-label" htmlFor="wf-action-config">
                                Configuración (JSON)
                            </label>
                            <button
                                type="button"
                                className="wf-btn-link"
                                onClick={fillExample}
                                disabled={!actionType}
                            >
                                Ver ejemplo
                            </button>
                        </div>
                        <textarea
                            id="wf-action-config"
                            className="wf-field-input wf-field-textarea"
                            rows={10}
                            value={actionConfigText}
                            onChange={(e) => setActionConfigText(e.target.value)}
                            spellCheck={false}
                        />
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'condition' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-cond-field">
                            Campo
                        </label>
                        <input
                            id="wf-cond-field"
                            type="text"
                            className="wf-field-input"
                            value={conditionField}
                            onChange={(e) => setConditionField(e.target.value)}
                            placeholder="p. ej. deal.stage"
                        />

                        <label className="wf-field-label" htmlFor="wf-cond-operator">
                            Operador
                        </label>
                        <select
                            id="wf-cond-operator"
                            className="wf-field-input"
                            value={conditionOperator}
                            onChange={(e) => setConditionOperator(e.target.value)}
                        >
                            <option value="">-- Selecciona --</option>
                            {Object.entries(conditionOperators).map(([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ))}
                        </select>

                        <label className="wf-field-label" htmlFor="wf-cond-value">
                            Valor
                        </label>
                        <input
                            id="wf-cond-value"
                            type="text"
                            className="wf-field-input"
                            value={conditionValue}
                            onChange={(e) => setConditionValue(e.target.value)}
                        />
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'wait' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-wait-amount">
                            Cantidad
                        </label>
                        <input
                            id="wf-wait-amount"
                            type="number"
                            className="wf-field-input"
                            value={waitAmount}
                            onChange={(e) => setWaitAmount(e.target.value)}
                            min="0"
                        />

                        <label className="wf-field-label" htmlFor="wf-wait-unit">
                            Unidad
                        </label>
                        <select
                            id="wf-wait-unit"
                            className="wf-field-input"
                            value={waitUnit}
                            onChange={(e) => setWaitUnit(e.target.value)}
                        >
                            <option value="">-- Selecciona --</option>
                            {Object.entries(waitUnits).map(([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </>
                )}

                {error && <div className="wf-inspector-error">{error}</div>}
            </div>

            <div className="wf-inspector-footer">
                {step.step_type !== 'trigger' && (
                    <button type="button" className="wf-btn wf-btn-danger" onClick={handleDelete}>
                        Eliminar paso
                    </button>
                )}
                <button type="button" className="wf-btn wf-btn-primary" onClick={handleSave}>
                    Guardar
                </button>
            </div>
        </div>
    );
}
