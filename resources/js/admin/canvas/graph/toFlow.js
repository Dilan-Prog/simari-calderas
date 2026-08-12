/**
 * toFlow.js
 *
 * ============================================================================
 *  SINCRONIZACIÓN MANUAL OBLIGATORIA CON app/Services/WorkflowEngineService.php
 * ============================================================================
 * La función toFlow() de este archivo reconstruye, sólo para fines visuales
 * en el canvas de React Flow, la topología de "quién sigue a quién" dentro
 * de un Workflow a partir del arreglo plano de steps. Esa reconstrucción
 * DEBE reproducir exactamente la misma semántica que el método privado
 * `nextSiblingOf(WorkflowStep $step)` de WorkflowEngineService.php, que es
 * el que el motor usa en tiempo de ejecución para decidir el siguiente step
 * de una inscripción (enrollment).
 *
 * Semántica actual de nextSiblingOf() (PHP):
 *   Dado un step, su "siguiente hermano" es el step con el MISMO
 *   parent_step_id y el MISMO branch_key (ambos null cuentan como iguales),
 *   con 'order' MAYOR al del step dado, tomando el de menor 'order' entre
 *   esos candidatos (es decir, el inmediato siguiente en la secuencia).
 *
 * La función buildPredecessorMap() de este archivo calcula lo mismo pero en
 * sentido inverso (busca el PREDECESOR de cada step: mismo parent_step_id +
 * branch_key, con 'order' MENOR, tomando el mayor de esos) para poder
 * dibujar las edges del canvas.
 *
 * SI EN ALGÚN MOMENTO SE CAMBIA LA LÓGICA DE nextSiblingOf() EN PHP
 * (por ejemplo, para soportar otro criterio de agrupamiento, orden no
 * secuencial, etc.), ESTA FUNCIÓN DEBE ACTUALIZARSE EN CONSECUENCIA. Si no
 * se hace, el canvas mostrará conexiones entre steps que NO coinciden con
 * la ruta que el motor de workflows realmente ejecuta en producción, lo
 * cual puede confundir gravemente a quien edite el workflow visualmente.
 * ============================================================================
 */

import { autoLayoutPositions } from './autoLayout.js';

/**
 * Determina si dos branch_key se consideran "el mismo grupo" (null == null).
 */
function sameBranchKey(a, b) {
    return (a ?? null) === (b ?? null);
}

/**
 * Para cada step, encuentra su predecesor inmediato: el step con mismo
 * parent_step_id y mismo branch_key, con el mayor 'order' que sea menor
 * al 'order' de este step. Debe mantenerse en espejo con nextSiblingOf()
 * de WorkflowEngineService.php (ver comentario al inicio del archivo).
 *
 * @param {Array} steps
 * @returns {Map<number, object|null>} stepId -> step predecesor (o null si no tiene)
 */
function buildPredecessorMap(steps) {
    const predecessorByStepId = new Map();

    for (const step of steps) {
        let predecessor = null;

        for (const candidate of steps) {
            if (candidate.id === step.id) continue;
            if ((candidate.parent_step_id ?? null) !== (step.parent_step_id ?? null)) continue;
            if (!sameBranchKey(candidate.branch_key, step.branch_key)) continue;
            if (candidate.order >= step.order) continue;

            if (predecessor === null || candidate.order > predecessor.order) {
                predecessor = candidate;
            }
        }

        predecessorByStepId.set(step.id, predecessor);
    }

    return predecessorByStepId;
}

/**
 * Construye {nodes, edges} en formato React Flow a partir de los steps
 * planos de un Workflow.
 *
 * @param {Array} steps
 * @param {{id:number, name:string, type:string, enrollment_trigger:*, is_active:boolean}} workflow
 * @param {{action_types?: Object}} [catalog] catálogo de acciones que devuelve el endpoint
 *   /canvas (App\Services\WorkflowActionExecutor::supportedActions()) — se usa sólo para
 *   resolver la etiqueta amigable de un action_type (ej. "create_task" -> "Crear tarea"),
 *   nunca para lógica de negocio.
 * @returns {{nodes: Array, edges: Array}}
 */
export function toFlow(steps, workflow, catalog) {
    const layoutPositions = autoLayoutPositions(steps);
    const actionTypes = catalog?.action_types || {};

    // --- Nodo trigger ---
    // Posición fija en X (-280, una columna a la izquierda de la primera
    // columna de steps, que arranca en x=0) y en Y alineado con el primer
    // nivel (depth 0) de los root steps, que en autoLayoutPositions arranca
    // siempre en y=0.
    const triggerNode = {
        id: 'trigger',
        type: 'trigger',
        position: { x: -280, y: 0 },
        data: {
            enrollment_trigger: workflow.enrollment_trigger,
            workflowType: workflow.type,
        },
        deletable: false,
    };

    const stepNodes = steps.map((step) => {
        const position = step.position_x != null && step.position_y != null
            ? { x: step.position_x, y: step.position_y }
            : layoutPositions.get(step.id);

        const actionLabel = step.action_type
            ? (actionTypes[step.action_type]?.label || null)
            : null;

        return {
            id: String(step.id),
            type: step.step_type,
            position,
            data: actionLabel ? { ...step, action_label: actionLabel } : step,
        };
    });

    const nodes = [triggerNode, ...stepNodes];

    // --- Edges ---
    const predecessorByStepId = buildPredecessorMap(steps);

    const edges = steps.map((step) => {
        const predecessor = predecessorByStepId.get(step.id);

        let source;
        let sourceHandle;

        if (predecessor) {
            source = String(predecessor.id);
            sourceHandle = undefined;
        } else if (step.parent_step_id == null) {
            source = 'trigger';
            sourceHandle = undefined;
        } else {
            source = String(step.parent_step_id);
            sourceHandle = step.branch_key;
        }

        const target = String(step.id);

        const label = step.branch_key === 'yes'
            ? 'Sí'
            : step.branch_key === 'no'
                ? 'No'
                : undefined;

        return {
            id: `e-${source}-${target}`,
            source,
            target,
            sourceHandle,
            type: 'smoothstep',
            label,
        };
    });

    return { nodes, edges };
}
