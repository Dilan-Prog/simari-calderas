import React, { useState } from 'react';
import Palette from './Palette.jsx';
import Canvas from './Canvas.jsx';
import PropertiesPanel from './PropertiesPanel.jsx';
import { buildEmailHtml } from './buildEmailHtml.js';
import { TIPOS } from './blockTypes.js';

/**
 * BlockBuilderEditor.jsx
 *
 * Vista sencilla del editor de Plantillas: compone Palette + Canvas +
 * PropertiesPanel en la grilla de 3 columnas del mockup (246px / 1fr /
 * 274px), más la barra superior con Deshacer/Rehacer, indicador de
 * autoguardado, alternador Escritorio/Móvil y "Ver HTML generado".
 *
 * Nota de arquitectura: este componente es deliberadamente "tonto" en
 * cuanto al arreglo de bloques -- no guarda su propia copia ni su
 * propio historial. `TemplateEditorShell` (el dueño real de
 * `useBlockHistory`, según su propia especificación) le pasa `blocks`
 * ya resuelto y `canUndo`/`canRedo`/`onUndo`/`onRedo` listos para
 * pintar en la barra de herramientas. Cada mutación local (agregar,
 * mover, duplicar, eliminar, editar una propiedad) se calcula aquí de
 * forma pura y se reporta como un arreglo NUEVO vía `onBlocksChange`,
 * que el shell envuelve en su `mutate()` para registrar el paso en el
 * historial -- una sola fuente de verdad para los bloques, un solo
 * historial de deshacer/rehacer.
 *
 * Props:
 *  - blocks: arreglo de bloques actual (controlado por el padre).
 *  - onBlocksChange(newBlocks): reporta el arreglo resultante de una
 *    mutación local (agregar/mover/duplicar/eliminar/editar propiedad).
 *  - canUndo, canRedo, onUndo(), onRedo(): estado/acciones de deshacer-
 *    rehacer, delegadas al `useBlockHistory` que vive en el shell.
 *  - previewMode: 'desktop' | 'mobile'.
 *  - onPreviewModeChange(mode).
 *  - onRequestCodeView(generatedHtml): el botón "Ver HTML generado"
 *    corre buildEmailHtml(blocks) y delega el cambio a vista de código.
 *  - autosaveStatus (opcional): texto del indicador de autoguardado.
 */
export default function BlockBuilderEditor({
  blocks,
  onBlocksChange,
  canUndo,
  canRedo,
  onUndo,
  onRedo,
  previewMode,
  onPreviewModeChange,
  onRequestCodeView,
  autosaveStatus,
}) {
  const [selectedId, setSelectedId] = useState(null);
  const [hoveredId, setHoveredId] = useState(null);

  const selectedBlock = blocks.find((b) => b.id === selectedId) || null;

  const addBlock = (tipoKey, index) => {
    const def = TIPOS[tipoKey];
    if (!def) return;
    const newBlock = { id: 'b' + Date.now() + Math.random().toString(16).slice(2), tipo: tipoKey, props: JSON.parse(JSON.stringify(def.props)) };
    const next = blocks.slice();
    next.splice(index == null ? next.length : index, 0, newBlock);
    onBlocksChange(next);
    setSelectedId(newBlock.id);
  };

  const moveBlock = (id, delta) => {
    const i = blocks.findIndex((b) => b.id === id);
    const j = i + delta;
    if (i < 0 || j < 0 || j >= blocks.length) return;
    const next = blocks.slice();
    const [moved] = next.splice(i, 1);
    next.splice(j, 0, moved);
    onBlocksChange(next);
  };

  const reorderBlock = (id, toIndex) => {
    const i = blocks.findIndex((b) => b.id === id);
    if (i < 0) return;
    const next = blocks.slice();
    const [moved] = next.splice(i, 1);
    next.splice(toIndex > i ? toIndex - 1 : toIndex, 0, moved);
    onBlocksChange(next);
  };

  const duplicateBlock = (id) => {
    const i = blocks.findIndex((b) => b.id === id);
    if (i < 0) return;
    const clone = { id: 'b' + Date.now() + Math.random().toString(16).slice(2), tipo: blocks[i].tipo, props: { ...blocks[i].props } };
    const next = blocks.slice();
    next.splice(i + 1, 0, clone);
    onBlocksChange(next);
  };

  const deleteBlock = (id) => {
    onBlocksChange(blocks.filter((b) => b.id !== id));
    setSelectedId((prev) => (prev === id ? null : prev));
    setHoveredId((prev) => (prev === id ? null : prev));
  };

  const changeProp = (key, value) => {
    if (!selectedBlock) return;
    const id = selectedBlock.id;
    onBlocksChange(blocks.map((b) => (b.id === id ? { ...b, props: { ...b.props, [key]: value } } : b)));
  };

  const handleVerHtml = () => {
    onRequestCodeView(buildEmailHtml(blocks));
  };

  return (
    <div className="emb-builder">
      <div className="emb-toolbar">
        <div className="emb-toolbar-left">
          <button type="button" className="emb-toolbar-btn" title="Deshacer" disabled={!canUndo} onClick={onUndo}>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
              <path d="M9 10H5V6" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M5 10a8 8 0 1 1 3 6.2" strokeLinecap="round" />
            </svg>
          </button>
          <button type="button" className="emb-toolbar-btn" title="Rehacer" disabled={!canRedo} onClick={onRedo}>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
              <path d="M15 10h4V6" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M19 10a8 8 0 1 0-3 6.2" strokeLinecap="round" />
            </svg>
          </button>
          <div className="emb-autosave">
            <span className="emb-autosave-dot" />
            {autosaveStatus || 'Los cambios se guardan automáticamente'}
          </div>
        </div>
        <div className="emb-toolbar-right">
          <div className="emb-preview-toggle">
            <button
              type="button"
              className={'emb-preview-toggle-btn' + (previewMode !== 'mobile' ? ' emb-preview-toggle-btn--active' : '')}
              onClick={() => onPreviewModeChange('desktop')}
            >
              Escritorio
            </button>
            <button
              type="button"
              className={'emb-preview-toggle-btn' + (previewMode === 'mobile' ? ' emb-preview-toggle-btn--active' : '')}
              onClick={() => onPreviewModeChange('mobile')}
            >
              Móvil
            </button>
          </div>
          <button type="button" className="emb-btn-outline" onClick={handleVerHtml}>
            Ver HTML generado
          </button>
        </div>
      </div>

      <div className="emb-grid">
        <Palette onAddBlock={addBlock} />
        <Canvas
          blocks={blocks}
          selectedId={selectedId}
          hoveredId={hoveredId}
          onSelect={setSelectedId}
          onHover={setHoveredId}
          onAddBlock={addBlock}
          onMoveBlock={moveBlock}
          onReorder={reorderBlock}
          onDuplicate={duplicateBlock}
          onDelete={deleteBlock}
          previewMode={previewMode}
        />
        <PropertiesPanel
          selectedBlock={selectedBlock}
          onChangeProp={changeProp}
          onDuplicate={duplicateBlock}
          onDelete={deleteBlock}
          onDeselect={() => setSelectedId(null)}
        />
      </div>
    </div>
  );
}
