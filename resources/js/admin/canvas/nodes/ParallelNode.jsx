import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Paralelo" del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'parallel'):
 * action_config = { branches: [{branch_key, label}, ...] }.
 *
 * Un target handle a la izquierda + un source handle por cada rama
 * (id=branch.branch_key). A diferencia de Switch, aquí NO hay evaluación
 * condicional -- todas las ramas se disparan siempre -- por eso el ícono es
 * de bifurcación (no de interrogación) y no hay handle "default".
 */
function handleTop(index, total) {
    if (total <= 1) return '50%';
    const start = 18;
    const end = 82;
    const pct = start + ((end - start) * index) / (total - 1);
    return `${pct}%`;
}

function ForkIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 4v6c0 4 12 4 12 0V4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M12 10v10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

export default function ParallelNode({ data, selected }) {
    const branches = Array.isArray(data?.action_config?.branches) ? data.action_config.branches : [];
    const totalHandles = Math.max(branches.length, 1);

    return (
        <div className={'wf-node-card wf-node-parallel' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge"><ForkIcon /></span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Paralelo</div>
                    <div className="wf-node-subtitle">{branches.length} rama{branches.length === 1 ? '' : 's'} · todas se disparan</div>
                </div>
            </div>

            <div className="wf-node-body">
                {branches.length === 0 ? (
                    <div className="wf-node-config">Sin ramas configuradas</div>
                ) : (
                    <div className="wf-node-config">
                        {branches.map((b) => b.label || b.branch_key).join(' · ')}
                    </div>
                )}
            </div>

            {branches.map((branch, index) => (
                <span
                    key={branch.branch_key}
                    className="wf-node-branch-label wf-node-branch-generic"
                    style={{ top: handleTop(index, totalHandles) }}
                >
                    {branch.label || branch.branch_key}
                </span>
            ))}

            {branches.map((branch, index) => (
                <Handle
                    key={branch.branch_key}
                    type="source"
                    position={Position.Right}
                    id={branch.branch_key}
                    style={{ top: handleTop(index, totalHandles) }}
                />
            ))}
        </div>
    );
}
