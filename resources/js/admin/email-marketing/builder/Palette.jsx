import React from 'react';
import { PALETA, TIPOS, ICONOS } from './blockTypes.js';

function BlockIcon({ tipo }) {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9">
      <path d={ICONOS[tipo]} strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

/**
 * Palette.jsx
 *
 * Columna izquierda del armador: catálogo de bloques agrupado en 4
 * categorías (Estructura/Contenido/Multimedia/Encabezado y pie).
 * Arrastre HTML5 nativo (sin librería): `onDragStart` deposita el tipo
 * de bloque como texto plano en el dataTransfer; `Canvas.jsx` lo lee en
 * su `onDrop`. Un clic simple agrega el bloque al final del lienzo.
 *
 * Props:
 *  - onAddBlock(tipoKey, index): agrega un bloque; index=null => al final.
 */
export default function Palette({ onAddBlock }) {
  const handleDragStart = (tipoKey) => (e) => {
    e.dataTransfer.setData('text/plain', tipoKey);
    e.dataTransfer.effectAllowed = 'copy';
  };

  return (
    <div className="emb-palette">
      <div className="emb-palette-title">Bloques</div>
      <div className="emb-palette-hint">Arrastra al lienzo o da clic para agregarlo al final.</div>

      {PALETA.map((cat) => (
        <div className="emb-palette-cat" key={cat.nombre}>
          <div className="emb-palette-cat-name">{cat.nombre}</div>
          <div className="emb-palette-grid">
            {cat.items.map((tipoKey) => (
              <div
                key={tipoKey}
                className="emb-palette-item"
                draggable="true"
                onDragStart={handleDragStart(tipoKey)}
                onClick={() => onAddBlock(tipoKey, null)}
                title={'Agregar bloque de ' + TIPOS[tipoKey].nombre.toLowerCase()}
              >
                <span className="emb-palette-item-icon">
                  <BlockIcon tipo={tipoKey} />
                </span>
                <span className="emb-palette-item-label">{TIPOS[tipoKey].nombre}</span>
              </div>
            ))}
          </div>
        </div>
      ))}

      <div className="emb-palette-footnote">Los tokens de personalización siguen disponibles dentro de los bloques de texto.</div>
    </div>
  );
}
