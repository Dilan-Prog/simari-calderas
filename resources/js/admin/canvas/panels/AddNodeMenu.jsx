import { useState } from 'react';

/**
 * AddNodeMenu
 *
 * Catálogo de nodos que se pueden agregar al canvas, organizado en
 * categorías colapsables (<details>/<summary> nativos). "Acciones CRM" y
 * "Lógica y flujo" son funcionales hoy; "Datos y HTTP" tiene dos ítems
 * funcionales ("Base de datos (MySQL)", Fase 8, y "HTTP Request") e
 * "Integraciones" tiene un único ítem funcional ("WhatsApp", Fase 15)
 * mientras el resto de esas
 * categorías y las demás categorías se muestran deshabilitadas
 * ("Próximamente") para comunicar el roadmap sin prometer funcionalidad que
 * todavía no existe.
 *
 * Props:
 *   onAddNode: (stepType, actionType) => void
 */

const CRM_ACTIONS = [
    { actionType: 'create_task', label: 'Crear tarea', subtitle: 'Crea una tarea pendiente para el equipo', icon: 'task' },
    // Opción A (QA Fase 10, punto 4): notify_rep se muestra deshabilitado con
    // badge "Próximamente" -- igual que Integraciones/IA/Datos y HTTP -- porque
    // WorkflowActionExecutor::notifyRep() es un no-op hoy (solo registra
    // 'skipped', no envía notificación real). Ver comentario espejo en
    // WorkflowActionExecutor::supportedActions() ('coming_soon' => true).
    { actionType: 'notify_rep', label: 'Notificar al vendedor', subtitle: 'Envía una notificación interna al vendedor asignado', disabled: true, icon: 'bell' },
    { actionType: 'update_property', label: 'Actualizar propiedad', subtitle: 'Actualiza un campo del contacto, empresa o negocio', icon: 'edit' },
    { actionType: 'move_deal_stage', label: 'Mover etapa del negocio', subtitle: 'Cambia la etapa del negocio en el pipeline', icon: 'kanban' },
    { actionType: 'enroll_in_workflow', label: 'Inscribir en otro workflow', subtitle: 'Inscribe el registro en otro workflow activo', icon: 'enroll' },
    { actionType: 'send_email', label: 'Enviar correo', subtitle: 'Envía un email de la plantilla configurada', icon: 'mail' },
];

const LOGIC_ITEMS = [
    { stepType: 'condition', label: 'Condición Sí/No', subtitle: 'Ramifica el flujo según una condición', icon: 'branch' },
    { stepType: 'wait', label: 'Esperar', subtitle: 'Pausa el flujo un tiempo antes de continuar', icon: 'clock' },
    { stepType: 'switch', label: 'Switch', subtitle: 'Ramifica el flujo en varias rutas según reglas', icon: 'switch' },
    { stepType: 'parallel', label: 'Paralelo', subtitle: 'Dispara varias ramas al mismo tiempo', icon: 'parallel' },
    { stepType: 'join', label: 'Unir ramas', subtitle: 'Reúne las ramas de un Paralelo anterior', icon: 'join' },
    { stepType: 'loop', label: 'Bucle', subtitle: 'Repite un tramo del flujo N veces', icon: 'loop' },
    { stepType: 'end', label: 'Fin', subtitle: 'Termina el flujo en este punto', icon: 'end' },
];

const INTEGRATIONS_ITEMS = [
    // Fase 15: primer ítem funcional de esta categoría -- mismo patrón
    // quirúrgico que "Base de datos (MySQL)" en Datos y HTTP (Fase 8): un
    // actionType real habilita el botón, el resto de la categoría sigue
    // "Próximamente" hasta tener su propio backend.
    {
        actionType: 'send_whatsapp_message',
        label: 'WhatsApp',
        subtitle: 'Envía un mensaje de WhatsApp Business',
        icon: 'bubble',
    },
    { label: 'Slack', subtitle: 'Envía un mensaje a un canal de Slack', disabled: true, icon: 'chat' },
    { label: 'Google Sheets', subtitle: 'Agrega o actualiza una fila en una hoja de cálculo', disabled: true, icon: 'sheet' },
    { label: 'Notion', subtitle: 'Crea o actualiza una página en Notion', disabled: true, icon: 'page' },
    { label: 'Stripe', subtitle: 'Crea un cargo o consulta un cliente en Stripe', disabled: true, icon: 'card' },
    { label: 'HubSpot', subtitle: 'Sincroniza el registro con HubSpot', disabled: true, icon: 'sync' },
];

const AI_ITEMS = [
    { label: 'Claude', subtitle: 'Genera texto o toma decisiones con un modelo de Anthropic', icon: 'sparkle' },
    { label: 'OpenAI', subtitle: 'Genera texto o toma decisiones con un modelo de OpenAI', icon: 'cpu' },
];

const DATA_HTTP_ITEMS = [
    // Fase 8: primer ítem funcional de esta categoría -- el resto sigue
    // "Próximamente" hasta que tengan su propio backend real.
    {
        actionType: 'external_db_query',
        label: 'Base de datos (MySQL)',
        subtitle: 'Lee o escribe filas en una base de datos MySQL/MariaDB externa',
        icon: 'database',
    },
    {
        actionType: 'call_webhook',
        label: 'HTTP Request',
        subtitle: 'Llama a una URL externa y usa la respuesta',
        icon: 'globe',
    },
    { label: 'Webhook', subtitle: 'Recibe datos de un sistema externo', disabled: true, icon: 'webhook' },
    { label: 'Código (JS/Python)', subtitle: 'Ejecuta un fragmento de código personalizado', disabled: true, icon: 'code' },
];

function initialsFor(label) {
    const words = label.trim().split(/\s+/).filter(Boolean);
    if (words.length === 0) return '--';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return (words[0][0] + words[1][0]).toUpperCase();
}

function matches(label, query) {
    if (!query) return true;
    return label.toLowerCase().includes(query.toLowerCase());
}

function Chevron() {
    return (
        <svg className="wf-add-node-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

// Un ícono por categoría, coherente con lo que agrupa: CRM = contacto/negocio,
// Lógica y flujo = bifurcación, Integraciones = enchufe/conexión externa,
// IA = destello, Datos y HTTP = base de datos.
function CrmCategoryIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="8" r="3.5" stroke="currentColor" strokeWidth="2" />
            <path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

function LogicCategoryIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="6" cy="6" r="2.3" stroke="currentColor" strokeWidth="2" />
            <circle cx="6" cy="18" r="2.3" stroke="currentColor" strokeWidth="2" />
            <circle cx="18" cy="12" r="2.3" stroke="currentColor" strokeWidth="2" />
            <path d="M8 7l8 4M8 17l8-4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

function IntegrationsCategoryIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 3v4M15 3v4M6 7h12v3a6 6 0 01-12 0V7z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
            <path d="M12 16v5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

function AiCategoryIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 3l1.8 4.9L19 9.5l-5.2 1.6L12 16l-1.8-4.9L5 9.5l5.2-1.6L12 3z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
            <path d="M19 16l.9 2.4L22 19l-2.1.6L19 22l-.9-2.4L16 19l2.1-.6L19 16z" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round" />
        </svg>
    );
}

function DataHttpCategoryIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="12" cy="5.5" rx="7" ry="2.5" stroke="currentColor" strokeWidth="2" />
            <path d="M5 5.5V12c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V5.5" stroke="currentColor" strokeWidth="2" />
            <path d="M5 12v6.5c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V12" stroke="currentColor" strokeWidth="2" />
        </svg>
    );
}

// Ícono SVG por ítem individual del catálogo (14px, mismo trazo que los
// íconos de categoría). ITEM_ICON_PATHS es un mapa clave->contenido interno
// del <svg> para no repetir el wrapper en cada uno.
const ITEM_ICON_PATHS = {
    task: (
        <>
            <rect x="9" y="2.5" width="6" height="3.5" rx="1" stroke="currentColor" strokeWidth="1.7" />
            <path d="M9 4H7a2 2 0 00-2 2v13a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2h-2" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M8.5 13l2 2 4.5-4.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    bell: (
        <>
            <path d="M18 8a6 6 0 10-12 0c0 6.5-2.5 8.5-2.5 8.5h17S18 14.5 18 8z" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M13.7 20a2 2 0 01-3.4 0" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </>
    ),
    edit: (
        <>
            <path d="M4 20h4l10.5-10.5a2.1 2.1 0 00-3-3L5 17v3z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <path d="M13.5 8l2.5 2.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </>
    ),
    kanban: (
        <>
            <rect x="3" y="4" width="5.5" height="16" rx="1.2" stroke="currentColor" strokeWidth="1.7" />
            <rect x="9.75" y="4" width="5.5" height="10" rx="1.2" stroke="currentColor" strokeWidth="1.7" />
            <rect x="16.5" y="4" width="4.5" height="7" rx="1.2" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    enroll: (
        <>
            <path d="M13.5 3.5h4a2 2 0 012 2v13a2 2 0 01-2 2h-4" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M3 12h12" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M11 7.5l4.5 4.5-4.5 4.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    mail: (
        <>
            <rect x="2.5" y="5" width="19" height="14" rx="2" stroke="currentColor" strokeWidth="1.7" />
            <path d="M3 6.5l9 6.5 9-6.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    branch: (
        <>
            <circle cx="6" cy="5" r="2.2" stroke="currentColor" strokeWidth="1.7" />
            <circle cx="6" cy="19" r="2.2" stroke="currentColor" strokeWidth="1.7" />
            <circle cx="18" cy="12" r="2.2" stroke="currentColor" strokeWidth="1.7" />
            <path d="M6 7.2V19M6 7.2c0 6 4 4.8 10 4.8" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </>
    ),
    clock: (
        <>
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
            <path d="M12 7v5.5l3.5 2" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    database: (
        <>
            <ellipse cx="12" cy="5.5" rx="7.5" ry="2.7" stroke="currentColor" strokeWidth="1.7" />
            <path d="M4.5 5.5V12c0 1.5 3.4 2.7 7.5 2.7s7.5-1.2 7.5-2.7V5.5" stroke="currentColor" strokeWidth="1.7" />
            <path d="M4.5 12v6.5c0 1.5 3.4 2.7 7.5 2.7s7.5-1.2 7.5-2.7V12" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    globe: (
        <>
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
            <path d="M3 12h18" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M12 3c2.4 2.4 3.8 5.7 3.8 9s-1.4 6.6-3.8 9c-2.4-2.4-3.8-5.7-3.8-9s1.4-6.6 3.8-9z" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    webhook: (
        <>
            <path d="M12 3v11" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M7.5 10l4.5 4.5 4.5-4.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <rect x="4" y="17.5" width="16" height="3.2" rx="1.2" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    code: (
        <>
            <path d="M8.5 6L2.5 12l6 6" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M15.5 6l6 6-6 6" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    chat: (
        <path d="M21 15a2 2 0 01-2 2H8.5L4 21V5a2 2 0 012-2h13a2 2 0 012 2z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
    ),
    bubble: (
        <path d="M21 11.5c0 4.7-4 8.5-9 8.5a9.6 9.6 0 01-4.2-.95L3 21l1.3-3.9A8.3 8.3 0 013 11.5C3 6.8 7 3 12 3s9 3.8 9 8.5z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
    ),
    sheet: (
        <>
            <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" strokeWidth="1.7" />
            <path d="M3 10h18M9 4v16M15 4v16" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    page: (
        <>
            <path d="M14 2.5H7.5a2 2 0 00-2 2v15a2 2 0 002 2h9a2 2 0 002-2V8.5z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <path d="M14 2.5V8.5h4.5" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <path d="M9 13h6M9 16.5h6" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </>
    ),
    card: (
        <>
            <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" strokeWidth="1.7" />
            <path d="M2 9.5h20" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
    sync: (
        <>
            <path d="M20 12a8 8 0 01-13.7 5.7M4 12a8 8 0 0113.7-5.7" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M4 6.3v4.5h4.5M20 17.7v-4.5h-4.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    sparkle: (
        <path d="M12 2.5l2.1 5.8L20 10.5l-5.9 1.8L12 18l-2.1-5.7L4 10.5l5.9-2.2L12 2.5z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
    ),
    cpu: (
        <>
            <rect x="7" y="7" width="10" height="10" rx="1.5" stroke="currentColor" strokeWidth="1.7" />
            <path d="M12 2v3.5M12 18.5V22M2 12h3.5M18.5 12H22M4.6 4.6l2.5 2.5M16.9 16.9l2.5 2.5M4.6 19.4l2.5-2.5M16.9 7.1l2.5-2.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </>
    ),
    // Fase de nodos de flujo avanzado: switch (multi-rama), parallel
    // (bifurcación simultánea), join (reunión de ramas), loop (repetición),
    // end (fin del flujo). Mismo trazo/estilo que los íconos anteriores.
    switch: (
        <>
            <circle cx="5" cy="5" r="2" stroke="currentColor" strokeWidth="1.7" />
            <circle cx="5" cy="12" r="2" stroke="currentColor" strokeWidth="1.7" />
            <circle cx="5" cy="19" r="2" stroke="currentColor" strokeWidth="1.7" />
            <path d="M7 5h4a3 3 0 013 3M7 12h10M7 19h4a3 3 0 003-3" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <circle cx="20" cy="8" r="1.6" stroke="currentColor" strokeWidth="1.5" />
            <circle cx="20" cy="16" r="1.6" stroke="currentColor" strokeWidth="1.5" />
        </>
    ),
    parallel: (
        <>
            <path d="M4 5v5c0 3.5 8 3.5 8 0V5M12 10v5c0 3.5 8 3.5 8 0v-5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="4" cy="4" r="1.6" stroke="currentColor" strokeWidth="1.5" />
            <circle cx="12" cy="4" r="1.6" stroke="currentColor" strokeWidth="1.5" />
        </>
    ),
    join: (
        <>
            <path d="M5 4v6c0 3.5 5 5 7 5s7-1.5 7-5V4" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M19 15l0 5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M16.5 17.5L19 20l2.5-2.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    loop: (
        <>
            <path d="M4 12a8 8 0 0114-5.3" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M20 12a8 8 0 01-14 5.3" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M14.5 3.5L18 6.7l-4 1" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M9.5 20.5L6 17.3l4-1" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
        </>
    ),
    end: (
        <>
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
            <rect x="8.5" y="8.5" width="7" height="7" rx="1" stroke="currentColor" strokeWidth="1.7" />
        </>
    ),
};

function ItemIcon({ name, fallbackLabel }) {
    const inner = ITEM_ICON_PATHS[name];
    if (!inner) return initialsFor(fallbackLabel);
    return (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            {inner}
        </svg>
    );
}

function CategoryHeader({ label, count, icon, variant }) {
    return (
        <summary className="wf-add-node-category-summary">
            <span className="wf-add-node-category-left">
                <span className={`wf-add-node-category-icon wf-add-node-category-icon-${variant}`}>{icon}</span>
                <span className="wf-add-node-category-label">{label}</span>
            </span>
            <span className="wf-add-node-category-right">
                <span className="wf-add-node-count-badge">{count}</span>
                <Chevron />
            </span>
        </summary>
    );
}

export default function AddNodeMenu({ onAddNode }) {
    const [query, setQuery] = useState('');

    const crmMatches = CRM_ACTIONS.filter((item) => matches(item.label, query));
    const logicMatches = LOGIC_ITEMS.filter((item) => matches(item.label, query));
    const integrationsMatches = INTEGRATIONS_ITEMS.filter((item) => matches(item.label, query));
    const aiMatches = AI_ITEMS.filter((item) => matches(item.label, query));
    const dataHttpMatches = DATA_HTTP_ITEMS.filter((item) => matches(item.label, query));

    return (
        <div className="wf-canvas-sidebar">
            <div className="wf-add-node-search-wrap">
                <svg className="wf-add-node-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" strokeWidth="2" />
                    <path d="M21 21l-4.3-4.3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
                <input
                    type="text"
                    className="wf-add-node-search"
                    placeholder="Buscar nodos e integraciones"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                />
            </div>

            <div className="wf-add-node-menu">
                {crmMatches.length > 0 && (
                    <details className="wf-add-node-category" open>
                        <CategoryHeader label="Acciones CRM" count={crmMatches.length} icon={<CrmCategoryIcon />} variant="crm" />
                        <ul className="wf-add-node-list">
                            {crmMatches.map((item) => (
                                <li key={item.actionType}>
                                    {item.disabled ? (
                                        <span
                                            className="wf-add-node-item wf-add-node-item-disabled"
                                            title="Próximamente"
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-disabled"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                            <span className="wf-add-node-badge">Próximamente</span>
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            className="wf-add-node-item"
                                            onClick={() => onAddNode('action', item.actionType)}
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-crm"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                {logicMatches.length > 0 && (
                    <details className="wf-add-node-category" open>
                        <CategoryHeader label="Lógica y flujo" count={logicMatches.length} icon={<LogicCategoryIcon />} variant="logic" />
                        <ul className="wf-add-node-list">
                            {logicMatches.map((item) => (
                                <li key={item.stepType}>
                                    <button
                                        type="button"
                                        className="wf-add-node-item"
                                        onClick={() => onAddNode(item.stepType, null)}
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-logic"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                        <span className="wf-add-node-texts">
                                            <span className="wf-add-node-label">{item.label}</span>
                                            <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                {integrationsMatches.length > 0 && (
                    <details className="wf-add-node-category">
                        <CategoryHeader label="Integraciones" count={integrationsMatches.length} icon={<IntegrationsCategoryIcon />} variant="integrations" />
                        <ul className="wf-add-node-list">
                            {integrationsMatches.map((item) => (
                                <li key={item.label}>
                                    {item.disabled ? (
                                        <span
                                            className="wf-add-node-item wf-add-node-item-disabled"
                                            title="Próximamente"
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-disabled"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                            <span className="wf-add-node-badge">Próximamente</span>
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            className="wf-add-node-item"
                                            onClick={() => onAddNode('action', item.actionType)}
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-crm"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                {aiMatches.length > 0 && (
                    <details className="wf-add-node-category">
                        <CategoryHeader label="Inteligencia artificial" count={aiMatches.length} icon={<AiCategoryIcon />} variant="ai" />
                        <ul className="wf-add-node-list">
                            {aiMatches.map((item) => (
                                <li key={item.label}>
                                    <span
                                        className="wf-add-node-item wf-add-node-item-disabled"
                                        title="Próximamente"
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-disabled"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                        <span className="wf-add-node-texts">
                                            <span className="wf-add-node-label">{item.label}</span>
                                            <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                        </span>
                                        <span className="wf-add-node-badge">Próximamente</span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                {dataHttpMatches.length > 0 && (
                    <details className="wf-add-node-category">
                        <CategoryHeader label="Datos y HTTP" count={dataHttpMatches.length} icon={<DataHttpCategoryIcon />} variant="data" />
                        <ul className="wf-add-node-list">
                            {dataHttpMatches.map((item) => (
                                <li key={item.label}>
                                    {item.disabled ? (
                                        <span
                                            className="wf-add-node-item wf-add-node-item-disabled"
                                            title="Próximamente"
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-disabled"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                            <span className="wf-add-node-badge">Próximamente</span>
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            className="wf-add-node-item"
                                            onClick={() => onAddNode('action', item.actionType)}
                                        >
                                            <span className="wf-add-node-icon wf-add-node-icon-crm"><ItemIcon name={item.icon} fallbackLabel={item.label} /></span>
                                            <span className="wf-add-node-texts">
                                                <span className="wf-add-node-label">{item.label}</span>
                                                <span className="wf-add-node-subtitle">{item.subtitle}</span>
                                            </span>
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </details>
                )}
            </div>
        </div>
    );
}
