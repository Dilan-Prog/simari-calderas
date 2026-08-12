import { useState } from 'react';

/**
 * AddNodeMenu
 *
 * Catálogo de nodos que se pueden agregar al canvas, organizado en
 * categorías colapsables (<details>/<summary> nativos). Sólo "Acciones CRM"
 * y "Lógica y flujo" son funcionales hoy; el resto de categorías se muestran
 * deshabilitadas ("Próximamente") para comunicar el roadmap sin prometer
 * funcionalidad que todavía no existe.
 *
 * Props:
 *   onAddNode: (stepType, actionType) => void
 */

const CRM_ACTIONS = [
    { actionType: 'create_task', label: 'Crear tarea', subtitle: 'Crea una tarea pendiente para el equipo' },
    { actionType: 'notify_rep', label: 'Notificar al vendedor', subtitle: 'Envía una notificación interna al vendedor asignado' },
    { actionType: 'update_property', label: 'Actualizar propiedad', subtitle: 'Actualiza un campo del contacto, empresa o negocio' },
    { actionType: 'move_deal_stage', label: 'Mover etapa del negocio', subtitle: 'Cambia la etapa del negocio en el pipeline' },
    { actionType: 'enroll_in_workflow', label: 'Inscribir en otro workflow', subtitle: 'Inscribe el registro en otro workflow activo' },
    { actionType: 'send_email', label: 'Enviar correo', subtitle: 'Envía un email de la plantilla configurada' },
];

const LOGIC_ITEMS = [
    { stepType: 'condition', label: 'Condición Sí/No', subtitle: 'Ramifica el flujo según una condición' },
    { stepType: 'wait', label: 'Esperar', subtitle: 'Pausa el flujo un tiempo antes de continuar' },
];

const INTEGRATIONS_ITEMS = [
    { label: 'Slack', subtitle: 'Envía un mensaje a un canal de Slack' },
    { label: 'Google Sheets', subtitle: 'Agrega o actualiza una fila en una hoja de cálculo' },
    { label: 'Notion', subtitle: 'Crea o actualiza una página en Notion' },
    { label: 'Stripe', subtitle: 'Crea un cargo o consulta un cliente en Stripe' },
    { label: 'HubSpot', subtitle: 'Sincroniza el registro con HubSpot' },
    { label: 'WhatsApp', subtitle: 'Envía un mensaje de WhatsApp Business' },
];

const AI_ITEMS = [
    { label: 'Claude', subtitle: 'Genera texto o toma decisiones con un modelo de Anthropic' },
    { label: 'OpenAI', subtitle: 'Genera texto o toma decisiones con un modelo de OpenAI' },
];

const DATA_HTTP_ITEMS = [
    { label: 'HTTP Request', subtitle: 'Llama a una URL externa y usa la respuesta' },
    { label: 'Webhook', subtitle: 'Recibe datos de un sistema externo' },
    { label: 'Código (JS/Python)', subtitle: 'Ejecuta un fragmento de código personalizado' },
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

function CategoryHeader({ label, count }) {
    return (
        <summary className="wf-add-node-category-summary">
            <span className="wf-add-node-category-label">{label}</span>
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
                        <CategoryHeader label="Acciones CRM" count={crmMatches.length} />
                        <ul className="wf-add-node-list">
                            {crmMatches.map((item) => (
                                <li key={item.actionType}>
                                    <button
                                        type="button"
                                        className="wf-add-node-item"
                                        onClick={() => onAddNode('action', item.actionType)}
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-crm">{initialsFor(item.label)}</span>
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

                {logicMatches.length > 0 && (
                    <details className="wf-add-node-category" open>
                        <CategoryHeader label="Lógica y flujo" count={logicMatches.length} />
                        <ul className="wf-add-node-list">
                            {logicMatches.map((item) => (
                                <li key={item.stepType}>
                                    <button
                                        type="button"
                                        className="wf-add-node-item"
                                        onClick={() => onAddNode(item.stepType, null)}
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-logic">{initialsFor(item.label)}</span>
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
                        <CategoryHeader label="Integraciones" count={integrationsMatches.length} />
                        <ul className="wf-add-node-list">
                            {integrationsMatches.map((item) => (
                                <li key={item.label}>
                                    <span
                                        className="wf-add-node-item wf-add-node-item-disabled"
                                        title="Próximamente"
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-disabled">{initialsFor(item.label)}</span>
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

                {aiMatches.length > 0 && (
                    <details className="wf-add-node-category">
                        <CategoryHeader label="Inteligencia artificial" count={aiMatches.length} />
                        <ul className="wf-add-node-list">
                            {aiMatches.map((item) => (
                                <li key={item.label}>
                                    <span
                                        className="wf-add-node-item wf-add-node-item-disabled"
                                        title="Próximamente"
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-disabled">{initialsFor(item.label)}</span>
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
                        <CategoryHeader label="Datos y HTTP" count={dataHttpMatches.length} />
                        <ul className="wf-add-node-list">
                            {dataHttpMatches.map((item) => (
                                <li key={item.label}>
                                    <span
                                        className="wf-add-node-item wf-add-node-item-disabled"
                                        title="Próximamente"
                                    >
                                        <span className="wf-add-node-icon wf-add-node-icon-disabled">{initialsFor(item.label)}</span>
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
            </div>
        </div>
    );
}
