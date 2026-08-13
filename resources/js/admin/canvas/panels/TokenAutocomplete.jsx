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
const EVENT_KEY_REGEX = /"event"\s*:\s*"([a-zA-Z_]*)$/;
const FIELD_KEY_REGEX = /"field"\s*:\s*"([a-zA-Z0-9_]*)$/;
const OPERATOR_KEY_REGEX = /"operator"\s*:\s*"([a-zA-Z_]*)$/;
// Valor objetivo de "value"/"to_stage_id" (alias legado) -- se sugiere solo
// cuando el campo vigilado por el trigger tiene una fuente de datos real
// registrada en catalog.field_value_sources (ej. "pipeline_stage_id" -> lista
// de PipelineStage reales), para no tener que adivinar el id a mano. Cubre
// tanto el caso con comilla de apertura ya escrita (flujo normal: se llega
// aquí después de elegir "value" en el modo trigger-key, que sí abre comilla)
// como el caso sin comillas (alguien escribe el número directo a mano).
const VALUE_TARGET_KEY_REGEX = /"(?:value|to_stage_id)"\s*:\s*"([a-zA-Z0-9_]*)$/;
const VALUE_TARGET_KEY_UNQUOTED_REGEX = /"(?:value|to_stage_id)"\s*:\s*([0-9]*)$/;
// Fase 22: detecta que se está escribiendo una CLAVE nueva (no un valor) justo
// después de "{" o "," -- ej. `{"` o `, "`. Por construcción no coincide con
// los regex de valor de arriba (ahí la comilla viene después de ": ", no de
// "{"/",").
const TRIGGER_KEY_REGEX = /[{,]\s*"([a-zA-Z_]*)$/;

const EVENT_VALUES = [
    { value: 'created', hint: 'al crear el registro' },
    { value: 'updated', hint: 'al actualizar el registro' },
    { value: 'deleted', hint: 'al borrar el registro' },
    { value: 'stale', hint: 'sin actividad en N horas' },
];

const OPERATOR_VALUES = [
    { value: 'eq', hint: 'igual a (default)' },
    { value: 'neq', hint: 'distinto de' },
    { value: 'gt', hint: 'mayor que' },
    { value: 'gte', hint: 'mayor o igual que' },
    { value: 'lt', hint: 'menor que' },
    { value: 'lte', hint: 'menor o igual que' },
];

// Fase 22: las 6 claves reales del esquema del Trigger (ampliado por la Fase
// 23). `to_stage_id` es alias legado y nunca se sugiere como clave nueva.
const TRIGGER_KEYS = [
    { value: 'event', hint: 'evento que dispara (created/updated/deleted/stale)' },
    { value: 'field', hint: 'columna a vigilar (solo con event=updated)' },
    { value: 'operator', hint: 'comparador (eq/neq/gt/gte/lt/lte)' },
    { value: 'value', hint: 'valor objetivo de la comparación' },
    { value: 'from_value', hint: 'valor anterior exigido (transición A→B)' },
    { value: 'hours', hint: 'horas sin actividad (solo con event=stale)', numeric: true },
];

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
export function buildTokenSuggestions(moduleType, modules, workflowVariables) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);

    const suggestions = [];
    const seen = new Set();

    function push(path, hint) {
        if (seen.has(path)) return;
        seen.add(path);
        suggestions.push({ path, hint });
    }

    // Fase 24: variables globales/de contexto de ejecución -- siempre
    // presentes, sin importar el módulo activo del workflow.
    push('hoy', 'fecha actual (YYYY-MM-DD)');
    push('ahora', 'fecha y hora actual');
    push('empresa.nombre', 'nombre de la empresa (config app.name)');
    push('actor.nombre', 'usuario que disparó el evento (o vacío)');
    push('actor.email', 'email del usuario que disparó el evento (o vacío)');

    // Variables nombradas por el usuario en el panel "Variables" del
    // workflow (WorkflowVariable) -- sin punto, se resuelven vía
    // WorkflowVariable::resolveValue() en el backend. Incluye tanto las
    // propias de este workflow como las de scope Global (workflow_id null,
    // ya vienen mezcladas en el mismo array desde WorkflowController::canvas()).
    (Array.isArray(workflowVariables) ? workflowVariables : []).forEach((v) => {
        if (!v || !v.name) return;
        push(v.name, v.workflow_id == null ? 'variable global' : 'variable del workflow');
    });

    if (entry) {
        (entry.fields || []).forEach((field) => push(field, entry.label || moduleType));

        // Fase 24: valor ANTERIOR de cada campo plano del módulo activo
        // (disponible solo cuando el trigger capturó `from_value`/`field`).
        (entry.fields || []).forEach((field) =>
            push(`_previous.${field}`, `valor anterior de ${entry.label || moduleType}`)
        );

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
    }

    return suggestions;
}

/**
 * Sugerencias de valor real para la clave "field" del Trigger (Fase 21):
 * solo columnas planas del módulo activo (nunca relaciones anidadas --
 * "field" alimenta Model::wasChanged($field)/$model->{$field}, que exigen
 * una columna literal, no un path con punto).
 */
export function buildFieldKeySuggestions(moduleType, modules) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);
    if (!entry) return [];
    return (entry.fields || []).map((field) => ({ value: field, hint: entry.label || moduleType }));
}

/**
 * Sugerencias de valor real para la clave "event" del Trigger (Fase 21):
 * lista fija created/updated, más stale solo si el módulo activo soporta
 * ese trigger (catalog.modules[type].supports_stale, ya cargado sin fetch
 * nuevo -- mismo dato que ya usa AutomatableModuleRegistry en el backend).
 */
export function buildEventKeySuggestions(moduleType, modules) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);
    const supportsStale = !!(entry && entry.supports_stale);
    return EVENT_VALUES.filter((e) => e.value !== 'stale' || supportsStale);
}

/**
 * Sugerencias de valor real para la clave "operator" del Trigger (Fase 23):
 * lista fija, no depende del módulo activo.
 */
export function buildOperatorKeySuggestions() {
    return OPERATOR_VALUES;
}

/**
 * Sugerencias de valor real para "value"/"to_stage_id" cuando el campo
 * vigilado por el trigger (su clave "field", ya escrita en el mismo JSON)
 * tiene una fuente de datos registrada en catalog.field_value_sources (Fase
 * ad-hoc post-24 -- ej. "pipeline_stage_id" -> etapas reales de Pipeline).
 * Sin esto había que adivinar a mano a qué etapa corresponde un id como 4.
 */
export function buildValueTargetSuggestions(currentText, fieldValueSources) {
    const text = String(currentText || '');
    const fieldMatch = text.match(/"field"\s*:\s*"([a-zA-Z0-9_]+)"/);
    const field = fieldMatch ? fieldMatch[1] : null;
    if (!field) return [];
    const list = fieldValueSources && fieldValueSources[field];
    return Array.isArray(list) ? list : [];
}

/**
 * Sugerencias de CLAVE nueva del JSON del Trigger (Fase 22): las 6 claves
 * reales del esquema, siempre, sin exigir orden -- salvo las que ya aparecen
 * en el texto (evita duplicados), detectado con una regex simple por clave,
 * no un parseo JSON estricto (el usuario todavía puede estar escribiendo).
 */
export function buildTriggerKeySuggestions(currentText) {
    const text = String(currentText || '');
    return TRIGGER_KEYS.filter((k) => !new RegExp(`"${k.value}"\\s*:`).test(text));
}

function filterSuggestions(suggestions, term, key) {
    const field = key || 'path';
    if (!term) return suggestions.slice(0, 30);
    const t = term.toLowerCase();
    return suggestions.filter((s) => String(s[field]).toLowerCase().includes(t)).slice(0, 30);
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
 * spellCheck, moduleType (workflow.type actual), modules (catalog.modules),
 * workflowVariables (WorkflowVariable[] del workflow + globales, ya cargadas
 * en WorkflowCanvasApp -- se sugieren sin punto, ej. {{ mi_variable }}),
 * schemaAware (Fase 21 -- solo el textarea del Trigger lo activa: además del
 * `{{ }}` de siempre, sugiere valores reales para las claves JSON "event" y
 * "field" del propio esquema del trigger).
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
    workflowVariables,
    fieldValueSources,
    schemaAware,
}) {
    const textareaRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [coords, setCoords] = useState({ top: 0, left: 0 });
    const matchRef = useRef({ start: 0, length: 0, mode: 'token' });

    const tokenSuggestions = useMemo(
        () => buildTokenSuggestions(moduleType, modules, workflowVariables),
        [moduleType, modules, workflowVariables]
    );
    const eventSuggestions = useMemo(() => buildEventKeySuggestions(moduleType, modules), [moduleType, modules]);
    const fieldSuggestions = useMemo(() => buildFieldKeySuggestions(moduleType, modules), [moduleType, modules]);
    const operatorSuggestions = useMemo(() => buildOperatorKeySuggestions(), []);

    function closeMenu() {
        setOpen(false);
        setItems([]);
        setActiveIndex(-1);
    }

    function openMenu(mode, replaceLength, filtered, cursor) {
        matchRef.current = { start: cursor - replaceLength, length: replaceLength, mode };
        setItems(filtered);
        setActiveIndex(0);
        setOpen(true);
        if (textareaRef.current) {
            setCoords(getCaretCoordinates(textareaRef.current, cursor));
        }
    }

    function evaluateTrigger(text, cursor) {
        const before = text.slice(0, cursor);

        if (schemaAware) {
            // Modo event/field: solo se reemplaza el término parcial ya
            // escrito (grupo capturado), NUNCA el match completo -- este
            // incluye la clave "event"/"field", los dos puntos y la comilla
            // de apertura, que el usuario ya escribió a mano y deben quedar
            // intactos (sustituirlos borraba el prefijo del JSON, bug real
            // detectado en QA manual).
            const eventMatch = before.match(EVENT_KEY_REGEX);
            if (eventMatch) {
                const term = eventMatch[1] || '';
                const filtered = filterSuggestions(eventSuggestions, term, 'value');
                if (filtered.length) return openMenu('event', term.length, filtered, cursor);
                return closeMenu();
            }

            const fieldMatch = before.match(FIELD_KEY_REGEX);
            if (fieldMatch) {
                const term = fieldMatch[1] || '';
                const filtered = filterSuggestions(fieldSuggestions, term, 'value');
                if (filtered.length) return openMenu('field', term.length, filtered, cursor);
                return closeMenu();
            }

            const operatorMatch = before.match(OPERATOR_KEY_REGEX);
            if (operatorMatch) {
                const term = operatorMatch[1] || '';
                const filtered = filterSuggestions(operatorSuggestions, term, 'value');
                if (filtered.length) return openMenu('operator', term.length, filtered, cursor);
                return closeMenu();
            }

            const valueTargetMatch = before.match(VALUE_TARGET_KEY_REGEX) || before.match(VALUE_TARGET_KEY_UNQUOTED_REGEX);
            if (valueTargetMatch) {
                const term = valueTargetMatch[1] || '';
                const valueTargetSuggestions = buildValueTargetSuggestions(text, fieldValueSources);
                const filtered = filterSuggestions(valueTargetSuggestions, term, 'value');
                if (filtered.length) return openMenu('value-target', term.length, filtered, cursor);
                return closeMenu();
            }

            // Modo trigger-key (Fase 22): se está escribiendo una clave nueva
            // justo después de "{" o ",". Solo se reemplaza el término
            // parcial ya escrito (grupo capturado) -- el "{"/","/comilla de
            // apertura los escribió el usuario y deben quedar intactos.
            const keyMatch = before.match(TRIGGER_KEY_REGEX);
            if (keyMatch) {
                const term = keyMatch[1] || '';
                const filtered = filterSuggestions(buildTriggerKeySuggestions(text), term, 'value');
                if (filtered.length) return openMenu('trigger-key', term.length, filtered, cursor);
                return closeMenu();
            }
        }

        const match = before.match(TRIGGER_REGEX);
        if (!match || !tokenSuggestions.length) {
            closeMenu();
            return;
        }

        const filtered = filterSuggestions(tokenSuggestions, match[1] || '', 'path');
        if (!filtered.length) {
            closeMenu();
            return;
        }

        // Modo token: sí se reemplaza el match completo ("{{parcial"), porque
        // el resultado debe quedar envuelto en "{{ }}" (ver applySelection).
        openMenu('token', match[0].length, filtered, cursor);
    }

    function handleChange(e) {
        const newValue = e.target.value;
        onValueChange(newValue);
        evaluateTrigger(newValue, e.target.selectionStart);
    }

    function applySelection(item) {
        const el = textareaRef.current;
        if (!el) return;
        const { start, length, mode } = matchRef.current;
        const before = value.slice(0, start);
        const after = value.slice(start + length);
        // Modo token: envuelve en "{{ }}" (interpolación de texto libre).
        // Modo event/field/operator: inserta el valor plano -- ya está dentro
        // de las comillas que el usuario escribió a mano en el JSON.
        // Modo trigger-key: inserta la clave + "": " " (cursor listo para
        // que el modo event/field/operator tome el relevo) -- salvo `hours`,
        // que se inserta sin comillas (siempre numérico, `(int)` en
        // handleModelStale()).
        let insertion;
        if (mode === 'token') {
            insertion = `{{ ${item.path} }}`;
        } else if (mode === 'trigger-key') {
            insertion = item.numeric ? `${item.value}": ` : `${item.value}": "`;
        } else {
            insertion = item.value;
        }
        const newValue = before + insertion + after;
        onValueChange(newValue);
        closeMenu();

        // El nuevo value llega vía prop en el próximo render; posicionamos el
        // cursor después de que React vuelva a pintar el textarea.
        requestAnimationFrame(() => {
            if (!textareaRef.current) return;
            const newCursor = start + insertion.length;
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
            applySelection(items[activeIndex]);
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
                            key={item.path || item.value}
                            className={`wf-token-menu-item ${i === activeIndex ? 'is-active' : ''}`}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                applySelection(item);
                            }}
                            onMouseEnter={() => setActiveIndex(i)}
                        >
                            <span className="wf-token-menu-item-path">
                                {matchRef.current.mode === 'token' ? '{{ ' + item.path + ' }}' : item.value}
                            </span>
                            <span className="wf-token-menu-item-hint">{item.hint}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
