import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Fin" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'end'): sin action_config,
 * sin branch_key en hijos (no tiene hijos). Sólo un target handle a la
 * izquierda -- el flujo termina aquí, no hay source handle de salida.
 */
export default function EndNode({ selected }) {
    return (
        <div className={'wf-node-card wf-node-end' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">FIN</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Fin</div>
                    <div className="wf-node-subtitle">El flujo termina aquí</div>
                </div>
            </div>
        </div>
    );
}
