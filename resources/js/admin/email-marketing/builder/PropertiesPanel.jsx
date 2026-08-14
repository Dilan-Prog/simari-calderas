import React from 'react';
import { TIPOS, TIPS } from './blockTypes.js';

const ALIGN = [
  { label: 'Izq.', value: 'left' },
  { label: 'Centro', value: 'center' },
  { label: 'Der.', value: 'right' },
];

// ── Constructores de campo, portados de fTexto/fLinea/fColor/fNum/fOps
//    del mockup (líneas ~1206-1211) ────────────────────────────────
function fTexto(label, key, tip) {
  return { type: 'area', label, tip: tip || '', key };
}
function fLinea(label, key, placeholder, tip) {
  return { type: 'text', label, tip: tip || '', key, placeholder: placeholder || '' };
}
function fColor(label, key, hexes) {
  return { type: 'color', label, key, swatches: hexes };
}
function fNum(label, key, min, max, step, unidad, tip) {
  return { type: 'number', label, tip: tip || '', key, min, max, step, unidad: unidad || '' };
}
function fOps(label, key, options) {
  return { type: 'options', label, key, options };
}

// ── Set exacto de campos por `kind`, portado de la construcción de
//    `propFields` del mockup (líneas ~1213-1226) ───────────────────
function fieldsForKind(kind) {
  const fondo = fColor('Color de fondo', 'bg', ['#ffffff', '#fafbfc', '#f7f8fa', '#fff2eb']);
  const espaciado = fNum('Espaciado interior', 'padding', 0, 48, 2, ' px', TIPS.padding);

  switch (kind) {
    case 'text':
      return [
        fTexto('Contenido', 'texto', TIPS.texto),
        fNum('Tamaño de letra', 'tamano', 11, 34, 1, ' px'),
        fColor('Color del texto', 'color', ['#141516', '#4b5563', '#9ca3af', '#ff6213']),
        fOps('Alineación', 'align', ALIGN),
        fOps('Peso', 'bold', [{ label: 'Normal', value: false }, { label: 'Negrita', value: true }]),
        fOps('Estilo', 'italic', [{ label: 'Normal', value: false }, { label: 'Cursiva', value: true }]),
        fondo,
        espaciado,
      ];
    case 'btn':
      return [
        fLinea('Texto del botón', 'texto'),
        fLinea('Enlace', 'url', 'https://', TIPS.url),
        fColor('Color del botón', 'btnBg', ['#ff6213', '#de4a00', '#141516', '#0369a1']),
        fColor('Color del texto', 'color', ['#ffffff', '#141516']),
        fNum('Redondeo', 'radio', 0, 24, 2, ' px'),
        fOps('Alineación', 'align', ALIGN),
        fondo,
        espaciado,
      ];
    case 'img':
      return [
        fLinea('Texto alternativo', 'texto', 'Describe la imagen'),
        fLinea('Enlace al hacer clic', 'url', 'https://', TIPS.url),
        fNum('Ancho', 'ancho', 20, 100, 5, ' %', TIPS.ancho),
        fOps('Alineación', 'align', ALIGN),
        fondo,
        espaciado,
      ];
    case 'cols':
      return [fNum('Número de columnas', 'n', 1, 3, 1, ''), fondo, espaciado];
    case 'space':
      return [fNum('Alto', 'alto', 8, 80, 4, ' px'), fondo];
    case 'rule':
      return [
        fColor('Color de la línea', 'color', ['#e8eaed', '#c9ccd1', '#ff6213', '#141516']),
        fNum('Grosor', 'grosor', 1, 6, 1, ' px'),
        fondo,
        espaciado,
      ];
    case 'video':
      return [fLinea('Título del video', 'texto'), fLinea('Enlace del video', 'url', 'https://', TIPS.url), fondo, espaciado];
    case 'social':
      return [fOps('Alineación', 'align', ALIGN), fondo, espaciado];
    case 'imgtexto':
      return [fTexto('Texto', 'texto', TIPS.texto), fondo, espaciado];
    default:
      return [];
  }
}

function FieldTip({ tip }) {
  if (!tip) return null;
  return (
    <span className="emb-field-tip" title={tip}>
      ?
    </span>
  );
}

function Field({ field, value, onChange }) {
  if (field.type === 'area') {
    return (
      <textarea
        className="emb-field-textarea"
        rows="4"
        value={value ?? ''}
        onChange={(e) => onChange(e.target.value)}
      />
    );
  }

  if (field.type === 'text') {
    return (
      <input
        className="emb-field-input"
        type="text"
        placeholder={field.placeholder}
        value={value ?? ''}
        onChange={(e) => onChange(e.target.value)}
      />
    );
  }

  if (field.type === 'color') {
    return (
      <div className="emb-field-swatches">
        {field.swatches.map((hex) => (
          <button
            key={hex}
            type="button"
            title={hex}
            className={'emb-field-swatch' + (value === hex ? ' emb-field-swatch--active' : '')}
            style={{ background: hex }}
            onClick={() => onChange(hex)}
          />
        ))}
      </div>
    );
  }

  if (field.type === 'number') {
    return (
      <div className="emb-field-range">
        <input
          type="range"
          min={field.min}
          max={field.max}
          step={field.step}
          value={value ?? field.min}
          onChange={(e) => onChange(Number(e.target.value))}
        />
        <span className="emb-field-range-display">{(value ?? field.min) + field.unidad}</span>
      </div>
    );
  }

  if (field.type === 'options') {
    return (
      <div className="emb-field-pills">
        {field.options.map((opt) => (
          <button
            key={String(opt.value)}
            type="button"
            className={'emb-field-pill' + (value === opt.value ? ' emb-field-pill--active' : '')}
            onClick={() => onChange(opt.value)}
          >
            {opt.label}
          </button>
        ))}
      </div>
    );
  }

  return null;
}

/**
 * PropertiesPanel.jsx
 *
 * Columna derecha del armador. Sin selección muestra un placeholder;
 * con un bloque seleccionado, el set de campos EXACTO según su `kind`
 * (ver `fieldsForKind`, portado literal de la construcción de
 * `propFields` del mockup, líneas ~1213-1226).
 *
 * Props: selectedBlock, onChangeProp(key, value), onDuplicate, onDelete, onDeselect
 */
export default function PropertiesPanel({ selectedBlock, onChangeProp, onDuplicate, onDelete, onDeselect }) {
  if (!selectedBlock) {
    return (
      <div className="emb-props">
        <div className="emb-props-empty">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c9ccd1" strokeWidth="1.8">
            <path d="M12 3v3M12 18v3M3 12h3M18 12h3" strokeLinecap="round" />
            <circle cx="12" cy="12" r="4" />
          </svg>
          <div className="emb-props-empty-title">Selecciona un bloque</div>
          <div className="emb-props-empty-hint">Aquí aparecerán sus opciones: colores, espaciado, tipografía, enlaces.</div>
        </div>
      </div>
    );
  }

  const kind = TIPOS[selectedBlock.tipo].kind;
  const fields = fieldsForKind(kind);

  return (
    <div className="emb-props">
      <div className="emb-props-header">
        <div>
          <div className="emb-props-eyebrow">Bloque</div>
          <div className="emb-props-title">{TIPOS[selectedBlock.tipo].nombre}</div>
        </div>
        <button type="button" className="emb-props-close" title="Cerrar" onClick={onDeselect}>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4">
            <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
          </svg>
        </button>
      </div>

      {fields.map((field) => (
        <div className="emb-field" key={field.key}>
          <div className="emb-field-label-row">
            <span className="emb-field-label">{field.label}</span>
            <FieldTip tip={field.tip} />
          </div>
          <Field field={field} value={selectedBlock.props[field.key]} onChange={(v) => onChangeProp(field.key, v)} />
        </div>
      ))}

      <div className="emb-props-actions">
        <button type="button" className="emb-props-action" onClick={() => onDuplicate(selectedBlock.id)}>
          Duplicar
        </button>
        <button type="button" className="emb-props-action emb-props-action--danger" onClick={() => onDelete(selectedBlock.id)}>
          Eliminar
        </button>
      </div>
    </div>
  );
}
