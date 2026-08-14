import { useEffect, useState } from 'react';
import { listDatabaseCredentials, listDatabaseTables, listDatabaseColumns } from '../api/stepsApi.js';
import TokenAutocompleteTextarea from './TokenAutocomplete.jsx';
import FieldAutocompleteInput from './FieldAutocompleteInput.jsx';
import { TRIGGER_SCHEMA, ACTION_CONFIG_SCHEMAS, EXTERNAL_DB_SCHEMA } from './nodeConfigSchemas.js';

/**
 * Agrupa catalog.modules (Fase 18) por su `group` (Ecommerce/Servicios/ERP/CRM)
 * en el orden fijo de abajo, para pintar <optgroup> en el selector de módulo
 * del Trigger.
 */
const MODULE_GROUP_ORDER = ['Ecommerce', 'Servicios', 'ERP', 'CRM'];

function groupModules(modules) {
    const list = Array.isArray(modules) ? modules : [];
    const groups = new Map();

    list.forEach((m) => {
        const key = m.group || 'Otro';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(m);
    });

    const orderedKeys = [
        ...MODULE_GROUP_ORDER.filter((g) => groups.has(g)),
        ...[...groups.keys()].filter((g) => !MODULE_GROUP_ORDER.includes(g)),
    ];

    return orderedKeys.map((key) => ({ group: key, modules: groups.get(key) }));
}

// Fase 8 -- nodo de acción 'external_db_query' (Base de datos externa MySQL).
const DB_OPERATIONS = {
    select: 'Consultar (SELECT)',
    insert: 'Insertar (INSERT)',
    update: 'Actualizar (UPDATE)',
    delete: 'Eliminar (DELETE)',
};

const DB_WHERE_OPERATORS = {
    '=': 'igual a (=)',
    '!=': 'distinto de (!=)',
    '>': 'mayor que (>)',
    '<': 'menor que (<)',
    '>=': 'mayor o igual (>=)',
    '<=': 'menor o igual (<=)',
    like: 'contiene (LIKE)',
};

function normalizeDbColumnRow(row) {
    return {
        column: row?.column || '',
        value_source: row?.value_source === 'variable' ? 'variable' : 'literal',
        value: row?.value ?? '',
    };
}

function normalizeDbWhereRow(row) {
    return {
        column: row?.column || '',
        operator: row?.operator && DB_WHERE_OPERATORS[row.operator] ? row.operator : '=',
        value_source: row?.value_source === 'variable' ? 'variable' : 'literal',
        value: row?.value ?? '',
    };
}

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
 *   variables:     WorkflowVariable[] ya cargadas en WorkflowCanvasApp -- se
 *                  usan para poblar los selects de "valor = variable" del
 *                  formulario estructurado de 'external_db_query' (Fase 8).
 */
const STEP_TYPE_LABELS = {
    trigger: 'Trigger',
    action: 'Acción',
    condition: 'Condición',
    wait: 'Espera',
    switch: 'Switch',
    parallel: 'Paralelo',
    join: 'Unir ramas',
    loop: 'Bucle',
    end: 'Fin',
};

function initialsForStepType(stepType) {
    if (stepType === 'trigger') return 'TR';
    if (stepType === 'action') return 'AC';
    if (stepType === 'condition') return 'CO';
    if (stepType === 'wait') return 'ES';
    if (stepType === 'switch') return 'SW';
    if (stepType === 'parallel') return 'PA';
    if (stepType === 'join') return 'JN';
    if (stepType === 'loop') return '🔁';
    if (stepType === 'end') return 'FIN';
    return '--';
}

// Fase de nodos de flujo avanzado -- presets amigables del selector de
// "tipo de trigger" (capa de conveniencia sobre el textarea JSON crudo, que
// sigue siendo la fuente de verdad editable directamente para cualquier
// caso no cubierto por esta lista). `needs` indica qué sub-selector mostrar
// ('stage' | 'days' | null). `requiresModule`/`requiresSupportsStale`
// filtran la visibilidad según el módulo activo del workflow.
const TRIGGER_PRESETS = [
    {
        key: 'stage_enter',
        label: 'Lead entra a etapa',
        needs: 'stage',
        build: (stageId) => ({ event: 'updated', field: 'pipeline_stage_id', value: Number(stageId) }),
    },
    {
        key: 'stage_exit',
        label: 'Lead sale de etapa',
        needs: 'stage',
        build: (stageId) => ({ event: 'updated', field: 'pipeline_stage_id', from_value: Number(stageId) }),
    },
    {
        key: 'owner_assigned',
        label: 'Lead asignado',
        needs: null,
        build: () => ({ event: 'updated', field: 'owner_id' }),
    },
    {
        key: 'stale',
        label: 'Sin actividad por N días',
        needs: 'days',
        requiresSupportsStale: true,
        build: (days) => ({ event: 'stale', hours: Number(days) * 24 }),
    },
    {
        key: 'stage_stagnant',
        label: 'X días sin avanzar',
        needs: 'days',
        requiresModule: 'deal',
        build: (days) => ({ event: 'stage_stagnant', days: Number(days) }),
    },
    {
        key: 'upcoming_close',
        label: 'Fecha de cierre próxima',
        needs: 'days',
        requiresModule: 'deal',
        build: (days) => ({ event: 'upcoming', field: 'expected_close_date', days: Number(days) }),
    },
    {
        key: 'wa_received',
        label: 'Mensaje WA recibido',
        needs: null,
        requiresModule: 'whatsapp_message',
        build: () => ({ event: 'created', field: 'sender_type', value: 'contact' }),
    },
    {
        key: 'score_threshold',
        label: 'Score supera umbral',
        needs: null,
        disabled: true,
        build: () => ({}),
    },
];

function visibleTriggerPresets(moduleType, moduleEntry) {
    return TRIGGER_PRESETS.filter((preset) => {
        if (preset.requiresModule && preset.requiresModule !== moduleType) return false;
        if (preset.requiresSupportsStale && !moduleEntry?.supports_stale) return false;
        return true;
    });
}

function nextBranchKey(rows, prefix) {
    let n = 0;
    const used = new Set(rows.map((r) => r.branch_key));
    while (used.has(`${prefix}_${n}`)) n += 1;
    return `${prefix}_${n}`;
}

export default function NodeInspector({ step, catalog, workflowType, onSave, onSaveTrigger, onSelectModule, onDelete, onClose, variables }) {
    const [inspectorTab, setInspectorTab] = useState('params');
    const [moduleSaving, setModuleSaving] = useState(false);
    const [moduleError, setModuleError] = useState('');
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

    // Fase de nodos de flujo avanzado -- estado del selector amigable de
    // trigger (además del textarea JSON, no en su lugar).
    const [triggerPresetKey, setTriggerPresetKey] = useState('');
    const [triggerPresetStageId, setTriggerPresetStageId] = useState('');
    const [triggerPresetDays, setTriggerPresetDays] = useState('');

    // Estado del editor de reglas de 'switch' (persiste como
    // step.branch_condition = {"rules": [...]}).
    const [switchRules, setSwitchRules] = useState([]);

    // Estado del editor de ramas de 'parallel' (persiste como
    // step.action_config = {"branches": [...]}).
    const [parallelBranches, setParallelBranches] = useState([]);

    // Estado de 'loop' (persiste como step.action_config = {"max_iterations": N}).
    const [loopMaxIterations, setLoopMaxIterations] = useState('');

    // Fase 8 -- estado del formulario estructurado de 'external_db_query'.
    const [dbJsonMode, setDbJsonMode] = useState(false);
    const [dbCredentialId, setDbCredentialId] = useState('');
    const [dbTable, setDbTable] = useState('');
    const [dbOperation, setDbOperation] = useState('select');
    const [dbColumnsRows, setDbColumnsRows] = useState([]);
    const [dbWhereRows, setDbWhereRows] = useState([]);
    const [dbOutputVariable, setDbOutputVariable] = useState('');
    const [dbLimit, setDbLimit] = useState('');
    const [dbCredentials, setDbCredentials] = useState([]);
    const [dbTables, setDbTables] = useState([]);
    const [dbTableColumns, setDbTableColumns] = useState([]);
    const [dbMetaError, setDbMetaError] = useState('');
    const [dbLoadingTables, setDbLoadingTables] = useState(false);
    const [dbLoadingColumns, setDbLoadingColumns] = useState(false);

    const variableList = variables || [];

    function applyDbConfigToState(cfg) {
        const c = cfg || {};
        setDbCredentialId(c.credential_id != null ? String(c.credential_id) : '');
        setDbTable(c.table || '');
        setDbOperation(c.operation || 'select');
        setDbColumnsRows(Array.isArray(c.columns) ? c.columns.map(normalizeDbColumnRow) : []);
        setDbWhereRows(Array.isArray(c.where) ? c.where.map(normalizeDbWhereRow) : []);
        setDbOutputVariable(c.output_variable || '');
        setDbLimit(c.limit != null ? String(c.limit) : '');
    }

    function buildDbConfigFromState() {
        const config = {
            credential_id: dbCredentialId ? Number(dbCredentialId) : null,
            table: dbTable,
            operation: dbOperation,
        };
        if (dbOperation === 'insert' || dbOperation === 'update') {
            config.columns = dbColumnsRows
                .filter((row) => row.column)
                .map((row) => ({ column: row.column, value_source: row.value_source, value: row.value }));
        }
        if (dbOperation === 'select' || dbOperation === 'update' || dbOperation === 'delete') {
            config.where = dbWhereRows
                .filter((row) => row.column)
                .map((row) => ({
                    column: row.column,
                    operator: row.operator,
                    value_source: row.value_source,
                    value: row.value,
                }));
        }
        if (dbOperation === 'select') {
            if (dbOutputVariable.trim()) config.output_variable = dbOutputVariable.trim();
            if (dbLimit !== '') config.limit = Number(dbLimit);
        }
        return config;
    }

    function addDbColumnRow() {
        setDbColumnsRows((rows) => [...rows, { column: '', value_source: 'literal', value: '' }]);
    }

    function updateDbColumnRow(index, patch) {
        setDbColumnsRows((rows) => rows.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    }

    function removeDbColumnRow(index) {
        setDbColumnsRows((rows) => rows.filter((_, i) => i !== index));
    }

    // Fase de nodos de flujo avanzado -- editor de reglas de 'switch'.
    function addSwitchRule() {
        setSwitchRules((rows) => [
            ...rows,
            { branch_key: nextBranchKey(rows, 'case'), field: '', operator: 'equals', value: '', label: '' },
        ]);
    }

    function updateSwitchRule(index, patch) {
        setSwitchRules((rows) => rows.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    }

    function removeSwitchRule(index) {
        setSwitchRules((rows) => rows.filter((_, i) => i !== index));
    }

    function moveSwitchRule(index, direction) {
        setSwitchRules((rows) => {
            const target = index + direction;
            if (target < 0 || target >= rows.length) return rows;
            const next = [...rows];
            [next[index], next[target]] = [next[target], next[index]];
            return next;
        });
    }

    // Fase de nodos de flujo avanzado -- editor de ramas de 'parallel'.
    function addParallelBranch() {
        setParallelBranches((rows) => [...rows, { branch_key: nextBranchKey(rows, 'branch'), label: '' }]);
    }

    function updateParallelBranch(index, patch) {
        setParallelBranches((rows) => rows.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    }

    function removeParallelBranch(index) {
        setParallelBranches((rows) => rows.filter((_, i) => i !== index));
    }

    // Fase de nodos de flujo avanzado -- aplica un preset del selector
    // amigable de trigger, escribiendo el JSON resultante en el mismo
    // textarea (setTriggerConfigText) que ya usa el editor de respaldo.
    function applyTriggerPreset() {
        const preset = TRIGGER_PRESETS.find((p) => p.key === triggerPresetKey);
        if (!preset || preset.disabled) return;
        if (preset.needs === 'stage' && !triggerPresetStageId) {
            setError('Selecciona una etapa.');
            return;
        }
        if (preset.needs === 'days' && (triggerPresetDays === '' || Number.isNaN(Number(triggerPresetDays)))) {
            setError('Indica un número de días válido.');
            return;
        }
        const arg = preset.needs === 'stage' ? triggerPresetStageId : preset.needs === 'days' ? triggerPresetDays : undefined;
        const built = preset.build(arg);
        setTriggerConfigText(JSON.stringify(built, null, 2));
        setError('');
    }

    function addDbWhereRow() {
        setDbWhereRows((rows) => [...rows, { column: '', operator: '=', value_source: 'literal', value: '' }]);
    }

    function updateDbWhereRow(index, patch) {
        setDbWhereRows((rows) => rows.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    }

    function removeDbWhereRow(index) {
        setDbWhereRows((rows) => rows.filter((_, i) => i !== index));
    }

    function handleToggleDbJsonMode() {
        if (!dbJsonMode) {
            setActionConfigText(JSON.stringify(buildDbConfigFromState(), null, 2));
            setDbJsonMode(true);
            setError('');
            return;
        }

        try {
            const parsed = actionConfigText.trim() ? JSON.parse(actionConfigText) : {};
            applyDbConfigToState(parsed);
            setDbJsonMode(false);
            setError('');
        } catch (e) {
            setError('El JSON no es válido. Corrígelo antes de volver al formulario.');
        }
    }

    // Fetch de metadatos de solo lectura (nunca el secreto de la credencial)
    // para poblar los selects del formulario estructurado.
    useEffect(() => {
        if (actionType !== 'external_db_query') return undefined;
        let cancelled = false;
        setDbMetaError('');
        listDatabaseCredentials()
            .then((list) => {
                if (!cancelled) setDbCredentials(Array.isArray(list) ? list : []);
            })
            .catch((err) => {
                if (!cancelled) setDbMetaError(err.message || 'No se pudieron cargar las credenciales.');
            });
        return () => {
            cancelled = true;
        };
    }, [actionType]);

    useEffect(() => {
        if (actionType !== 'external_db_query' || !dbCredentialId) {
            setDbTables([]);
            return undefined;
        }
        let cancelled = false;
        setDbLoadingTables(true);
        setDbMetaError('');
        listDatabaseTables(dbCredentialId)
            .then((list) => {
                if (!cancelled) setDbTables(Array.isArray(list) ? list : []);
            })
            .catch((err) => {
                if (!cancelled) setDbMetaError(err.message || 'No se pudieron cargar las tablas.');
            })
            .finally(() => {
                if (!cancelled) setDbLoadingTables(false);
            });
        return () => {
            cancelled = true;
        };
    }, [actionType, dbCredentialId]);

    useEffect(() => {
        if (actionType !== 'external_db_query' || !dbCredentialId || !dbTable) {
            setDbTableColumns([]);
            return undefined;
        }
        let cancelled = false;
        setDbLoadingColumns(true);
        setDbMetaError('');
        listDatabaseColumns(dbCredentialId, dbTable)
            .then((list) => {
                if (!cancelled) setDbTableColumns(Array.isArray(list) ? list : []);
            })
            .catch((err) => {
                if (!cancelled) setDbMetaError(err.message || 'No se pudieron cargar las columnas.');
            })
            .finally(() => {
                if (!cancelled) setDbLoadingColumns(false);
            });
        return () => {
            cancelled = true;
        };
    }, [actionType, dbCredentialId, dbTable]);

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
            setDbJsonMode(false);
            if (step.action_type === 'external_db_query') {
                applyDbConfigToState(step.action_config || {});
            }
        } else if (step.step_type === 'condition') {
            const cond = step.branch_condition || {};
            setConditionField(cond.field ?? '');
            setConditionOperator(cond.operator ?? '');
            setConditionValue(cond.value ?? '');
        } else if (step.step_type === 'wait') {
            const cfg = step.action_config || {};
            setWaitAmount(cfg.amount ?? '');
            setWaitUnit(cfg.unit ?? '');
        } else if (step.step_type === 'switch') {
            const rules = Array.isArray(step.branch_condition?.rules) ? step.branch_condition.rules : [];
            setSwitchRules(rules.map((r, i) => ({
                branch_key: r.branch_key || `case_${i}`,
                field: r.field || '',
                operator: r.operator || 'equals',
                value: r.value ?? '',
                label: r.label || '',
            })));
        } else if (step.step_type === 'parallel') {
            const branches = Array.isArray(step.action_config?.branches) ? step.action_config.branches : [];
            setParallelBranches(branches.map((b, i) => ({
                branch_key: b.branch_key || `branch_${i}`,
                label: b.label || '',
            })));
        } else if (step.step_type === 'loop') {
            const cfg = step.action_config || {};
            setLoopMaxIterations(cfg.max_iterations ?? '');
        } else if (step.step_type === 'trigger') {
            setTriggerType(step.type || step.workflowType || '');
            setTriggerConfigText(
                step.enrollment_trigger != null ? JSON.stringify(step.enrollment_trigger, null, 2) : ''
            );
            setTriggerPresetKey('');
            setTriggerPresetStageId('');
            setTriggerPresetDays('');
        }
    }, [step]);

    if (!step) return null;

    const actionTypes = catalog?.action_types || {};
    const conditionOperators = catalog?.condition_operators || {};
    const waitUnits = catalog?.wait_units || {};
    const modules = catalog?.modules || [];
    const fieldValueSources = catalog?.field_value_sources || {};
    const actionValueSources = catalog?.action_value_sources || {};
    const groupedModules = groupModules(modules);
    // Módulo activo para filtrar sugerencias de autocompletado en los
    // textareas JSON: mientras se está eligiendo módulo en el propio Trigger
    // se usa el select en vivo (triggerType); en cualquier otro paso se usa
    // el módulo ya guardado del workflow (workflowType).
    const activeModuleType = step.step_type === 'trigger' ? triggerType : workflowType;

    function handleModuleChange(e) {
        const newType = e.target.value;
        setTriggerType(newType);
        if (!onSelectModule) return;
        setModuleSaving(true);
        setModuleError('');
        Promise.resolve(onSelectModule(newType))
            .catch((err) => setModuleError(err?.message || 'Error guardando el módulo.'))
            .finally(() => setModuleSaving(false));
    }

    function handleActionTypeChange(e) {
        const selected = e.target.value;
        setActionType(selected);
        setError('');
        setDbJsonMode(false);

        // Sólo autocompleta con el ejemplo si el usuario todavía no escribió nada.
        if (actionConfigText.trim() === '') {
            const example = actionTypes[selected]?.example_config;
            if (example !== undefined) {
                setActionConfigText(JSON.stringify(example, null, 2));
            }
        }

        if (selected === 'external_db_query') {
            applyDbConfigToState({});
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
            if (actionType === 'external_db_query' && !dbJsonMode) {
                if (!dbCredentialId) {
                    setError('Selecciona una credencial de base de datos.');
                    return;
                }
                if (!dbTable) {
                    setError('Selecciona una tabla.');
                    return;
                }
                if (dbOperation === 'update' || dbOperation === 'delete') {
                    const hasWhere = dbWhereRows.some((row) => row.column);
                    if (!hasWhere) {
                        setError('Actualizar/Eliminar requiere al menos una condición (WHERE) para evitar afectar toda la tabla.');
                        return;
                    }
                }
                if (dbOperation === 'select' && dbLimit !== '') {
                    const limitNumber = Number(dbLimit);
                    if (Number.isNaN(limitNumber) || limitNumber < 1 || limitNumber > 500) {
                        setError('El límite de filas debe ser un número entre 1 y 500.');
                        return;
                    }
                }
                onSave(step.id, {
                    action_type: actionType,
                    action_config: buildDbConfigFromState(),
                });
                return;
            }

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

        if (step.step_type === 'switch') {
            const cleanRules = switchRules.filter((r) => r.field.trim());
            if (cleanRules.length === 0) {
                setError('Agrega al menos una regla con un campo configurado.');
                return;
            }
            onSave(step.id, {
                branch_condition: {
                    rules: cleanRules.map((r) => ({
                        branch_key: r.branch_key,
                        field: r.field,
                        operator: r.operator,
                        value: r.value,
                        label: r.label,
                    })),
                },
            });
            return;
        }

        if (step.step_type === 'parallel') {
            const cleanBranches = parallelBranches.filter((b) => b.branch_key);
            if (cleanBranches.length === 0) {
                setError('Agrega al menos una rama.');
                return;
            }
            onSave(step.id, {
                action_config: {
                    branches: cleanBranches.map((b) => ({ branch_key: b.branch_key, label: b.label })),
                },
            });
            return;
        }

        if (step.step_type === 'loop') {
            const n = Number(loopMaxIterations);
            if (loopMaxIterations === '' || Number.isNaN(n) || n < 1 || n > 50) {
                setError('El número máximo de iteraciones debe ser entre 1 y 50.');
                return;
            }
            onSave(step.id, {
                action_config: { max_iterations: Math.min(50, Math.max(1, Math.round(n))) },
            });
            return;
        }

        if (step.step_type === 'join' || step.step_type === 'end') {
            onSave(step.id, { action_config: {} });
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
                {inspectorTab === 'credentials' && step.step_type === 'action' && actionType === 'external_db_query' ? (
                    <div className="wf-inspector-empty-tab">
                        {dbCredentialId ? (
                            <>
                                Credencial seleccionada:{' '}
                                {dbCredentials.find((c) => String(c.id) === String(dbCredentialId))?.name || `#${dbCredentialId}`}{' '}
                                (tipo: mysql)
                            </>
                        ) : (
                            'Este paso todavía no tiene una credencial de base de datos seleccionada.'
                        )}
                    </div>
                ) : (
                    inspectorTab !== 'params' && (
                        <div className="wf-inspector-empty-tab">
                            {inspectorTab === 'credentials' && 'Este tipo de nodo no requiere credenciales.'}
                            {inspectorTab === 'input' && 'El input de este paso aparecerá aquí después de ejecutar una prueba.'}
                            {inspectorTab === 'output' && 'El output de este paso aparecerá aquí después de ejecutar una prueba.'}
                        </div>
                    )
                )}

                {inspectorTab === 'params' && step.step_type === 'trigger' && (
                    <>
                        <div className="wf-field-row-header">
                            <label className="wf-field-label" htmlFor="wf-trigger-module">
                                Módulo a automatizar
                            </label>
                            {moduleSaving && <span className="wf-inspector-subtitle">Guardando…</span>}
                        </div>
                        <select
                            id="wf-trigger-module"
                            className="wf-field-input"
                            value={triggerType}
                            onChange={handleModuleChange}
                        >
                            <option value="">-- Sin configurar --</option>
                            {groupedModules.map(({ group, modules: groupModulesList }) => (
                                <optgroup key={group} label={group}>
                                    {groupModulesList.map((m) => (
                                        <option key={m.type} value={m.type}>
                                            {m.label}
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </select>
                        {moduleError && <div className="wf-inspector-error">{moduleError}</div>}

                        {(() => {
                            const moduleEntry = modules.find((m) => m.type === triggerType) || null;
                            const presets = visibleTriggerPresets(triggerType, moduleEntry);
                            const selectedPreset = TRIGGER_PRESETS.find((p) => p.key === triggerPresetKey);
                            const pipelineStageOptions = fieldValueSources.pipeline_stage_id || [];

                            return (
                                <>
                                    <label className="wf-field-label" htmlFor="wf-trigger-preset">
                                        Tipo de trigger (rápido)
                                    </label>
                                    <select
                                        id="wf-trigger-preset"
                                        className="wf-field-input"
                                        value={triggerPresetKey}
                                        onChange={(e) => {
                                            setTriggerPresetKey(e.target.value);
                                            setTriggerPresetStageId('');
                                            setTriggerPresetDays('');
                                            setError('');
                                        }}
                                    >
                                        <option value="">-- Elegir un tipo de trigger --</option>
                                        {presets.map((preset) => (
                                            <option key={preset.key} value={preset.key} disabled={preset.disabled}>
                                                {preset.label}{preset.disabled ? ' (Próximamente)' : ''}
                                            </option>
                                        ))}
                                    </select>

                                    {selectedPreset && !selectedPreset.disabled && selectedPreset.needs === 'stage' && (
                                        <select
                                            className="wf-field-input"
                                            value={triggerPresetStageId}
                                            onChange={(e) => setTriggerPresetStageId(e.target.value)}
                                        >
                                            <option value="">-- Selecciona una etapa --</option>
                                            {pipelineStageOptions.map((opt) => (
                                                <option key={opt.value} value={opt.value}>
                                                    {opt.hint}
                                                </option>
                                            ))}
                                        </select>
                                    )}

                                    {selectedPreset && !selectedPreset.disabled && selectedPreset.needs === 'days' && (
                                        <input
                                            type="number"
                                            className="wf-field-input"
                                            min="1"
                                            placeholder="Número de días"
                                            value={triggerPresetDays}
                                            onChange={(e) => setTriggerPresetDays(e.target.value)}
                                        />
                                    )}

                                    {selectedPreset && !selectedPreset.disabled && (
                                        <button type="button" className="wf-btn-link" onClick={applyTriggerPreset}>
                                            Usar esta opción
                                        </button>
                                    )}
                                </>
                            );
                        })()}

                        <label className="wf-field-label" htmlFor="wf-trigger-config">
                            Trigger de inscripción (JSON)
                        </label>
                        <TokenAutocompleteTextarea
                            id="wf-trigger-config"
                            className="wf-field-input wf-field-textarea"
                            rows={10}
                            value={triggerConfigText}
                            onValueChange={setTriggerConfigText}
                            spellCheck={false}
                            moduleType={activeModuleType}
                            modules={modules}
                            workflowVariables={variableList}
                            fieldValueSources={fieldValueSources}
                            actionValueSources={actionValueSources}
                            nodeSchema={TRIGGER_SCHEMA}
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

                        {actionType === 'external_db_query' ? (
                            <>
                                <div className="wf-field-row-header">
                                    <label className="wf-field-label">Configuración</label>
                                    <button type="button" className="wf-btn-link" onClick={handleToggleDbJsonMode}>
                                        {dbJsonMode ? 'Editar con formulario' : 'Editar como JSON'}
                                    </button>
                                </div>

                                {dbMetaError && <div className="wf-inspector-error">{dbMetaError}</div>}

                                {dbJsonMode ? (
                                    <TokenAutocompleteTextarea
                                        id="wf-action-config"
                                        className="wf-field-input wf-field-textarea"
                                        rows={12}
                                        value={actionConfigText}
                                        onValueChange={setActionConfigText}
                                        spellCheck={false}
                                        moduleType={activeModuleType}
                                        modules={modules}
                                        workflowVariables={variableList}
                                        fieldValueSources={fieldValueSources}
                                        actionValueSources={actionValueSources}
                                        localValueSources={{ db_credentials: dbCredentials }}
                                        nodeSchema={EXTERNAL_DB_SCHEMA}
                                    />
                                ) : (
                                    <>
                                        <label className="wf-field-label" htmlFor="wf-db-credential">
                                            Credencial
                                        </label>
                                        <select
                                            id="wf-db-credential"
                                            className="wf-field-input"
                                            value={dbCredentialId}
                                            onChange={(e) => {
                                                setDbCredentialId(e.target.value);
                                                setDbTable('');
                                            }}
                                        >
                                            <option value="">-- Selecciona --</option>
                                            {dbCredentials.map((cred) => (
                                                <option key={cred.id} value={cred.id}>
                                                    {cred.name}
                                                </option>
                                            ))}
                                        </select>

                                        <label className="wf-field-label" htmlFor="wf-db-table">
                                            Tabla
                                        </label>
                                        <select
                                            id="wf-db-table"
                                            className="wf-field-input"
                                            value={dbTable}
                                            onChange={(e) => setDbTable(e.target.value)}
                                            disabled={!dbCredentialId || dbLoadingTables}
                                        >
                                            <option value="">
                                                {dbLoadingTables ? 'Cargando...' : '-- Selecciona --'}
                                            </option>
                                            {dbTables.map((table) => (
                                                <option key={table} value={table}>
                                                    {table}
                                                </option>
                                            ))}
                                        </select>

                                        <label className="wf-field-label" htmlFor="wf-db-operation">
                                            Operación
                                        </label>
                                        <select
                                            id="wf-db-operation"
                                            className="wf-field-input"
                                            value={dbOperation}
                                            onChange={(e) => setDbOperation(e.target.value)}
                                        >
                                            {Object.entries(DB_OPERATIONS).map(([key, label]) => (
                                                <option key={key} value={key}>
                                                    {label}
                                                </option>
                                            ))}
                                        </select>

                                        {(dbOperation === 'insert' || dbOperation === 'update') && (
                                            <>
                                                <div className="wf-field-row-header">
                                                    <label className="wf-field-label">Columnas</label>
                                                    <button type="button" className="wf-btn-link" onClick={addDbColumnRow}>
                                                        + Agregar columna
                                                    </button>
                                                </div>
                                                {dbColumnsRows.length === 0 && (
                                                    <div className="wf-inspector-empty-tab">Sin columnas configuradas.</div>
                                                )}
                                                {dbColumnsRows.map((row, index) => (
                                                    <div className="wf-field-row" key={index}>
                                                        <select
                                                            className="wf-field-input"
                                                            value={row.column}
                                                            onChange={(e) => updateDbColumnRow(index, { column: e.target.value })}
                                                        >
                                                            <option value="">-- Columna --</option>
                                                            {dbTableColumns.map((col) => (
                                                                <option key={col} value={col}>
                                                                    {col}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <select
                                                            className="wf-field-input"
                                                            value={row.value_source}
                                                            onChange={(e) => updateDbColumnRow(index, { value_source: e.target.value })}
                                                        >
                                                            <option value="literal">Valor literal</option>
                                                            <option value="variable">Variable</option>
                                                        </select>
                                                        {row.value_source === 'variable' ? (
                                                            <select
                                                                className="wf-field-input"
                                                                value={row.value}
                                                                onChange={(e) => updateDbColumnRow(index, { value: e.target.value })}
                                                            >
                                                                <option value="">-- Variable --</option>
                                                                {variableList.map((v) => (
                                                                    <option key={v.id} value={v.name}>
                                                                        {v.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        ) : (
                                                            <input
                                                                type="text"
                                                                className="wf-field-input"
                                                                value={row.value}
                                                                onChange={(e) => updateDbColumnRow(index, { value: e.target.value })}
                                                            />
                                                        )}
                                                        <button
                                                            type="button"
                                                            className="wf-btn-link"
                                                            onClick={() => removeDbColumnRow(index)}
                                                        >
                                                            Quitar
                                                        </button>
                                                    </div>
                                                ))}
                                            </>
                                        )}

                                        {(dbOperation === 'select' || dbOperation === 'update' || dbOperation === 'delete') && (
                                            <>
                                                <div className="wf-field-row-header">
                                                    <label className="wf-field-label">
                                                        Condiciones (WHERE)
                                                        {(dbOperation === 'update' || dbOperation === 'delete') && ' -- obligatorio'}
                                                    </label>
                                                    <button type="button" className="wf-btn-link" onClick={addDbWhereRow}>
                                                        + Agregar condición
                                                    </button>
                                                </div>
                                                {dbWhereRows.length === 0 && (
                                                    <div className="wf-inspector-empty-tab">Sin condiciones configuradas.</div>
                                                )}
                                                {dbWhereRows.map((row, index) => (
                                                    <div className="wf-field-row" key={index}>
                                                        <select
                                                            className="wf-field-input"
                                                            value={row.column}
                                                            onChange={(e) => updateDbWhereRow(index, { column: e.target.value })}
                                                        >
                                                            <option value="">-- Columna --</option>
                                                            {dbTableColumns.map((col) => (
                                                                <option key={col} value={col}>
                                                                    {col}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <select
                                                            className="wf-field-input"
                                                            value={row.operator}
                                                            onChange={(e) => updateDbWhereRow(index, { operator: e.target.value })}
                                                        >
                                                            {Object.entries(DB_WHERE_OPERATORS).map(([key, label]) => (
                                                                <option key={key} value={key}>
                                                                    {label}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <select
                                                            className="wf-field-input"
                                                            value={row.value_source}
                                                            onChange={(e) => updateDbWhereRow(index, { value_source: e.target.value })}
                                                        >
                                                            <option value="literal">Valor literal</option>
                                                            <option value="variable">Variable</option>
                                                        </select>
                                                        {row.value_source === 'variable' ? (
                                                            <select
                                                                className="wf-field-input"
                                                                value={row.value}
                                                                onChange={(e) => updateDbWhereRow(index, { value: e.target.value })}
                                                            >
                                                                <option value="">-- Variable --</option>
                                                                {variableList.map((v) => (
                                                                    <option key={v.id} value={v.name}>
                                                                        {v.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        ) : (
                                                            <input
                                                                type="text"
                                                                className="wf-field-input"
                                                                value={row.value}
                                                                onChange={(e) => updateDbWhereRow(index, { value: e.target.value })}
                                                            />
                                                        )}
                                                        <button
                                                            type="button"
                                                            className="wf-btn-link"
                                                            onClick={() => removeDbWhereRow(index)}
                                                        >
                                                            Quitar
                                                        </button>
                                                    </div>
                                                ))}
                                            </>
                                        )}

                                        {dbOperation === 'select' && (
                                            <>
                                                <label className="wf-field-label" htmlFor="wf-db-output-variable">
                                                    Variable de salida (opcional)
                                                </label>
                                                <input
                                                    id="wf-db-output-variable"
                                                    type="text"
                                                    className="wf-field-input"
                                                    value={dbOutputVariable}
                                                    onChange={(e) => setDbOutputVariable(e.target.value)}
                                                    placeholder="p. ej. resultado_consulta"
                                                />

                                                <label className="wf-field-label" htmlFor="wf-db-limit">
                                                    Límite de filas (máx. 500)
                                                </label>
                                                <input
                                                    id="wf-db-limit"
                                                    type="number"
                                                    className="wf-field-input"
                                                    min="1"
                                                    max="500"
                                                    value={dbLimit}
                                                    onChange={(e) => setDbLimit(e.target.value)}
                                                />
                                            </>
                                        )}
                                    </>
                                )}
                            </>
                        ) : (
                            <>
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
                                <TokenAutocompleteTextarea
                                    id="wf-action-config"
                                    className="wf-field-input wf-field-textarea"
                                    rows={10}
                                    value={actionConfigText}
                                    onValueChange={setActionConfigText}
                                    spellCheck={false}
                                    moduleType={activeModuleType}
                                    modules={modules}
                                    workflowVariables={variableList}
                                    fieldValueSources={fieldValueSources}
                                    actionValueSources={actionValueSources}
                                    nodeSchema={ACTION_CONFIG_SCHEMAS[actionType]}
                                />
                            </>
                        )}
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'condition' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-cond-field">
                            Campo
                        </label>
                        <FieldAutocompleteInput
                            id="wf-cond-field"
                            value={conditionField}
                            onChange={setConditionField}
                            source="module_fields"
                            moduleType={activeModuleType}
                            modules={modules}
                            fieldValueSources={fieldValueSources}
                            actionValueSources={actionValueSources}
                            className="wf-field-input"
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
                        <FieldAutocompleteInput
                            id="wf-cond-value"
                            value={conditionValue}
                            onChange={setConditionValue}
                            source="field_value:dynamic"
                            dynamicFieldText={conditionField}
                            moduleType={activeModuleType}
                            modules={modules}
                            fieldValueSources={fieldValueSources}
                            actionValueSources={actionValueSources}
                            className="wf-field-input"
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

                {inspectorTab === 'params' && step.step_type === 'switch' && (
                    <>
                        <div className="wf-field-row-header">
                            <label className="wf-field-label">Reglas (se evalúan en orden)</label>
                            <button type="button" className="wf-btn-link" onClick={addSwitchRule}>
                                + Agregar regla
                            </button>
                        </div>
                        {switchRules.length === 0 && (
                            <div className="wf-inspector-empty-tab">Sin reglas configuradas. La rama "Otro caso" se usa cuando ninguna regla coincide.</div>
                        )}
                        {switchRules.map((rule, index) => (
                            <div className="wf-field-row" key={rule.branch_key}>
                                <FieldAutocompleteInput
                                    value={rule.field}
                                    onChange={(v) => updateSwitchRule(index, { field: v })}
                                    source="module_fields"
                                    moduleType={activeModuleType}
                                    modules={modules}
                                    fieldValueSources={fieldValueSources}
                                    actionValueSources={actionValueSources}
                                    className="wf-field-input"
                                    placeholder="Campo"
                                />
                                <select
                                    className="wf-field-input"
                                    value={rule.operator}
                                    onChange={(e) => updateSwitchRule(index, { operator: e.target.value })}
                                >
                                    {Object.entries(conditionOperators).map(([key, label]) => (
                                        <option key={key} value={key}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                <input
                                    type="text"
                                    className="wf-field-input"
                                    placeholder="Valor"
                                    value={rule.value}
                                    onChange={(e) => updateSwitchRule(index, { value: e.target.value })}
                                />
                                <input
                                    type="text"
                                    className="wf-field-input"
                                    placeholder="Etiqueta"
                                    value={rule.label}
                                    onChange={(e) => updateSwitchRule(index, { label: e.target.value })}
                                />
                                <button type="button" className="wf-btn-link" onClick={() => moveSwitchRule(index, -1)} disabled={index === 0}>
                                    ↑
                                </button>
                                <button type="button" className="wf-btn-link" onClick={() => moveSwitchRule(index, 1)} disabled={index === switchRules.length - 1}>
                                    ↓
                                </button>
                                <button type="button" className="wf-btn-link" onClick={() => removeSwitchRule(index)}>
                                    Quitar
                                </button>
                            </div>
                        ))}
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'parallel' && (
                    <>
                        <div className="wf-field-row-header">
                            <label className="wf-field-label">Ramas (todas se disparan siempre)</label>
                            <button type="button" className="wf-btn-link" onClick={addParallelBranch}>
                                + Agregar rama
                            </button>
                        </div>
                        {parallelBranches.length === 0 && (
                            <div className="wf-inspector-empty-tab">Sin ramas configuradas.</div>
                        )}
                        {parallelBranches.map((branch, index) => (
                            <div className="wf-field-row" key={branch.branch_key}>
                                <span className="wf-node-subtitle">{branch.branch_key}</span>
                                <input
                                    type="text"
                                    className="wf-field-input"
                                    placeholder="Etiqueta"
                                    value={branch.label}
                                    onChange={(e) => updateParallelBranch(index, { label: e.target.value })}
                                />
                                <button type="button" className="wf-btn-link" onClick={() => removeParallelBranch(index)}>
                                    Quitar
                                </button>
                            </div>
                        ))}
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'loop' && (
                    <>
                        <label className="wf-field-label" htmlFor="wf-loop-max-iterations">
                            Máximo de iteraciones (1-50)
                        </label>
                        <input
                            id="wf-loop-max-iterations"
                            type="number"
                            className="wf-field-input"
                            min="1"
                            max="50"
                            value={loopMaxIterations}
                            onChange={(e) => {
                                const raw = e.target.value;
                                if (raw === '') {
                                    setLoopMaxIterations('');
                                    return;
                                }
                                const n = Number(raw);
                                setLoopMaxIterations(Number.isNaN(n) ? raw : Math.min(50, Math.max(1, n)));
                            }}
                        />
                    </>
                )}

                {inspectorTab === 'params' && step.step_type === 'join' && (
                    <div className="wf-inspector-empty-tab">
                        Este paso reúne todas las ramas del Paralelo anterior. El flujo continúa cuando TODAS las ramas terminan aquí.
                    </div>
                )}

                {inspectorTab === 'params' && step.step_type === 'end' && (
                    <div className="wf-inspector-empty-tab">
                        El flujo termina en este punto.
                    </div>
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
