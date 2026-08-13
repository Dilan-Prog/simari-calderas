import { useState, useRef } from 'react';
import { resolveEnumSource } from './TokenAutocomplete.jsx';

/**
 * <FieldAutocompleteInput> -- input de una línea con dropdown de sugerencias,
 * para el paso Condición del canvas (campo/valor). A diferencia de
 * <TokenAutocompleteTextarea> (multilínea, JSON, posición de cursor), aquí
 * todo el valor del input ES el término de búsqueda -- se filtra la lista
 * completa de sugerencias contra el valor actual, sin parsear posición de
 * cursor.
 *
 * Props:
 *   value, onChange(newValue) -- controlado, igual convención que el resto
 *     del inspector.
 *   source -- string de fuente para resolveEnumSource (ver TokenAutocomplete.jsx),
 *     ej. 'module_fields' para el campo "campo", o 'field_value:dynamic' para
 *     el campo "valor" (usa dynamicFieldText en ese caso).
 *   dynamicFieldText -- SOLO necesario cuando source === 'field_value:dynamic':
 *     el nombre de campo actualmente elegido (ej. el valor de conditionField),
 *     pasado ya envuelto como si fuera un fragmento de JSON `"field": "<valor>"`
 *     para que resolveEnumSource lo pueda extraer con su regex interno tal
 *     cual -- NO reimplementes esa extracción aquí, simplemente construye
 *     ese string y pásalo como el parámetro `currentText` de resolveEnumSource.
 *   moduleType, modules, fieldValueSources, actionValueSources, localValueSources --
 *     mismos props que ya recibe NodeInspector, se re-exponen tal cual.
 *   placeholder, className -- pass-through al <input>.
 */
export default function FieldAutocompleteInput({
    id,
    value,
    onChange,
    source,
    dynamicFieldText,
    moduleType,
    modules,
    fieldValueSources,
    actionValueSources,
    localValueSources,
    placeholder,
    className,
}) {
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const wrapRef = useRef(null);

    const ctx = { moduleType, modules, fieldValueSources, actionValueSources, localValueSources };
    const currentText = source === 'field_value:dynamic' ? `"field": "${dynamicFieldText || ''}"` : '';
    const allSuggestions = resolveEnumSource(source, ctx, currentText);

    const term = String(value || '').toLowerCase();
    const filtered = term
        ? allSuggestions.filter((s) => String(s.value).toLowerCase().includes(term)).slice(0, 30)
        : allSuggestions.slice(0, 30);

    function handleChange(e) {
        onChange(e.target.value);
        setOpen(true);
        setActiveIndex(-1);
    }

    function select(item) {
        onChange(item.value);
        setOpen(false);
        setActiveIndex(-1);
    }

    function handleKeyDown(e) {
        if (!open || filtered.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, filtered.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
        } else if (e.key === 'Enter' && activeIndex >= 0 && filtered[activeIndex]) {
            e.preventDefault();
            select(filtered[activeIndex]);
        }
    }

    function handleBlur() {
        // Delay para que el mousedown del <li> alcance a dispararse antes de
        // que el blur cierre el menú -- mismo patrón que TokenAutocompleteTextarea.
        window.setTimeout(() => setOpen(false), 150);
    }

    return (
        <div className="wf-field-autocomplete-wrap" ref={wrapRef} style={{ position: 'relative' }}>
            <input
                id={id}
                type="text"
                className={className}
                value={value}
                placeholder={placeholder}
                onChange={handleChange}
                onFocus={() => setOpen(true)}
                onKeyDown={handleKeyDown}
                onBlur={handleBlur}
            />
            {open && filtered.length > 0 && (
                <ul className="wf-token-menu" style={{ position: 'absolute', top: '100%', left: 0, right: 0 }}>
                    {filtered.map((item, i) => (
                        <li
                            key={item.value}
                            className={`wf-token-menu-item ${i === activeIndex ? 'is-active' : ''}`}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                select(item);
                            }}
                            onMouseEnter={() => setActiveIndex(i)}
                        >
                            <span className="wf-token-menu-item-path">{item.value}</span>
                            <span className="wf-token-menu-item-hint">{item.hint}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
