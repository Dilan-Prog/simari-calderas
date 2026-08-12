import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Condición" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'condition'):
 * branch_condition = { field, operator, value } (o null/vacío).
 *
 * Un target handle a la izquierda y DOS source handles con id explícito
 * ("yes" / "no") para que WorkflowCanvasApp pueda mapear cada rama a su
 * propia conexión saliente.
 */
function summarizeCondition(condition) {
    if (!condition || (typeof condition === 'object' && Object.keys(condition).length === 0)) {
        return 'Sin condición configurada';
    }
    if (condition.field || condition.operator || condition.value !== undefined) {
        const field = condition.field ?? '?';
        const operator = condition.operator ?? '?';
        const value = condition.value !== undefined ? String(condition.value) : '?';
        return `${field} ${operator} ${value}`;
    }
    try {
        const json = JSON.stringify(condition);
        return json.length > 60 ? json.slice(0, 60) + '…' : json;
    } catch (e) {
        return 'Condición configurada';
    }
}

export default function ConditionNode({ data, selected }) {
    const summary = summarizeCondition(data?.branch_condition);

    return (
        <div className={'wf-node-card wf-node-condition' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">CO</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Condición</div>
                    <div className="wf-node-subtitle">Ramifica el flujo</div>
                </div>
            </div>

            <div className="wf-node-body">
                <div className="wf-node-config" title={summary}>{summary}</div>
            </div>

            <span className="wf-node-branch-label wf-node-branch-yes">Sí</span>
            <span className="wf-node-branch-label wf-node-branch-no">No</span>

            <Handle type="source" position={Position.Right} id="yes" style={{ top: '35%' }} />
            <Handle type="source" position={Position.Right} id="no" style={{ top: '65%' }} />
        </div>
    );
}
