import { useState, useEffect, useRef, useCallback } from 'react';

const VARIABLE_TYPES = ['string', 'number', 'json'];
const DEFAULT_HEIGHT = 220;
const MIN_HEIGHT = 120;
const MAX_HEIGHT = 560;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function statusDotClass(status) {
    if (status === 'completed') return 'wf-dot-ok';
    if (status === 'failed') return 'wf-dot-err';
    return 'wf-dot-warn';
}

/**
 * BottomPanel
 *
 * Panel inferior del editor de canvas con 3 pestañas: variables, executions, log.
 * Mismo patrón fetch+CSRF que resources/js/admin/canvas/api/stepsApi.js.
 *
 * Props:
 *   variablesUrl:      string - base url para CRUD de variables (GET lista, POST crea,
 *                       DELETE en variablesUrl + '/' + id)
 *   executionsUrl:      string - url para GET (Accept: application/json) del listado
 *                       paginado de ejecuciones (respuesta { data: [...], ... })
 *   initialVariables:  array   - variables iniciales [{ id, name, type, value }, ...]
 *   log:               array   - log de la última corrida en modo prueba, cada entrada
 *                       { step_id, step_type, result, message, action_type?, config? }
 */
export default function BottomPanel({ variablesUrl, executionsUrl, initialVariables, log }) {
    const [activeTab, setActiveTab] = useState('variables');
    const [collapsed, setCollapsed] = useState(false);
    const [height, setHeight] = useState(DEFAULT_HEIGHT);
    const dragState = useRef(null);

    const [variables, setVariables] = useState(initialVariables || []);
    const [newName, setNewName] = useState('');
    const [newType, setNewType] = useState('string');
    const [newValue, setNewValue] = useState('');
    const [variablesError, setVariablesError] = useState('');
    const [savingVariable, setSavingVariable] = useState(false);

    const [executions, setExecutions] = useState([]);
    const [executionsLoading, setExecutionsLoading] = useState(false);
    const [executionsError, setExecutionsError] = useState('');
    const [executionsLoaded, setExecutionsLoaded] = useState(false);
    const [expandedLogIndex, setExpandedLogIndex] = useState(null);

    useEffect(() => {
        if (activeTab !== 'executions' || executionsLoaded) return;

        setExecutionsLoading(true);
        setExecutionsError('');

        fetch(executionsUrl, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then((body) => {
                setExecutions(Array.isArray(body?.data) ? body.data : []);
                setExecutionsLoaded(true);
            })
            .catch((err) => {
                setExecutionsError(err.message || 'No se pudieron cargar las ejecuciones');
            })
            .finally(() => {
                setExecutionsLoading(false);
            });
    }, [activeTab, executionsLoaded, executionsUrl]);

    async function handleAddVariable(e) {
        e.preventDefault();
        if (!newName.trim()) {
            setVariablesError('El nombre es requerido');
            return;
        }

        setSavingVariable(true);
        setVariablesError('');

        try {
            const response = await fetch(variablesUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: newName, type: newType, value: newValue }),
            });

            if (!response.ok) {
                let message = `Error ${response.status}: ${response.statusText}`;
                try {
                    const body = await response.json();
                    if (body?.message) message = body.message;
                    else if (body?.errors) message = Object.values(body.errors).flat().join(' ');
                } catch (parseErr) {
                    // no-op, se mantiene el mensaje por defecto
                }
                throw new Error(message);
            }

            const body = await response.json();
            const created = body?.variable || body;

            setVariables((prev) => [...prev, created]);
            setNewName('');
            setNewType('string');
            setNewValue('');
        } catch (err) {
            setVariablesError(err.message || 'No se pudo agregar la variable');
        } finally {
            setSavingVariable(false);
        }
    }

    async function handleDeleteVariable(id) {
        setVariablesError('');

        try {
            const url = `${variablesUrl.replace(/\/$/, '')}/${id}`;
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            setVariables((prev) => prev.filter((v) => v.id !== id));
        } catch (err) {
            setVariablesError(err.message || 'No se pudo borrar la variable');
        }
    }

    const handleResizeStart = useCallback((event) => {
        event.preventDefault();
        dragState.current = { startY: event.clientY, startHeight: height };

        const onMouseMove = (moveEvent) => {
            if (!dragState.current) return;
            const delta = dragState.current.startY - moveEvent.clientY;
            const next = Math.min(MAX_HEIGHT, Math.max(MIN_HEIGHT, dragState.current.startHeight + delta));
            setHeight(next);
        };

        const onMouseUp = () => {
            dragState.current = null;
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        };

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    }, [height]);

    return (
        <div className={`wf-bottompanel ${collapsed ? 'is-collapsed' : ''}`} style={collapsed ? undefined : { height: `${height}px`, flexBasis: `${height}px` }}>
            {!collapsed && (
                <div className="wf-bottompanel-resize" onMouseDown={handleResizeStart}>
                    <span className="wf-bottompanel-resize-grip" />
                </div>
            )}

            <div className="wf-bottompanel-tabs">
                <button
                    type="button"
                    className={`wf-bottompanel-tab ${activeTab === 'variables' ? 'is-active' : ''}`}
                    onClick={() => setActiveTab('variables')}
                >
                    Variables ({variables.length})
                </button>
                <button
                    type="button"
                    className={`wf-bottompanel-tab ${activeTab === 'executions' ? 'is-active' : ''}`}
                    onClick={() => setActiveTab('executions')}
                >
                    Ejecuciones {executionsLoaded ? `(${executions.length})` : ''}
                </button>
                <button
                    type="button"
                    className={`wf-bottompanel-tab ${activeTab === 'log' ? 'is-active' : ''}`}
                    onClick={() => setActiveTab('log')}
                >
                    Log de ejecución{log && log.length > 0 ? ` (${log.length})` : ''}
                </button>

                <div className="wf-bottompanel-tabs-spacer" />

                <button
                    type="button"
                    className="wf-bottompanel-collapse-btn"
                    onClick={() => setCollapsed((c) => !c)}
                    title={collapsed ? 'Expandir panel' : 'Colapsar panel'}
                >
                    <svg
                        width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                        style={{ transform: collapsed ? 'rotate(180deg)' : 'none', transition: 'transform .15s ease' }}
                    >
                        <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </button>
            </div>

            {!collapsed && (
                <div className="wf-bottompanel-content">
                    {activeTab === 'variables' && (
                        <div className="wf-bottompanel-variables">
                            {variablesError && (
                                <div className="wf-bottompanel-error">{variablesError}</div>
                            )}

                            <div className="wf-var-grid">
                                <div className="wf-var-grid-header">
                                    <div>Nombre</div>
                                    <div>Tipo</div>
                                    <div>Valor</div>
                                    <div>Scope</div>
                                    <div />
                                </div>

                                {variables.length === 0 && (
                                    <div className="wf-bottompanel-empty">No hay variables definidas.</div>
                                )}

                                {variables.map((variable) => (
                                    <div className="wf-var-grid-row" key={variable.id}>
                                        <div className="wf-var-name">{variable.name}</div>
                                        <div className="wf-var-type">{variable.type}</div>
                                        <div className="wf-var-value">{variable.value}</div>
                                        <div className="wf-var-scope">{variable.scope || 'Global'}</div>
                                        <div className="wf-var-actions">
                                            <button
                                                type="button"
                                                className="wf-var-delete-btn"
                                                onClick={() => handleDeleteVariable(variable.id)}
                                                title="Borrar variable"
                                            >
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                ))}

                                <form className="wf-var-grid-row wf-var-new-row" onSubmit={handleAddVariable}>
                                    <input
                                        type="text"
                                        className="wf-bottompanel-input"
                                        placeholder="+ Nueva variable"
                                        value={newName}
                                        onChange={(e) => setNewName(e.target.value)}
                                    />
                                    <select
                                        className="wf-bottompanel-select"
                                        value={newType}
                                        onChange={(e) => setNewType(e.target.value)}
                                    >
                                        {VARIABLE_TYPES.map((type) => (
                                            <option key={type} value={type}>
                                                {type}
                                            </option>
                                        ))}
                                    </select>
                                    <input
                                        type="text"
                                        className="wf-bottompanel-input"
                                        placeholder="Valor"
                                        value={newValue}
                                        onChange={(e) => setNewValue(e.target.value)}
                                    />
                                    <div className="wf-var-scope">Global</div>
                                    <button
                                        type="submit"
                                        className="wf-var-add-btn"
                                        disabled={savingVariable}
                                    >
                                        {savingVariable ? '…' : 'Agregar'}
                                    </button>
                                </form>
                            </div>
                        </div>
                    )}

                    {activeTab === 'executions' && (
                        <div className="wf-bottompanel-executions">
                            {executionsLoading && (
                                <div className="wf-bottompanel-loading">Cargando ejecuciones...</div>
                            )}
                            {executionsError && (
                                <div className="wf-bottompanel-error">{executionsError}</div>
                            )}
                            {!executionsLoading && !executionsError && (
                                <table className="wf-bottompanel-table">
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th>Inscrito</th>
                                            <th>Completado</th>
                                            <th>Falló</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {executions.length === 0 && (
                                            <tr>
                                                <td colSpan={4} className="wf-bottompanel-empty">
                                                    No hay ejecuciones registradas.
                                                </td>
                                            </tr>
                                        )}
                                        {executions.map((execution) => (
                                            <tr key={execution.id}>
                                                <td>{execution.status}</td>
                                                <td>{execution.enrolled_at}</td>
                                                <td>{execution.completed_at}</td>
                                                <td>{execution.failed ? 'Sí' : 'No'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    )}

                    {activeTab === 'log' && (
                        <div className="wf-bottompanel-log">
                            {(!log || log.length === 0) && (
                                <div className="wf-bottompanel-empty">
                                    Pulsa "Ejecutar" para ver el resultado de la prueba aquí.
                                </div>
                            )}
                            {log && log.length > 0 && (
                                <ul className="wf-log-list">
                                    {log.map((entry, index) => (
                                        <li className="wf-log-entry" key={`${entry.step_id}-${index}`}>
                                            <span className="wf-log-time">{new Date().toLocaleTimeString('es-MX', { hour12: false })}</span>
                                            <span className={`wf-log-dot ${statusDotClass(entry.result)}`} />
                                            <span className="wf-log-node">{entry.step_type}{entry.action_type ? ` · ${entry.action_type}` : ''}</span>
                                            <span className="wf-log-message">{entry.message}</span>
                                            <button
                                                type="button"
                                                className="wf-log-json-toggle"
                                                onClick={() => setExpandedLogIndex((prev) => (prev === index ? null : index))}
                                            >
                                                {expandedLogIndex === index ? 'ocultar JSON' : 'ver JSON'}
                                            </button>
                                            {expandedLogIndex === index && (
                                                <pre className="wf-log-json">{JSON.stringify(entry, null, 2)}</pre>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
