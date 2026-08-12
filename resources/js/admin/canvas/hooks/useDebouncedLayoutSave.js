import { useCallback, useEffect, useRef } from 'react';

/**
 * useDebouncedLayoutSave
 *
 * Acumula posiciones de nodos arrastrados en el canvas y las persiste en
 * batch tras `delayMs` de inactividad, en vez de disparar un guardado por
 * cada `onNodeDragStop`.
 *
 * Uso típico en el componente del canvas:
 *
 *   const queuePosition = useDebouncedLayoutSave(saveLayoutFn);
 *   <ReactFlow onNodeDragStop={(evt, node) =>
 *     queuePosition(node.id, node.position.x, node.position.y)
 *   } />
 *
 * @param {(payload: Array<{id:number, position_x:number, position_y:number}>) => void} saveLayoutFn
 *   Función que recibe el arreglo de posiciones pendientes y las persiste
 *   (p. ej. un POST a /admin/workflows/{id}/steps/layout).
 * @param {number} delayMs Milisegundos de inactividad antes de guardar (default 800).
 * @returns {(stepId: string|number, x: number, y: number) => void} queuePosition
 */
export function useDebouncedLayoutSave(saveLayoutFn, delayMs = 800) {
    const pendingRef = useRef(new Map());
    const timerRef = useRef(null);
    const saveLayoutFnRef = useRef(saveLayoutFn);

    // Mantiene la referencia actualizada sin forzar que queuePosition/flush
    // se recreen cada vez que el componente padre pase una nueva closure.
    useEffect(() => {
        saveLayoutFnRef.current = saveLayoutFn;
    }, [saveLayoutFn]);

    // Limpia el timer pendiente si el componente se desmonta antes de que dispare.
    useEffect(() => {
        return () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
        };
    }, []);

    const flush = useCallback(() => {
        const pending = pendingRef.current;
        timerRef.current = null;

        if (pending.size === 0) return;

        const payload = Array.from(pending.entries()).map(([id, { x, y }]) => ({
            id: Number(id),
            position_x: x,
            position_y: y,
        }));

        pendingRef.current = new Map();

        if (typeof saveLayoutFnRef.current === 'function') {
            saveLayoutFnRef.current(payload);
        }
    }, []);

    const queuePosition = useCallback(
        (stepId, x, y) => {
            pendingRef.current.set(stepId, { x, y });

            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }
            timerRef.current = setTimeout(flush, delayMs);
        },
        [delayMs, flush]
    );

    return queuePosition;
}

export default useDebouncedLayoutSave;
