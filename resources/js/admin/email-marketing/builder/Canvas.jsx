import React, { useState } from 'react';
import { TIPOS } from './blockTypes.js';

const REDES = [
  { ini: 'f', bg: '#1877f2' },
  { ini: 'in', bg: '#0a66c2' },
  { ini: 'ig', bg: '#c13584' },
];

function ToolbarButton({ title, onClick, danger, children }) {
  return (
    <button
      type="button"
      title={title}
      className={'emb-block-tool-btn' + (danger ? ' emb-block-tool-btn--danger' : '')}
      onClick={(e) => {
        e.stopPropagation();
        onClick();
      }}
    >
      {children}
    </button>
  );
}

function BlockBody({ block }) {
  const p = block.props;
  const kind = TIPOS[block.tipo].kind;
  const bg = p.bg || '#ffffff';
  const padding = (p.padding || 0) + 'px';
  const align = p.align || 'left';

  if (kind === 'text') {
    return (
      <div style={{ background: bg, padding, textAlign: align }}>
        <div
          style={{
            fontSize: (p.tamano || 14) + 'px',
            color: p.color || '#4b5563',
            fontWeight: p.bold ? 700 : 400,
            fontStyle: p.italic ? 'italic' : 'normal',
            lineHeight: 1.6,
          }}
        >
          {p.texto}
        </div>
      </div>
    );
  }

  if (kind === 'img') {
    return (
      <div style={{ background: bg, padding, textAlign: align }}>
        <div className="emb-block-img-placeholder" style={{ width: (p.ancho || 100) + '%' }}>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.8">
            <rect x="3" y="4.5" width="18" height="15" rx="2" />
            <circle cx="8.5" cy="10" r="1.6" />
            <path d="M4 17l5-4.5 4 3.5 3-2.5 4 3.5" strokeLinecap="round" />
          </svg>
          <div className="emb-block-img-caption">{p.texto}</div>
        </div>
      </div>
    );
  }

  if (kind === 'btn') {
    return (
      <div style={{ background: bg, padding, textAlign: align }}>
        <span
          className="emb-block-btn"
          style={{
            background: p.btnBg || '#ff6213',
            color: p.color || '#ffffff',
            borderRadius: (p.radio || 0) + 'px',
          }}
        >
          {p.texto}
        </span>
      </div>
    );
  }

  if (kind === 'cols') {
    const cols = [];
    for (let c = 0; c < (p.n || 0); c++) cols.push('Contenido ' + (c + 1));
    return (
      <div style={{ background: bg, padding, display: 'flex', gap: '12px' }}>
        {cols.map((label) => (
          <div className="emb-block-col-placeholder" key={label}>
            {label}
          </div>
        ))}
      </div>
    );
  }

  if (kind === 'space') {
    return (
      <div className="emb-block-space" style={{ background: bg, height: (p.alto || 24) + 'px' }}>
        <span className="emb-block-space-label">{(p.alto || 24) + ' px de espacio'}</span>
      </div>
    );
  }

  if (kind === 'rule') {
    return (
      <div style={{ background: bg, padding }}>
        <div style={{ height: (p.grosor || 1) + 'px', background: p.color || '#e8eaed' }} />
      </div>
    );
  }

  if (kind === 'social') {
    return (
      <div style={{ background: bg, padding, textAlign: align }}>
        <div className="emb-block-social-row">
          {REDES.map((r) => (
            <span className="emb-block-social-avatar" style={{ background: r.bg }} key={r.ini}>
              {r.ini}
            </span>
          ))}
        </div>
      </div>
    );
  }

  if (kind === 'video') {
    return (
      <div style={{ background: bg, padding }}>
        <div className="emb-block-video">
          <span className="emb-block-video-play">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#ffffff">
              <path d="M8 5.5v13l11-6.5z" />
            </svg>
          </span>
          <div className="emb-block-video-caption">{p.texto}</div>
        </div>
      </div>
    );
  }

  if (kind === 'imgtexto') {
    return (
      <div style={{ background: bg, padding, display: 'flex', gap: '14px', alignItems: 'center' }}>
        <div className="emb-block-imgtexto-thumb">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.8">
            <rect x="3" y="4.5" width="18" height="15" rx="2" />
            <path d="M4 17l5-4.5 4 3.5 3-2.5 4 3.5" strokeLinecap="round" />
          </svg>
        </div>
        <div className="emb-block-imgtexto-text">{p.texto}</div>
      </div>
    );
  }

  return null;
}

function DropZone({ active, onDragOver, onDrop }) {
  return (
    <div
      className={'emb-dropzone' + (active ? ' emb-dropzone--active' : '')}
      onDragOver={onDragOver}
      onDrop={onDrop}
    />
  );
}

/**
 * Canvas.jsx
 *
 * Columna central del armador: lienzo del correo con una zona de
 * "soltar" antes de cada bloque y una al final (arrastre HTML5 nativo,
 * mismos handlers `onDragOver`/`onDrop` del mockup). Cada bloque muestra
 * su marcado visual según `kind` y, al pasar el mouse o seleccionarlo,
 * una barra de herramientas flotante (subir/bajar/duplicar/eliminar).
 *
 * El reordenamiento por arrastre de un bloque YA existente se rastrea
 * con estado local `dragIndex` (no vía dataTransfer, que en este
 * componente solo se usa para bloques nuevos venidos de Palette.jsx).
 */
export default function Canvas({
  blocks,
  selectedId,
  hoveredId,
  onSelect,
  onHover,
  onAddBlock,
  onMoveBlock,
  onReorder,
  onDuplicate,
  onDelete,
  previewMode,
}) {
  const [dragIndex, setDragIndex] = useState(null);
  const [dropIndex, setDropIndex] = useState(null);

  const canvasWidth = previewMode === 'mobile' ? '380px' : '600px';

  const handleDropAt = (index) => (e) => {
    e.preventDefault();
    if (dragIndex !== null) {
      const draggedBlock = blocks[dragIndex];
      if (draggedBlock) onReorder(draggedBlock.id, index);
    } else {
      const tipoKey = e.dataTransfer.getData('text/plain');
      if (tipoKey && TIPOS[tipoKey]) onAddBlock(tipoKey, index);
    }
    setDragIndex(null);
    setDropIndex(null);
  };

  const handleDragOverAt = (index) => (e) => {
    e.preventDefault();
    if (dropIndex !== index) setDropIndex(index);
  };

  return (
    <div className="emb-canvas-wrap">
      <div className="emb-canvas" style={{ width: canvasWidth }}>
        {blocks.length === 0 && (
          <div className="emb-canvas-empty" onDragOver={handleDragOverAt(0)} onDrop={handleDropAt(0)}>
            <div className="emb-canvas-empty-title">Arrastra un bloque aquí para empezar</div>
            <div className="emb-canvas-empty-hint">
              O da clic en cualquier bloque del panel izquierdo. El correo mide {canvasWidth} de ancho, la medida estándar de correo.
            </div>
          </div>
        )}

        {blocks.map((block, i) => {
          const selected = selectedId === block.id;
          const hovered = hoveredId === block.id;
          const showTools = selected || hovered;

          return (
            <div key={block.id}>
              <DropZone active={dropIndex === i && dragIndex !== null} onDragOver={handleDragOverAt(i)} onDrop={handleDropAt(i)} />

              <div
                className={
                  'emb-block' +
                  (selected ? ' emb-block--selected' : '') +
                  (hovered ? ' emb-block--hovered' : '') +
                  (dragIndex === i ? ' emb-block--dragging' : '')
                }
                draggable="true"
                onDragStart={() => setDragIndex(i)}
                onDragEnd={() => {
                  setDragIndex(null);
                  setDropIndex(null);
                }}
                onMouseEnter={() => onHover(block.id)}
                onMouseLeave={() => onHover(null)}
                onClick={() => onSelect(block.id)}
              >
                {showTools && (
                  <>
                    <div className="emb-block-toolbar">
                      <ToolbarButton title="Subir" onClick={() => onMoveBlock(block.id, -1)}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4">
                          <path d="M6 14l6-6 6 6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </ToolbarButton>
                      <ToolbarButton title="Bajar" onClick={() => onMoveBlock(block.id, 1)}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4">
                          <path d="M6 10l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </ToolbarButton>
                      <ToolbarButton title="Duplicar" onClick={() => onDuplicate(block.id)}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
                          <rect x="9" y="9" width="11" height="11" rx="2" />
                          <path d="M15 5H6a2 2 0 0 0-2 2v9" strokeLinecap="round" />
                        </svg>
                      </ToolbarButton>
                      <ToolbarButton title="Eliminar" danger onClick={() => onDelete(block.id)}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
                          <path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </ToolbarButton>
                    </div>
                    <div className="emb-block-badge">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                        <circle cx="9" cy="6" r="1" />
                        <circle cx="15" cy="6" r="1" />
                        <circle cx="9" cy="12" r="1" />
                        <circle cx="15" cy="12" r="1" />
                        <circle cx="9" cy="18" r="1" />
                        <circle cx="15" cy="18" r="1" />
                      </svg>
                      {TIPOS[block.tipo].nombre}
                    </div>
                  </>
                )}

                <BlockBody block={block} />
              </div>
            </div>
          );
        })}

        {blocks.length > 0 && (
          <DropZone
            active={dropIndex === blocks.length && dragIndex !== null}
            onDragOver={handleDragOverAt(blocks.length)}
            onDrop={handleDropAt(blocks.length)}
          />
        )}
      </div>
      <div className="emb-canvas-footnote">
        Vista {previewMode === 'mobile' ? 'móvil' : 'escritorio'} · el HTML final se genera con tablas anidadas y estilos en línea para Outlook, Gmail y Apple Mail.
      </div>
    </div>
  );
}
