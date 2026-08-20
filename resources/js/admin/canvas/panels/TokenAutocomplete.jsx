import { useEffect, useMemo, useRef, useState } from 'react';

/**
 * TokenAutocomplete.jsx (Fase 19, generalizado en Fase 25)
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
 * El catálogo de sugerencias `{{ }}` sale de `catalog.modules` (ya cargado
 * por el endpoint /canvas, Fase 18) -- sin fetch nuevo. Para cada módulo se
 * sugieren sus `fields` planos y, para cada `relations`, si el nombre de la
 * relación (normalizado a snake_case) coincide con el `type` de otro módulo
 * del registro, también sus campos anidados (p. ej. `customer.email`).
 *
 * Fase 25: el modo antes llamado "schemaAware" (Fase 21-23) era exclusivo
 * del Trigger, con toda su lógica de qué claves/valores sugerir hardcodeada
 * a su esquema específico (event/field/operator/value/from_value/hours).
 * Se generalizó a un motor genérico dirigido por un objeto `nodeSchema`
 * (ver resources/js/admin/canvas/panels/nodeConfigSchemas.js) -- el Trigger
 * es ahora el primer consumidor real de este motor, no un caso especial en
 * paralelo. Soporta también esquemas con arrays de objetos anidados (Fase
 * 25, `kind: 'array_of_object'` + `itemSchema`, usado por external_db_query)
 * detectando en qué array-de-objetos está posicionado el cursor mediante
 * conteo de profundidad de brackets hacia atrás desde el cursor.
 */

const TRIGGER_REGEX = /\{\{\s*([a-zA-Z0-9_.]*)$/;
// Detecta que se está escribiendo una CLAVE nueva (no un valor) justo
// después de "{" o "," -- ej. `{"` o `, "`. Por construcción no coincide con
// los regex de valor (ahí la comilla viene después de ": ", no de "{"/",").
const NEW_KEY_REGEX = /[{,]\s*"([a-zA-Z_]*)$/;

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

// Fuentes estáticas usadas por esquemas fuera del Trigger (Fase 25,
// external_db_query). No dependen de ningún dato cargado del backend.
const DB_OPERATION_VALUES = [
    { value: 'select', hint: 'leer filas' },
    { value: 'insert', hint: 'insertar una fila' },
    { value: 'update', hint: 'actualizar filas (requiere where)' },
    { value: 'delete', hint: 'borrar filas (requiere where)' },
];

const VALUE_SOURCE_VALUES = [
    { value: 'literal', hint: 'valor fijo escrito a mano' },
    { value: 'variable', hint: 'nombre de una WorkflowVariable' },
];

const WHERE_OPERATOR_VALUES = [
    { value: '=', hint: 'igual a' },
    { value: '!=', hint: 'distinto de' },
    { value: '>', hint: 'mayor que' },
    { value: '<', hint: 'menor que' },
    { value: '>=', hint: 'mayor o igual que' },
    { value: '<=', hint: 'menor o igual que' },
    { value: 'like', hint: 'coincidencia parcial (LIKE)' },
];

// Fase (call_webhook): métodos HTTP soportados por el ejecutor. Lista fija,
// no depende de ningún dato cargado del backend -- mismo patrón que
// DB_OPERATION_VALUES/WHERE_OPERATOR_VALUES de arriba.
const HTTP_METHOD_VALUES = [
    { value: 'GET', hint: 'leer sin cuerpo' },
    { value: 'POST', hint: 'crear / enviar datos (default)' },
    { value: 'PUT', hint: 'reemplazar un recurso' },
    { value: 'PATCH', hint: 'actualizar parcialmente' },
    { value: 'DELETE', hint: 'borrar un recurso' },
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
 * Sugerencias de valor real para claves con source 'module_fields' (Fase 21,
 * generalizado en Fase 25): solo columnas planas del módulo activo (nunca
 * relaciones anidadas -- "field" alimenta Model::wasChanged($field)/
 * $model->{$field}, que exigen una columna literal, no un path con punto).
 */
export function buildFieldKeySuggestions(moduleType, modules) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);
    if (!entry) return [];
    return (entry.fields || []).map((field) => ({ value: field, hint: entry.label || moduleType }));
}

/**
 * Sugerencias de valor real para source 'trigger_event' (Fase 21): lista
 * fija created/updated, más stale solo si el módulo activo soporta ese
 * trigger (catalog.modules[type].supports_stale, ya cargado sin fetch
 * nuevo -- mismo dato que ya usa AutomatableModuleRegistry en el backend).
 */
export function buildEventKeySuggestions(moduleType, modules) {
    const list = Array.isArray(modules) ? modules : [];
    const entry = list.find((m) => m.type === moduleType);
    const supportsStale = !!(entry && entry.supports_stale);
    return EVENT_VALUES.filter((e) => e.value !== 'stale' || supportsStale);
}

/**
 * Sugerencias de valor real para source 'trigger_operator' (Fase 23): lista
 * fija, no depende del módulo activo.
 */
export function buildOperatorKeySuggestions() {
    return OPERATOR_VALUES;
}

/**
 * Fase 25: resuelve cualquier `source` declarado en nodeConfigSchemas.js a
 * una lista de sugerencias { value, hint }. Punto único de traducción entre
 * el esquema declarativo y los datos reales disponibles en el momento (props
 * del componente + texto actual del textarea, para el caso dinámico
 * 'field_value:dynamic').
 *
 * ctx: { moduleType, modules, fieldValueSources, actionValueSources, localValueSources }
 */
export function resolveEnumSource(source, ctx, currentText) {
    if (!source) return [];

    if (source === 'trigger_event') return buildEventKeySuggestions(ctx.moduleType, ctx.modules);
    if (source === 'trigger_operator') return buildOperatorKeySuggestions();
    if (source === 'module_fields') return buildFieldKeySuggestions(ctx.moduleType, ctx.modules);

    if (source === 'static:db_operations') return DB_OPERATION_VALUES;
    if (source === 'static:value_sources') return VALUE_SOURCE_VALUES;
    if (source === 'static:where_operators') return WHERE_OPERATOR_VALUES;
    if (source === 'static:http_methods') return HTTP_METHOD_VALUES;

    if (source.startsWith('field_value:')) {
        const target = source.slice('field_value:'.length);
        const field =
            target === 'dynamic'
                ? (String(currentText || '').match(/"field"\s*:\s*"([a-zA-Z0-9_]+)"/) || [])[1]
                : target;
        if (!field) return [];
        const list = ctx.fieldValueSources && ctx.fieldValueSources[field];
        return Array.isArray(list) ? list : [];
    }

    if (source.startsWith('catalog:')) {
        const key = source.slice('catalog:'.length);
        const list = ctx.actionValueSources && ctx.actionValueSources[key];
        return Array.isArray(list) ? list : [];
    }

    if (source.startsWith('local:')) {
        const key = source.slice('local:'.length);
        const list = ctx.localValueSources && ctx.localValueSources[key];
        return Array.isArray(list) ? list : [];
    }

    return [];
}

/**
 * Fase 25: dado un nodeSchema ({ keys: [...] }) y el texto completo, busca
 * en qué array-de-objetos (kind: 'array_of_object') está posicionado el
 * cursor -- si está dentro de uno, devuelve su itemSchema.keys; si no,
 * devuelve las keys de nivel raíz del esquema. Detección por conteo de
 * profundidad de brackets hacia atrás desde el cursor (no un parser JSON
 * completo -- el usuario puede estar escribiendo JSON todavía inválido, así
 * que un parser estricto rompería a cada tecleo).
 */
function activeKeysForCursor(schema, text, cursor) {
    if (!schema || !Array.isArray(schema.keys)) return [];

    const arrayKeys = schema.keys.filter((k) => k.kind === 'array_of_object' && k.itemSchema);
    if (!arrayKeys.length) return schema.keys;

    const before = text.slice(0, cursor);
    let depth = 0;
    for (let i = before.length - 1; i >= 0; i--) {
        const ch = before[i];
        if (ch === '}' || ch === ']') {
            depth++;
        } else if (ch === '{') {
            if (depth === 0) {
                // Seguimos buscando hacia afuera el "[" que contiene este objeto.
                continue;
            }
            depth--;
        } else if (ch === '[') {
            if (depth === 0) {
                const prefix = before.slice(0, i);
                const keyMatch = prefix.match(/"([a-zA-Z_]+)"\s*:\s*$/);
                const arrayKeyName = keyMatch ? keyMatch[1] : null;
                const matched = arrayKeys.find((k) => k.key === arrayKeyName);
                return matched ? matched.itemSchema.keys : schema.keys;
            }
            depth--;
        }
    }
    return schema.keys;
}

function buildKeyValueRegex(keyName) {
    return new RegExp(`"${keyName}"\\s*:\\s*"([a-zA-Z0-9_.-]*)$`);
}

function buildKeyValueUnquotedRegex(keyName) {
    return new RegExp(`"${keyName}"\\s*:\\s*([0-9]*)$`);
}

/**
 * Fase 25: sugerencias de CLAVE nueva del JSON, genérico sobre cualquier
 * lista de keyDefs (raíz de un esquema, o itemSchema.keys de un array-de-
 * objetos activo) -- las que ya aparecen en el texto se excluyen (evita
 * duplicados), detectado con una regex simple por clave, no un parseo JSON
 * estricto (el usuario todavía puede estar escribiendo).
 */
function buildNewKeySuggestions(keyDefs, currentText) {
    const text = String(currentText || '');
    return keyDefs
        .filter((k) => !new RegExp(`"${k.key}"\\s*:`).test(text))
        .map((k) => ({ value: k.key, hint: k.hint, numeric: k.kind === 'number' }));
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
 * fieldValueSources (catalog.field_value_sources), actionValueSources
 * (catalog.action_value_sources, Fase 25), localValueSources (datos ya
 * cargados en memoria por NodeInspector, ej. credenciales de BD, Fase 25),
 * nodeSchema (Fase 25 -- reemplaza al booleano `schemaAware` de Fase 21:
 * pasar un objeto { keys: [...] } de nodeConfigSchemas.js activa, además
 * del `{{ }}` de siempre, sugerencias de clave/valor reales dirigidas por
 * ese esquema; no pasar nada dejamdo solo el modo `{{ }}`).
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
    actionValueSources,
    localValueSources,
    nodeSchema,
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

    const enumCtx = useMemo(
        () => ({ moduleType, modules, fieldValueSources, actionValueSources, localValueSources }),
        [moduleType, modules, fieldValueSources, actionValueSources, localValueSources]
    );

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

        if (nodeSchema) {
            const activeKeys = activeKeysForCursor(nodeSchema, text, cursor);

            // Sugerencias de VALOR: solo para claves kind === 'enum', probadas
            // en el orden en que aparecen en el esquema. Solo se reemplaza el
            // término parcial ya escrito (grupo capturado), NUNCA el match
            // completo -- este incluye la clave, los dos puntos y la comilla
            // de apertura, que el usuario ya escribió a mano y deben quedar
            // intactos (sustituirlos borraba el prefijo del JSON, bug real
            // detectado en QA manual de la Fase 21).
            for (const keyDef of activeKeys) {
                if (keyDef.kind !== 'enum') continue;
                const regex = keyDef.key === 'hours' || keyDef.numericValue
                    ? buildKeyValueUnquotedRegex(keyDef.key)
                    : buildKeyValueRegex(keyDef.key);
                const m = before.match(regex);
                if (m) {
                    const term = m[1] || '';
                    const suggestions = resolveEnumSource(keyDef.source, enumCtx, text);
                    const filtered = filterSuggestions(suggestions, term, 'value');
                    if (filtered.length) return openMenu(`enum:${keyDef.key}`, term.length, filtered, cursor);
                    return closeMenu();
                }
            }
            // Caso legado del Trigger: alias "to_stage_id" para la clave "value".
            if (nodeSchema === undefined) {
                // no-op, placeholder para mantener el bloque simétrico
            }
            const legacyValueMatch =
                before.match(/"to_stage_id"\s*:\s*"([a-zA-Z0-9_]*)$/) ||
                before.match(/"to_stage_id"\s*:\s*([0-9]*)$/);
            if (legacyValueMatch && activeKeys.some((k) => k.key === 'value')) {
                const term = legacyValueMatch[1] || '';
                const suggestions = resolveEnumSource('field_value:dynamic', enumCtx, text);
                const filtered = filterSuggestions(suggestions, term, 'value');
                if (filtered.length) return openMenu('enum:value', term.length, filtered, cursor);
                return closeMenu();
            }

            // Modo new-key: se está escribiendo una clave nueva justo después
            // de "{" o ",". Solo se reemplaza el término parcial ya escrito.
            const keyMatch = before.match(NEW_KEY_REGEX);
            if (keyMatch) {
                const term = keyMatch[1] || '';
                const filtered = filterSuggestions(buildNewKeySuggestions(activeKeys, text), term, 'value');
                if (filtered.length) return openMenu('new-key', term.length, filtered, cursor);
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
        // Modo enum:<key>: inserta el valor plano -- ya está dentro de las
        // comillas que el usuario escribió a mano en el JSON.
        // Modo new-key: inserta la clave + "": " " (cursor listo para que el
        // modo enum:<key> tome el relevo) -- salvo las claves numéricas
        // (kind: 'number'), que se insertan sin comillas.
        let insertion;
        if (mode === 'token') {
            insertion = `{{ ${item.path} }}`;
        } else if (mode === 'new-key') {
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
