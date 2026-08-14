import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Bucle" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'loop'):
 * action_config = { max_iterations: N }. Un target handle a la izquierda +
 * un source handle a la derecha hacia el cuerpo del bucle (sin arista
 * visual de "vuelta" -- la repetición se comunica con un badge dentro del
 * propio nodo).
 */
export default function LoopNode({ data, selected }) {
    const maxIterations = data?.action_config?.max_iterations;

    return (
        <div className={'wf-node-card wf-node-loop' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">🔁</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Bucle</div>
                    <div className="wf-node-subtitle">Repite el cuerpo del bucle</div>
                </div>
            </div>

            <div className="wf-node-body">
                <div className="wf-node-config">
                    {maxIterations ? `🔁 hasta ${maxIterations} veces` : 'Sin configurar'}
                </div>
            </div>

            <Handle type="source" position={Position.Right} />
        </div>
    );
}
