import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Join" (reunión de ramas) del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'join'): sin action_config.
 * Un solo target handle sin id explícito -- React Flow permite que varias
 * aristas terminen en el mismo target handle sin necesidad de ids
 * distintos -- + un source handle hacia lo que sigue tras la reunión.
 */
export default function JoinNode({ selected }) {
    return (
        <div className={'wf-node-card wf-node-join' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">JN</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Une ramas</div>
                    <div className="wf-node-subtitle">Espera a que todas terminen</div>
                </div>
            </div>

            <Handle type="source" position={Position.Right} />
        </div>
    );
}
