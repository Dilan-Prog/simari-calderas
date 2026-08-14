import { useCallback, useRef, useState } from 'react';

const MAX_HISTORY = 40;

/**
 * useBlockHistory
 *
 * Estado de los bloques del armador + deshacer/rehacer, portado del
 * patrón `hist`/`fut` del mockup (líneas ~960-978): cada `mutate` empuja
 * el estado actual a `hist` (tope 40 pasos), aplica la mutación y limpia
 * la pila de `fut` (redo). `undo`/`redo` mueven un snapshot entre las dos
 * pilas.
 *
 * `hist`/`fut` viven en useRef (no useState): son pilas de snapshots que
 * no necesitan disparar un re-render por sí solas -- solo el `blocks`
 * resultante importa para el render, igual que el mockup los mantenía
 * como campos de instancia fuera de `state`.
 *
 * @param {Array} initialBlocks
 * @returns {{ blocks: Array, mutate: (fn: (blocks: Array) => Array) => void,
 *   undo: () => void, redo: () => void, canUndo: boolean, canRedo: boolean,
 *   setBlocks: (blocks: Array) => void }}
 */
export function useBlockHistory(initialBlocks) {
  const [blocks, setBlocksState] = useState(initialBlocks || []);
  const histRef = useRef([]);
  const futRef = useRef([]);
  // Forzamos re-render cuando cambian las pilas (para canUndo/canRedo)
  // sin duplicar los snapshots en useState.
  const [, forceTick] = useState(0);
  const bump = () => forceTick((n) => n + 1);

  const mutate = useCallback((fn) => {
    setBlocksState((current) => {
      histRef.current.push(JSON.stringify(current));
      if (histRef.current.length > MAX_HISTORY) histRef.current.shift();
      futRef.current = [];
      bump();
      return fn(current);
    });
  }, []);

  const undo = useCallback(() => {
    if (!histRef.current.length) return;
    setBlocksState((current) => {
      futRef.current.push(JSON.stringify(current));
      const prev = histRef.current.pop();
      bump();
      return JSON.parse(prev);
    });
  }, []);

  const redo = useCallback(() => {
    if (!futRef.current.length) return;
    setBlocksState((current) => {
      histRef.current.push(JSON.stringify(current));
      const next = futRef.current.pop();
      bump();
      return JSON.parse(next);
    });
  }, []);

  // Reemplaza el arreglo de bloques completo sin pasar por hist/fut (p.
  // ej. al cargar una plantilla existente o elegir un starter) -- no debe
  // contar como un paso deshacible desde un estado previo irrelevante.
  const setBlocks = useCallback((next) => {
    histRef.current = [];
    futRef.current = [];
    bump();
    setBlocksState(next || []);
  }, []);

  return {
    blocks,
    mutate,
    undo,
    redo,
    canUndo: histRef.current.length > 0,
    canRedo: futRef.current.length > 0,
    setBlocks,
  };
}

export default useBlockHistory;
