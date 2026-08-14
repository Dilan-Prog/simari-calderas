import { Handle, Position } from '@xyflow/react';

/**
 * Nodo "Switch" (multi-rama) del canvas de Workflows.
 *
 * `data` es directamente el step (step_type === 'switch'):
 * action_config = { rules: [{branch_key, field, operator, value, label}, ...] }.
 *
 * Un target handle a la izquierda + un source handle por cada regla
 * (id=rule.branch_key) MÁS un handle fijo adicional id="default" al final
 * (para cuando ninguna regla matchea). Los handles se reparten
 * verticalmente para que se vean claros cuando hay varias reglas.
 */
function handleTop(index, total) {
    if (total <= 1) return '50%';
    const start = 18;
    const end = 82;
    const pct = start + ((end - start) * index) / (total - 1);
    return `${pct}%`;
}

export default function SwitchNode({ data, selected }) {
    const rules = Array.isArray(data?.action_config?.rules) ? data.action_config.rules : [];
    const totalHandles = rules.length + 1; // +1 por "default"

    return (
        <div className={'wf-node-card wf-node-switch' + (selected ? ' is-selected' : '')}>
            <Handle type="target" position={Position.Left} />

            <div className="wf-node-card-header">
                <span className="wf-node-badge">SW</span>
                <div className="wf-node-titles">
                    <div className="wf-node-title">Switch</div>
                    <div className="wf-node-subtitle">{rules.length} regla{rules.length === 1 ? '' : 's'}</div>
                </div>
            </div>

            <div className="wf-node-body">
                {rules.length === 0 ? (
                    <div className="wf-node-config">Sin reglas configuradas</div>
                ) : (
                    <div className="wf-node-config">
                        {rules.map((r) => r.label || r.branch_key).join(' · ')}
                    </div>
                )}
            </div>

            {rules.map((rule, index) => (
                <span
                    key={rule.branch_key}
                    className="wf-node-branch-label wf-node-branch-generic"
                    style={{ top: handleTop(index, totalHandles) }}
                >
                    {rule.label || rule.branch_key}
                </span>
            ))}
            <span
                className="wf-node-branch-label wf-node-branch-default"
                style={{ top: handleTop(rules.length, totalHandles) }}
            >
                Otro caso
            </span>

            {rules.map((rule, index) => (
                <Handle
                    key={rule.branch_key}
                    type="source"
                    position={Position.Right}
                    id={rule.branch_key}
                    style={{ top: handleTop(index, totalHandles) }}
                />
            ))}
            <Handle
                type="source"
                position={Position.Right}
                id="default"
                style={{ top: handleTop(rules.length, totalHandles) }}
            />
        </div>
    );
}
