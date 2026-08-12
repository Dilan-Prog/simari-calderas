/**
 * fromFlow.js
 *
 * Traduce eventos/acciones del canvas de React Flow de vuelta al formato
 * que el backend espera para persistir cambios sobre los steps de un
 * Workflow (updateStep / deleteStep).
 */

/**
 * Dado un objeto de conexión de React Flow (evento onConnect:
 * {source, target, sourceHandle}), retorna el payload a enviar en
 * updateStep(target) para reflejar la nueva relación padre/rama.
 *
 * @param {{source:string, target:string, sourceHandle?:string|null}} connection
 * @param {Array} steps - no se usa actualmente, se recibe para simetría de
 *   la API del módulo y por si a futuro se necesita validar la conexión
 *   contra el árbol de steps existente.
 * @returns {{parent_step_id: number|null, branch_key: string|null}}
 */
export function applyConnection(connection, steps) {
    const { source, sourceHandle } = connection;

    if (source === 'trigger') {
        return {
            parent_step_id: null,
            branch_key: null,
        };
    }

    return {
        parent_step_id: Number(source),
        branch_key: sourceHandle ?? null,
    };
}

/**
 * Traduce el id de nodo de React Flow (string) al id numérico de step a
 * pasar a deleteStep. Passthrough simple, existe para simetría de la API
 * del módulo y para centralizar cualquier validación futura (por ejemplo,
 * rechazar el borrado del nodo 'trigger').
 *
 * @param {string} nodeId
 * @returns {number}
 */
export function applyNodeDeletion(nodeId) {
    return Number(nodeId);
}
