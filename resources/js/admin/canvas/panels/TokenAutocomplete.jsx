import { useEffect, useMemo, useRef, useState } from 'react';

/**
 * TokenAutocomplete.jsx (Fase 19)
 *
 * Autocompletado de variables `{{ campo.path }}` para los textareas de
 * configuración JSON del canvas (trigger config, action config genérico,
 * action config JSON de `external_db_query`). Porta el algoritmo YA probado
 * de resources/js/admin/variable-picker.js (detección de "{{" antes del
 * cursor vía regex + dropdown flotante posicionado con getBoundingClientRect()
 * + mirror-div para calcular coordenadas de caret) a un componente React
 * controlado -- sin delegación global sobre `document` como el original,
 * aquí todo vive en el propio `onKeyDown`/`onChange` del textarea.
 *
 * El catálogo de sugerencias sale de `catalog.modules` (ya cargado por el
 * endpoint /canvas, Fase 18) -- sin fetch nuevo. Para cada módulo se sugieren
 * sus `fields` planos y, para cada `relations`, si el nombre de la relación
 * (normalizado a snake_case) coincide con el `type` de otro módulo del
 * registro, también sus campos anidados (p. ej. `customer.email`).
 */

const TRIGGER_REGEX = /\{\{\s*([a-zA-Z0-9_.]*)$/;

function toSnakeCase(str) {
    return String(str || '')
        .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
        .toLowerCase();
}

function singularize(str) {
    return str.endsWith('s') && !str.endsWith('ss') ? str.slice(0, -1) : str;
}

/**
 * Construye la lista de sugerencias { path, hint } para un módulo dado,
 * a partir de catalog.modules (array de { type, label, group, fields, relations }).
 */
export function buildTokenSuggestions(moduleType, modules) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);
    if (!entry) return [];

    const suggestions = [];
    const seen = new Set();

    function push(path, hint) {
        if (seen.has(path)) return;
        seen.add(path);
        suggestions.push({ path, hint });
    }

    (entry.fields || []).forEach((field) => push(field, entry.label || moduleType));

    (entry.relations || []).forEach((relation) => {
        const snake = toSnakeCase(relation);
        push(snake, 'relación');

        const candidateTypes = [snake, singularize(snake)];
        const relatedEntry = list.find((m) => candidateTypes.includes(m.type));
        if (relatedEntry) {
            (relatedEntry.fields || []).forEach((field) => {
                push(`${snake}.${field}`, relatedEntry.label || relatedEntry.type);
            });
        }
    });

    return suggestions;
}

function filterSuggestions(suggestions, term) {
    if (!term) return suggestions.slice(0, 30);
    const t = term.toLowerCase();
    return suggestions.filter((s) => s.path.toLowerCase().includes(t)).slice(0, 30);
}

const MIRROR_PROPS = [
    'boxSizing', 'width', 'overflowX', 'overflowY',
    'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'borderStyle',
    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
    'fontStyle', 'fontVariant', 'fontWeight', 'fontSize', 'lineHeight', 'fontFamily',
    'textAlign', 'textTransform', 'textIndent', 'letterSpacing', 'wordSpacing',
    'whiteSpace', 'wordWrap', 'wordBreak',
];

function getCaretCoordinates(textarea, position) {
    const style = getComputedStyle(textarea);
    const mirror = document.createElement('div');
    mirror.style.position = 'absolute';
    mirror.style.visibility = 'hidden';
    mirror.style.whiteSpace = 'pre-wrap';
    mirror.style.wordWrap = 'break-word';
    mirror.style.top = '0';
    mirror.style.left = '-9999px';
    MIRROR_PROPS.forEach((p) => {
        mirror.style[p] = style[p];
    });
    document.body.appendChild(mirror);

    mirror.textContent = textarea.value.substring(0, position);
    const marker = document.createElement('span');
    marker.textContent = textarea.value.substring(position) || '.';
    mirror.appendChild(marker);

    const rect = textarea.getBoundingClientRect();
    const markerRect = marker.getBoundingClientRect();
    const mirrorRect = mirror.getBoundingClientRect();
    const lineHeight = parseInt(style.lineHeight, 10) || 16;

    const coords = {
        top: rect.top + (markerRect.top - mirrorRect.top) - textarea.scrollTop + lineHeight,
        left: rect.left + (markerRect.left - mirrorRect.left) - textarea.scrollLeft,
    };

    document.body.removeChild(mirror);

    return coords;
}

/**
 * <TokenAutocompleteTextarea> -- reemplazo drop-in de un <textarea>
 * controlado, con dropdown de sugerencias de variables `{{ }}`.
 *
 * Props: id, className, rows, value, onValueChange(newValue), placeholder,
 * spellCheck, moduleType (workflow.type actual), modules (catalog.modules).
 */
export default function TokenAutocompleteTextarea({
    id,
    className,
    rows,
    value,
    onValueChange,
    placeholder,
    spellCheck,
    moduleType,
    modules,
}) {
    const textareaRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [coords, setCoords] = useState({ top: 0, left: 0 });
    const matchRef = useRef({ start: 0, length: 0 });

    const suggestions = useMemo(() => buildTokenSuggestions(moduleType, modules), [moduleType, modules]);

    function closeMenu() {
        setOpen(false);
        setItems([]);
        setActiveIndex(-1);
    }

    function evaluateTrigger(text, cursor) {
        const before = text.slice(0, cursor);
        const match = before.match(TRIGGER_REGEX);

        if (!match || !suggestions.length) {
            closeMenu();
            return;
        }

        const term = match[1] || '';
        const filtered = filterSuggestions(suggestions, term);

        if (!filtered.length) {
            closeMenu();
            return;
        }

        matchRef.current = { start: cursor - match[0].length, length: match[0].length };
        setItems(filtered);
        setActiveIndex(0);
        setOpen(true);
        if (textareaRef.current) {
            setCoords(getCaretCoordinates(textareaRef.current, cursor));
        }
    }

    function handleChange(e) {
        const newValue = e.target.value;
        onValueChange(newValue);
        evaluateTrigger(newValue, e.target.selectionStart);
    }

    function applySelection(path) {
        const el = textareaRef.current;
        if (!el) return;
        const { start, length } = matchRef.current;
        const before = value.slice(0, start);
        const after = value.slice(start + length);
        const token = `{{ ${path} }}`;
        const newValue = before + token + after;
        onValueChange(newValue);
        closeMenu();

        // El nuevo value llega vía prop en el próximo render; posicionamos el
        // cursor después de que React vuelva a pintar el textarea.
        requestAnimationFrame(() => {
            if (!textareaRef.current) return;
            const newCursor = start + token.length;
            textareaRef.current.focus();
            textareaRef.current.setSelectionRange(newCursor, newCursor);
        });
    }

    function handleKeyDown(e) {
        if (!open) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeMenu();
        } else if (e.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            applySelection(items[activeIndex].path);
        }
    }

    function handleBlur() {
        // Delay para que el mousedown del <li> (ver onMouseDown) alcance a
        // dispararse antes de que el blur cierre el menú.
        window.setTimeout(closeMenu, 150);
    }

    useEffect(() => {
        if (!open) return undefined;
        function onScroll() {
            if (textareaRef.current) {
                setCoords(getCaretCoordinates(textareaRef.current, textareaRef.current.selectionStart));
            }
        }
        window.addEventListener('scroll', onScroll, true);
        return () => window.removeEventListener('scroll', onScroll, true);
    }, [open]);

    return (
        <div className="wf-token-autocomplete-wrap">
            <textarea
                id={id}
                ref={textareaRef}
                className={className}
                rows={rows}
                value={value}
                placeholder={placeholder}
                spellCheck={spellCheck}
                onChange={handleChange}
                onKeyDown={handleKeyDown}
                onBlur={handleBlur}
            />
            {open && items.length > 0 && (
                <ul
                    className="wf-token-menu"
                    style={{ top: coords.top, left: coords.left }}
                >
                    {items.map((item, i) => (
                        <li
                            key={item.path}
                            className={`wf-token-menu-item ${i === activeIndex ? 'is-active' : ''}`}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                applySelection(item.path);
                            }}
                            onMouseEnter={() => setActiveIndex(i)}
                        >
                            <span className="wf-token-menu-item-path">{'{{ ' + item.path + ' }}'}</span>
                            <span className="wf-token-menu-item-hint">{item.hint}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
