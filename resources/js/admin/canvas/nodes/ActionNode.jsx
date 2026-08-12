import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Acción" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'action'): action_type,
 * action_config, etc. Un target handle a la izquierda (recibe del paso
 * anterior) y un source handle a la derecha (continúa hacia el siguiente).
 */
function summarizeConfig(config) {
    if (!config || (typeof config === 'object' && Object.keys(config).length === 0)) {
        return 'Sin parámetros';
    }
    try {
        const json = JSON.stringify(config);
        return json.length > 60 ? json.slice(0, 60) + '…' : json;
    } catch (e) {
        return '';
    }
}

export default function ActionNode({ data, selected }) {
    const actionType = data?.action_type;
    const title = data?.action_label || actionType || 'Sin configurar';
    const summary = summarizeConfig(data?.action_config);

    return (
        <div className={'wf-node-card wf-node-action' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">AC</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">{title}</div>
                    <div className="wf-node-subtitle">Acción</div>
                </div>
            </div>

            <div className="wf-node-body">
                <div className="wf-node-config" title={summary}>{summary}</div>
            </div>

            <Handle type="source" position={Position.Right} />
        </div>
    );
}
