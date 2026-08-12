import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Esperar" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'wait'):
 * action_config = { amount, unit } (o null/vacío).
 */
const UNIT_LABELS = {
    minutes: 'minutos',
    hours: 'horas',
    days: 'días',
    weeks: 'semanas',
};

function summarizeWait(config) {
    if (!config || config.amount === undefined || config.amount === null || !config.unit) {
        return 'Sin configurar';
    }
    const unitLabel = UNIT_LABELS[config.unit] || config.unit;
    return `Esperar ${config.amount} ${unitLabel}`;
}

export default function WaitNode({ data, selected }) {
    const summary = summarizeWait(data?.action_config);

    return (
        <div className={'wf-node-card wf-node-wait' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">ES</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">{summary}</div>
                    <div className="wf-node-subtitle">Espera</div>
                </div>
            </div>

            <Handle type="source" position={Position.Right} />
        </div>
    );
}
