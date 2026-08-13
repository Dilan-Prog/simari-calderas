import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Trigger" del canvas de Workflows.
 *
 * Props de React Flow: { id, data, selected } donde `data` es
 * directamente { enrollment_trigger, workflowType } (no un step de BD:
 * el trigger vive en la cabecera del Workflow, no en workflow_steps).
 *
 * Un solo source handle a la derecha — los triggers no reciben
 * conexiones entrantes, son el punto de partida del flujo. El nodo es
 * seleccionable como cualquier otro (React Flow lo maneja igual que a
 * los steps); la edición ocurre en el NodeInspector lateral al
 * seleccionarlo, no hay formulario aparte para el trigger.
 */
const WORKFLOW_TYPE_LABELS = {
    contact: 'Contacto',
    company: 'Empresa',
    deal: 'Negociación',
    date_based: 'Basado en fecha',
};

function summarizeTrigger(trigger) {
    if (!trigger || (typeof trigger === 'object' && Object.keys(trigger).length === 0)) {
        return 'Sin condiciones configuradas';
    }
    try {
        const json = JSON.stringify(trigger);
        return json.length > 60 ? json.slice(0, 60) + '…' : json;
    } catch (e) {
        return 'Trigger configurado';
    }
}

function isTriggerUnconfigured(trigger) {
    if (trigger == null) return true;
    if (typeof trigger === 'object' && Object.keys(trigger).length === 0) return true;
    if (typeof trigger === 'string' && trigger.trim() === '') return true;
    return false;
}

export default function TriggerNode({ data, selected }) {
    const workflowType = data?.workflowType;
    const typeLabel = data?.moduleLabel || WORKFLOW_TYPE_LABELS[workflowType] || workflowType || 'Sin módulo';
    const summary = summarizeTrigger(data?.enrollment_trigger);
    const unconfigured = isTriggerUnconfigured(data?.enrollment_trigger);

    return (
        <div className={'wf-node-card wf-node-trigger' + (selected ? ' is-selected' : '')}>
            <div className="wf-node-card-header">
                <span className="wf-node-badge">TR</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Trigger</div>
                    <div className="wf-node-subtitle">{typeLabel}</div>
                </div>
                {unconfigured && (
                    <span className="wf-node-warning-badge" title="El trigger no tiene condiciones configuradas">
                        Sin configurar
                    </span>
                )}
            </div>

            <div className="wf-node-body">
                <div className="wf-node-config" title={summary}>{summary}</div>
            </div>

            <Handle type="source" position={Position.Right} />
        </div>
    );
}
