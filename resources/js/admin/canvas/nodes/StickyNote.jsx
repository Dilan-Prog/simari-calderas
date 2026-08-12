import { useEffect, useRef, useState } from 'react';

/**
 * Nota adhesiva del canvas de Workflows.
 *
 * Nodo de React Flow SIN handles: no participa en la ejecución del flujo,
 * es puramente una anotación visual. `data.text` es el contenido inicial;
 * `data.onTextChange(newText)` se dispara al perder el foco del textarea,
 * únicamente si el valor cambió, para que el contenedor persista la nota.
 */
export default function StickyNote({ id, data, selected }) {
    const [value, setValue] = useState(data?.text || '');
    const lastSavedRef = useRef(data?.text || '');

    // Si el texto llega actualizado desde afuera (recarga, undo/redo),
    // sincroniza el estado local para no mostrar contenido obsoleto.
    useEffect(() => {
        const incoming = data?.text || '';
        if (incoming !== lastSavedRef.current) {
            setValue(incoming);
            lastSavedRef.current = incoming;
        }
    }, [data?.text]);

    function handleBlur() {
        if (value === lastSavedRef.current) {
            return;
        }
        lastSavedRef.current = value;
        if (typeof data?.onTextChange === 'function') {
            data.onTextChange(value);
        }
    }

    return (
        <div className={'wf-node-sticky' + (selected ? ' is-selected' : '')} data-node-id={id}>
            <textarea
                className="wf-node-sticky-textarea"
                value={value}
                placeholder="Escribe una nota…"
                onChange={(e) => setValue(e.target.value)}
                onBlur={handleBlur}
            />
        </div>
    );
}
