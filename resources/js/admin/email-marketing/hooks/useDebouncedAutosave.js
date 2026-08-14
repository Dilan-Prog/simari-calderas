import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * useDebouncedAutosave
 *
 * Adaptado de resources/js/admin/canvas/hooks/useDebouncedLayoutSave.js:
 * mismo patrón de ref + setTimeout de idle-flush, pero genérico (acepta
 * cualquier saveFn y cualquier forma de payload) en vez de estar acoplado
 * a posiciones de nodos del canvas. Lo usa el editor de plantillas del
 * agente hermano (armador de bloques / editor de código) para autoguardar
 * sin acoplarse a esta implementación.
 *
 * @param {(payload: any) => (Promise<any>|void)} saveFn función que persiste el payload más reciente.
 * @param {number} delayMs milisegundos de inactividad antes de guardar (default 800).
 * @returns {{ triggerSave: (payload: any) => void, flushNow: () => void, status: 'idle'|'saving'|'saved' }}
 *   triggerSave() reemplaza el payload pendiente y reinicia el debounce.
 *   flushNow() cancela el timer pendiente y guarda de inmediato (botón "Guardar" explícito).
 */
export function useDebouncedAutosave(saveFn, delayMs = 800) {
  const [status, setStatus] = useState('idle');

  const pendingRef = useRef(undefined);
  const hasPendingRef = useRef(false);
  const timerRef = useRef(null);
  const saveFnRef = useRef(saveFn);
  const mountedRef = useRef(true);

  useEffect(() => {
    saveFnRef.current = saveFn;
  }, [saveFn]);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (timerRef.current) {
        clearTimeout(timerRef.current);
        timerRef.current = null;
      }
    };
  }, []);

  const runSave = useCallback(async () => {
    if (!hasPendingRef.current) return;

    const payload = pendingRef.current;
    hasPendingRef.current = false;
    pendingRef.current = undefined;

    if (typeof saveFnRef.current !== 'function') return;

    if (mountedRef.current) setStatus('saving');

    try {
      await saveFnRef.current(payload);
      if (mountedRef.current) setStatus('saved');
    } catch (e) {
      if (mountedRef.current) setStatus('idle');
      throw e;
    }
  }, []);

  const flushNow = useCallback(() => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
    return runSave();
  }, [runSave]);

  const triggerSave = useCallback(
    (payload) => {
      pendingRef.current = payload;
      hasPendingRef.current = true;

      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
      timerRef.current = setTimeout(() => {
        timerRef.current = null;
        runSave();
      }, delayMs);
    },
    [delayMs, runSave]
  );

  return { triggerSave, flushNow, status };
}

export default useDebouncedAutosave;
