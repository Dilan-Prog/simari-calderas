/**
 * autoLayout.js
 *
 * Layout determinístico y simple (NO es un algoritmo de grafos tipo dagre)
 * para posicionar steps de un Workflow que no traen position_x/position_y
 * desde el backend.
 *
 * Estrategia (recorrido DFS desde las raíces, ordenadas por 'order'):
 *  - X = depth * 280 (una columna por nivel de profundidad en el árbol).
 *  - Y: cada rama (grupo de hijos de un mismo parent_step_id/branch_key)
 *    arranca alineada con el Y de su padre, y cada hermano sucesivo dentro
 *    de esa misma rama se desplaza +140 respecto al anterior. Al entrar a
 *    una rama distinta (otro padre) el contador de Y se reinicia relativo
 *    al Y de ese nuevo padre, en vez de seguir acumulando globalmente.
 *  - No intenta minimizar cruces de edges ni centrar subárboles; sólo evita
 *    que los nodos queden todos apilados en (0,0).
 */

const LEVEL_X_STEP = 280;
const SIBLING_Y_STEP = 140;

/**
 * @param {Array<{id:number, parent_step_id:number|null, branch_key:string|null, order:number, position_x:number|null, position_y:number|null}>} steps
 * @returns {Map<number, {x:number, y:number}>} posiciones sólo para los steps sin position_x/position_y
 */
export function autoLayoutPositions(steps) {
    const positions = new Map();

    // Agrupa steps por parent_step_id para recorrer hijos rápidamente.
    const childrenByParent = new Map();
    for (const step of steps) {
        const key = step.parent_step_id ?? null;
        if (!childrenByParent.has(key)) {
            childrenByParent.set(key, []);
        }
        childrenByParent.get(key).push(step);
    }
    // Ordena cada grupo de hijos por 'order'.
    for (const group of childrenByParent.values()) {
        group.sort((a, b) => a.order - b.order);
    }

    const hasExplicitPosition = (step) => step.position_x != null && step.position_y != null;

    const visit = (step, depth, y) => {
        const x = depth * LEVEL_X_STEP;

        if (!hasExplicitPosition(step)) {
            positions.set(step.id, { x, y });
        }

        const resolvedY = hasExplicitPosition(step) ? step.position_y : y;

        const children = childrenByParent.get(step.id) || [];
        let childY = resolvedY;
        for (const child of children) {
            visit(child, depth + 1, childY);
            childY += SIBLING_Y_STEP;
        }
    };

    const roots = (childrenByParent.get(null) || []).slice().sort((a, b) => a.order - b.order);

    let rootY = 0;
    for (const root of roots) {
        visit(root, 0, rootY);
        rootY += SIBLING_Y_STEP;
    }

    return positions;
}
