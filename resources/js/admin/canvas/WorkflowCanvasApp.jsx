import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ReactFlowProvider,
  ReactFlow,
  Background,
  Controls,
  MiniMap,
  useNodesState,
  useEdgesState,
  useReactFlow,
  addEdge,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

import { toFlow } from './graph/toFlow.js';
import { applyConnection, applyNodeDeletion } from './graph/fromFlow.js';
import TriggerNode from './nodes/TriggerNode.jsx';
import ActionNode from './nodes/ActionNode.jsx';
import ConditionNode from './nodes/ConditionNode.jsx';
import WaitNode from './nodes/WaitNode.jsx';
import StickyNote from './nodes/StickyNote.jsx';
import NodeInspector from './panels/NodeInspector.jsx';
import AddNodeMenu from './panels/AddNodeMenu.jsx';
import TopBar from './TopBar.jsx';
import BottomPanel from './BottomPanel.jsx';
import { useDebouncedLayoutSave } from './hooks/useDebouncedLayoutSave.js';
import * as api from './api/stepsApi.js';

const nodeTypes = {
  trigger: TriggerNode,
  action: ActionNode,
  condition: ConditionNode,
  wait: WaitNode,
  sticky: StickyNote,
};

// Clave propia de esta página para persistir el tema claro/oscuro elegido.
const THEME_STORAGE_KEY = 'wf-canvas-theme';

function noteNodeId(noteId) {
  return `note-${noteId}`;
}

function isNoteNodeId(nodeId) {
  return typeof nodeId === 'string' && nodeId.startsWith('note-');
}

function noteIdFromNodeId(nodeId) {
  return Number(nodeId.replace('note-', ''));
}

function CanvasSurface({
  nodes,
  edges,
  nodeTypes,
  onNodesChange,
  onEdgesChange,
  onConnect,
  onNodeDragStop,
  onNodeClick,
  onPaneClick,
  onNodesDelete,
  onAddNote,
  error,
}) {
  const { fitView } = useReactFlow();
  const [locked, setLocked] = useState(false);

  return (
    <div className="wf-canvas-main" style={{ flex: 1, position: 'relative' }}>
      {error && (
        <div style={{ position: 'absolute', top: 8, left: 8, right: 8, zIndex: 10, background: '#fee', color: '#900', padding: 8, borderRadius: 4 }}>
          {error}
        </div>
      )}

      <div className="wf-canvas-toolbar">
        <button type="button" className="wf-canvas-toolbar-btn" onClick={onAddNote} title="Agregar nota">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
          </svg>
          Nota
        </button>

        <button
          type="button"
          className={`wf-canvas-toolbar-btn ${locked ? 'is-active' : ''}`}
          onClick={() => setLocked((prev) => !prev)}
          title={locked ? 'Desbloquear canvas' : 'Bloquear canvas'}
        >
          {locked ? (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" strokeWidth="2" />
              <path d="M8 11V7a4 4 0 018 0v4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          ) : (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" strokeWidth="2" />
              <path d="M8 11V7a4 4 0 017.75-1.4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          )}
          {locked ? 'Bloqueado' : 'Libre'}
        </button>

        <button type="button" className="wf-canvas-toolbar-btn" onClick={() => fitView()} title="Encuadrar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 3H5a2 2 0 00-2 2v4M15 3h4a2 2 0 012 2v4M9 21H5a2 2 0 01-2-2v-4M15 21h4a2 2 0 002-2v-4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
          Encuadrar
        </button>
      </div>

      <ReactFlow
        nodes={nodes}
        edges={edges}
        nodeTypes={nodeTypes}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeDragStop={onNodeDragStop}
        onNodeClick={onNodeClick}
        onPaneClick={onPaneClick}
        onNodesDelete={onNodesDelete}
        nodesDraggable={!locked}
        nodesConnectable={!locked}
        elementsSelectable={!locked}
        fitView
      >
        <Background />
        <Controls />
        <MiniMap />
      </ReactFlow>
    </div>
  );
}

export default function WorkflowCanvasApp({
  workflowId,
  userInitials,
  canvasUrl,
  stepsUrl,
  layoutUrl,
  testUrl,
  updateUrl,
  toggleUrl,
  undoUrl,
  redoUrl,
  variablesUrl,
  notesUrl,
  executionsUrl,
  backUrl,
  workflowName: initialWorkflowName,
  workflowActive: initialWorkflowActive,
}) {
  const [data, setData] = useState(null); // { workflow, steps, catalog, notes, variables, can_undo, can_redo }
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedStepId, setSelectedStepId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [testLog, setTestLog] = useState([]);

  const [theme, setTheme] = useState(() => {
    try {
      return window.localStorage.getItem(THEME_STORAGE_KEY) || 'light';
    } catch (e) {
      return 'light';
    }
  });

  // Aplica el tema como data-theme en el contenedor raíz real (#workflow-canvas-root,
  // el nodo montado por Blade) porque el CSS de tema de esta página apunta a ese id
  // específico, no a un wrapper interno de React.
  useEffect(() => {
    const root = document.getElementById('workflow-canvas-root');
    if (root) {
      root.setAttribute('data-theme', theme);
    }
    try {
      window.localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch (e) {
      // localStorage no disponible (modo privado, etc.), no-op.
    }
  }, [theme]);

  const handleToggleTheme = useCallback(() => {
    setTheme((prev) => (prev === 'dark' ? 'light' : 'dark'));
  }, []);

  const fetchCanvas = useCallback(() => {
    setLoading(true);
    return api
      .getCanvas(canvasUrl)
      .then((response) => {
        setData(response);
        setError(null);
      })
      .catch((err) => {
        setError(err.message || 'Error cargando el canvas');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [canvasUrl]);

  useEffect(() => {
    fetchCanvas();
  }, [fetchCanvas]);

  const handleNoteTextChange = useCallback(
    (noteId, text) => {
      api
        .updateNote(notesUrl, noteId, { text })
        .then(() => {
          setData((prev) =>
            prev
              ? {
                  ...prev,
                  notes: (prev.notes || []).map((n) => (n.id === noteId ? { ...n, text } : n)),
                }
              : prev
          );
        })
        .catch((err) => {
          setError(err.message || 'Error guardando la nota');
        });
    },
    [notesUrl]
  );

  const { nodes: initialNodes, edges: initialEdges } = useMemo(() => {
    if (!data) return { nodes: [], edges: [] };
    const { nodes: stepNodes, edges: stepEdges } = toFlow(data.steps, data.workflow, data.catalog);

    const stickyNodes = (data.notes || []).map((note) => ({
      id: noteNodeId(note.id),
      type: 'sticky',
      position: { x: note.position_x ?? 0, y: note.position_y ?? 0 },
      data: {
        text: note.text || '',
        onTextChange: (text) => handleNoteTextChange(note.id, text),
      },
    }));

    return { nodes: [...stepNodes, ...stickyNodes], edges: stepEdges };
  }, [data, handleNoteTextChange]);

  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);

  useEffect(() => {
    setNodes(initialNodes);
    setEdges(initialEdges);
  }, [initialNodes, initialEdges, setNodes, setEdges]);

  const saveLayoutBatch = useCallback(
    (positions) => {
      api.saveLayout(layoutUrl, positions).catch((err) => {
        setError(err.message || 'Error guardando la posición del nodo');
      });
    },
    [layoutUrl]
  );

  const queuePosition = useDebouncedLayoutSave(saveLayoutBatch);

  const selectedStep = useMemo(() => {
    if (!data || !selectedStepId) return null;

    if (selectedStepId === 'trigger') {
      return {
        id: 'trigger',
        step_type: 'trigger',
        type: data.workflow?.type,
        workflowType: data.workflow?.type,
        enrollment_trigger: data.workflow?.enrollment_trigger,
      };
    }

    return data.steps.find((step) => String(step.id) === String(selectedStepId)) || null;
  }, [data, selectedStepId]);

  const onConnect = useCallback(
    (connection) => {
      setEdges((eds) => addEdge(connection, eds));

      const payload = applyConnection(connection, data ? data.steps : []);
      if (!payload || !connection.target) return;

      api
        .updateStep(stepsUrl, connection.target, payload)
        .then(() => fetchCanvas())
        .catch((err) => {
          setError(err.message || 'Error guardando la conexión');
        });
    },
    [data, stepsUrl, fetchCanvas, setEdges]
  );

  const onNodeDragStop = useCallback(
    (evt, node) => {
      if (node.id === 'trigger') return;

      if (node.type === 'sticky' || isNoteNodeId(node.id)) {
        const noteId = noteIdFromNodeId(node.id);
        const position_x = Math.round(node.position.x);
        const position_y = Math.round(node.position.y);
        api
          .updateNote(notesUrl, noteId, { position_x, position_y })
          .then(() => {
            setData((prev) =>
              prev
                ? {
                    ...prev,
                    notes: (prev.notes || []).map((n) =>
                      n.id === noteId ? { ...n, position_x, position_y } : n
                    ),
                  }
                : prev
            );
          })
          .catch((err) => {
            setError(err.message || 'Error guardando la posición de la nota');
          });
        return;
      }

      queuePosition(node.id, node.position.x, node.position.y);
    },
    [queuePosition, notesUrl]
  );

  const onNodeClick = useCallback((evt, node) => {
    if (node.type === 'sticky') return;
    setSelectedStepId(node.id);
  }, []);

  const onPaneClick = useCallback(() => {
    setSelectedStepId(null);
  }, []);

  const handleSaveStep = useCallback(
    (payload) => {
      if (!selectedStepId) return Promise.resolve();
      return api
        .updateStep(stepsUrl, selectedStepId, payload)
        .then(() => fetchCanvas())
        .catch((err) => {
          setError(err.message || 'Error guardando el paso');
          throw err;
        });
    },
    [selectedStepId, stepsUrl, fetchCanvas]
  );

  const handleSaveTrigger = useCallback(
    ({ type, enrollment_trigger }) => {
      // El backend espera enrollment_trigger como string JSON (regla de
      // validación 'json'), igual que el formulario clásico de creación.
      return api
        .updateWorkflow(updateUrl, {
          type,
          enrollment_trigger: JSON.stringify(enrollment_trigger ?? {}),
        })
        .then(() => {
          setData((prev) =>
            prev
              ? {
                  ...prev,
                  workflow: { ...prev.workflow, type, enrollment_trigger },
                }
              : prev
          );
        })
        .catch((err) => {
          setError(err.message || 'Error guardando el trigger');
          throw err;
        });
    },
    [updateUrl]
  );

  const handleDeleteStep = useCallback(() => {
    if (!selectedStepId) return;
    if (!window.confirm('¿Eliminar este paso del workflow?')) return;
    api
      .deleteStep(stepsUrl, selectedStepId)
      .then(() => {
        setSelectedStepId(null);
        return fetchCanvas();
      })
      .catch((err) => {
        setError(err.message || 'Error eliminando el paso');
      });
  }, [selectedStepId, stepsUrl, fetchCanvas]);

  const onNodesDelete = useCallback(
    (deletedNodes) => {
      deletedNodes.forEach((node) => {
        if (node.id === 'trigger') return;

        if (node.type === 'sticky' || isNoteNodeId(node.id)) {
          const noteId = noteIdFromNodeId(node.id);
          api.deleteNote(notesUrl, noteId).catch((err) => {
            setError(err.message || 'Error eliminando la nota');
          });
          return;
        }

        const id = applyNodeDeletion(node.id);
        api.deleteStep(stepsUrl, id).catch((err) => {
          setError(err.message || 'Error eliminando el paso');
        });
      });
      fetchCanvas();
    },
    [stepsUrl, notesUrl, fetchCanvas]
  );

  const handleAddNode = useCallback(
    (stepType, actionType) => {
      const parentStepId = selectedStepId && !isNoteNodeId(selectedStepId) && selectedStepId !== 'trigger'
        ? selectedStepId
        : null;
      const payload = {
        parent_step_id: parentStepId,
        step_type: stepType,
        action_type: actionType || null,
      };
      return api
        .createStep(stepsUrl, payload)
        .then(() => fetchCanvas())
        .catch((err) => {
          setError(err.message || 'Error creando el paso');
          throw err;
        });
    },
    [selectedStepId, stepsUrl, fetchCanvas]
  );

  const handleAddNote = useCallback(() => {
    const count = data?.notes?.length || 0;
    const position = { x: 40 + (count % 6) * 30, y: 40 + (count % 6) * 30 };

    return api
      .createNote(notesUrl, { text: '', position_x: position.x, position_y: position.y })
      .then((response) => {
        const note = response?.note || response;
        if (!note) return;
        setData((prev) => (prev ? { ...prev, notes: [...(prev.notes || []), note] } : prev));
      })
      .catch((err) => {
        setError(err.message || 'Error creando la nota');
      });
  }, [data, notesUrl]);

  // ── Callbacks de la TopBar ──────────────────────────────────────

  const handleRename = useCallback(
    (newName) => {
      setSaving(true);
      return api
        .updateWorkflow(updateUrl, { name: newName })
        .then(() => {
          setData((prev) => (prev ? { ...prev, workflow: { ...prev.workflow, name: newName } } : prev));
        })
        .catch((err) => {
          setError(err.message || 'Error renombrando el workflow');
        })
        .finally(() => setSaving(false));
    },
    [updateUrl]
  );

  const handleToggleActive = useCallback(() => {
    setSaving(true);
    return api
      .toggleWorkflow(toggleUrl)
      .then(() => fetchCanvas())
      .catch((err) => {
        setError(err.message || 'Error cambiando el estado del workflow');
      })
      .finally(() => setSaving(false));
  }, [toggleUrl, fetchCanvas]);

  const handleUndo = useCallback(() => {
    return api
      .undoWorkflow(undoUrl)
      .then((response) => {
        if (!response) return;
        // Reemplaza el estado completo (no sólo can_undo/can_redo): steps y
        // workflow vienen del snapshot restaurado, así que nodes/edges se
        // reconstruyen enteros a partir de ellos vía el memo de toFlow().
        setData((prev) =>
          prev
            ? {
                ...prev,
                workflow: response.workflow,
                steps: response.steps,
                can_undo: response.can_undo,
                can_redo: response.can_redo,
              }
            : prev
        );
        setSelectedStepId(null);
      })
      .catch((err) => {
        setError(err.message || 'Error al deshacer');
      });
  }, [undoUrl]);

  const handleRedo = useCallback(() => {
    return api
      .redoWorkflow(redoUrl)
      .then((response) => {
        if (!response) return;
        setData((prev) =>
          prev
            ? {
                ...prev,
                workflow: response.workflow,
                steps: response.steps,
                can_undo: response.can_undo,
                can_redo: response.can_redo,
              }
            : prev
        );
        setSelectedStepId(null);
      })
      .catch((err) => {
        setError(err.message || 'Error al rehacer');
      });
  }, [redoUrl]);

  const handleSave = useCallback(() => {
    if (!data) return Promise.resolve();
    setSaving(true);
    return api
      .updateWorkflow(updateUrl, { name: data.workflow.name })
      .then(() => fetchCanvas())
      .catch((err) => {
        setError(err.message || 'Error guardando el workflow');
      })
      .finally(() => setSaving(false));
  }, [data, updateUrl, fetchCanvas]);

  const handleRun = useCallback(() => {
    return api
      .runTest(testUrl)
      .then((response) => {
        setTestLog(Array.isArray(response?.log) ? response.log : []);
      })
      .catch((err) => {
        setError(err.message || 'Error ejecutando la prueba');
      });
  }, [testUrl]);

  if (loading && !data) {
    return <div style={{ padding: 20 }}>Cargando...</div>;
  }

  if (error && !data) {
    return <div style={{ padding: 20, color: 'red' }}>{error}</div>;
  }

  const workflowName = data?.workflow?.name ?? initialWorkflowName ?? '';
  const isActive = data ? !!data.workflow?.is_active : !!initialWorkflowActive;

  return (
    <ReactFlowProvider>
      <div style={{ display: 'flex', flexDirection: 'column', width: '100%', height: '100%', minHeight: 600 }}>
        <TopBar
          workflowName={workflowName}
          isActive={isActive}
          canUndo={!!data?.can_undo}
          canRedo={!!data?.can_redo}
          saving={saving}
          backUrl={backUrl}
          onRename={handleRename}
          onToggleActive={handleToggleActive}
          onUndo={handleUndo}
          onRedo={handleRedo}
          onSave={handleSave}
          onRun={handleRun}
          theme={theme}
          onToggleTheme={handleToggleTheme}
          userInitials={userInitials}
        />

        <div className="wf-canvas-shell" style={{ display: 'flex', flex: 1, minHeight: 0, height: 'auto' }}>
          <AddNodeMenu
            catalog={data ? data.catalog : []}
            selectedStepId={selectedStepId}
            onAddNode={handleAddNode}
          />

          <CanvasSurface
            nodes={nodes}
            edges={edges}
            nodeTypes={nodeTypes}
            onNodesChange={onNodesChange}
            onEdgesChange={onEdgesChange}
            onConnect={onConnect}
            onNodeDragStop={onNodeDragStop}
            onNodeClick={onNodeClick}
            onPaneClick={onPaneClick}
            onNodesDelete={onNodesDelete}
            onAddNote={handleAddNote}
            error={error}
          />

          {selectedStep && (
            <NodeInspector
              step={selectedStep}
              catalog={data ? data.catalog : []}
              workflowId={workflowId}
              testUrl={testUrl}
              onSave={handleSaveStep}
              onSaveTrigger={handleSaveTrigger}
              onDelete={handleDeleteStep}
              onClose={() => setSelectedStepId(null)}
            />
          )}
        </div>

        <BottomPanel
          variablesUrl={variablesUrl}
          executionsUrl={executionsUrl}
          initialVariables={data?.variables || []}
          log={testLog}
        />
      </div>
    </ReactFlowProvider>
  );
}
