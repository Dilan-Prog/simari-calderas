import React, { useEffect, useState } from 'react';
import { TIPOS, TIPS } from './blockTypes.js';
import * as templatesApi from '../api/templatesApi.js';

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

/**
 * LogoPickerField -- selector visual de logos reales (public/images/logo/ y
 * sus variantes Blanco/Blanco-color/Negro/Negro-color), solo para el bloque
 * "Logo" (selectedBlock.tipo === 'logo', no el bloque genérico "Imagen").
 * Escribe la URL elegida en la MISMA prop `url` que ya alimenta el <img src>
 * en buildEmailHtml.js -- no se agrega ninguna prop nueva, solo una forma
 * visual de rellenar la que ya existía (antes solo un campo de texto crudo).
 */
function LogoPickerField({ value, onChange }) {
  const [logos, setLogos] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;

    templatesApi
      .logos()
      .then((res) => {
        if (!cancelled) setLogos(Array.isArray(res?.data) ? res.data : []);
      })
      .catch((err) => {
        if (!cancelled) setError(err?.message || 'No se pudieron cargar los logos.');
      });

    return () => {
      cancelled = true;
    };
  }, []);

  if (error) {
    return <div className="emb-logo-picker-error">{error}</div>;
  }

  if (logos === null) {
    return <div className="emb-logo-picker-loading">Cargando logos…</div>;
  }

  if (logos.length === 0) {
    return <div className="emb-logo-picker-empty">No hay ningún logo en public/images/logo/ todavía.</div>;
  }

  return (
    <div className="emb-logo-picker-grid">
      {logos.map((logo) => (
        <button
          key={logo.url}
          type="button"
          title={logo.label}
          className={'emb-logo-picker-item' + (value === logo.url ? ' emb-logo-picker-item--active' : '')}
          onClick={() => onChange(logo.url)}
        >
          <img src={logo.url} alt={logo.label} />
        </button>
      ))}
    </div>
  );
}

// ── Selector de color ampliado ──────────────────────────────────────
// Paleta amplia fija (independiente de los "sugeridos" por campo, que
// siguen viniendo de blockTypes.js/fColor) + colores personalizados que
// el usuario agrega con su propio hex, persistidos en localStorage para
// que sigan disponibles en cualquier bloque/plantilla, no solo en el que
// los creó.
const BROAD_PALETTE = [
  '#000000', '#141516', '#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#f3f4f6', '#ffffff',
  '#ef4444', '#f97316', '#ff6213', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981',
  '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
  '#ec4899', '#f43f5e', '#7c2d12', '#78350f', '#365314', '#134e4a', '#1e3a8a', '#4c1d95',
];

const CUSTOM_COLORS_KEY = 'emb-custom-colors';

function loadCustomColors() {
  try {
    const raw = window.localStorage.getItem(CUSTOM_COLORS_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function saveCustomColors(colors) {
  try {
    window.localStorage.setItem(CUSTOM_COLORS_KEY, JSON.stringify(colors));
  } catch {
    // localStorage no disponible (modo privado, cuota llena) -- el color
    // elegido igual se aplica al bloque, solo no persiste entre sesiones.
  }
}

function isValidHex(hex) {
  return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(hex);
}

function ColorField({ suggested, value, onChange }) {
  const [customColors, setCustomColors] = useState(loadCustomColors);
  const [showPicker, setShowPicker] = useState(false);
  const [hexInput, setHexInput] = useState(value || '#ff6213');

  const paletteColors = BROAD_PALETTE.filter((hex) => !suggested.includes(hex));

  const addCustomColor = () => {
    if (!isValidHex(hexInput)) return;
    const hex = hexInput.startsWith('#') ? hexInput : `#${hexInput}`;
    if (!customColors.includes(hex)) {
      const next = [...customColors, hex];
      setCustomColors(next);
      saveCustomColors(next);
    }
    onChange(hex);
    setShowPicker(false);
  };

  const removeCustomColor = (hex, e) => {
    e.stopPropagation();
    const next = customColors.filter((c) => c !== hex);
    setCustomColors(next);
    saveCustomColors(next);
  };

  const renderSwatch = (hex, removable) => (
    <button
      key={hex}
      type="button"
      title={hex}
      className={'emb-field-swatch' + (value === hex ? ' emb-field-swatch--active' : '')}
      style={{ background: hex, position: 'relative' }}
      onClick={() => onChange(hex)}
    >
      {removable && (
        <span
          onClick={(e) => removeCustomColor(hex, e)}
          title="Quitar de mis colores"
          style={{
            position: 'absolute', top: -5, right: -5, width: 14, height: 14, borderRadius: '50%',
            background: '#141516', color: '#fff', fontSize: 9, lineHeight: '14px', textAlign: 'center',
            cursor: 'pointer', boxShadow: '0 0 0 1px #fff',
          }}
        >
          &times;
        </span>
      )}
    </button>
  );

  return (
    <div>
      {suggested.length > 0 && (
        <div className="emb-field-swatches" style={{ marginBottom: 8 }}>
          {suggested.map((hex) => renderSwatch(hex, false))}
        </div>
      )}

      <div className="emb-field-swatches" style={{ marginBottom: customColors.length ? 8 : 0 }}>
        {paletteColors.map((hex) => renderSwatch(hex, false))}
      </div>

      {customColors.length > 0 && (
        <div style={{ marginBottom: 8 }}>
          <div style={{ fontSize: 11, color: '#9ca3af', marginBottom: 4 }}>Mis colores</div>
          <div className="emb-field-swatches">
            {customColors.map((hex) => renderSwatch(hex, true))}
          </div>
        </div>
      )}

      {!showPicker ? (
        <button
          type="button"
          onClick={() => { setHexInput(value || '#ff6213'); setShowPicker(true); }}
          style={{
            display: 'flex', alignItems: 'center', gap: 6, border: '1px dashed #d1d5db', borderRadius: 6,
            padding: '6px 10px', background: 'transparent', color: '#4b5563', fontSize: 12, cursor: 'pointer', width: '100%',
          }}
        >
          + Color personalizado
        </button>
      ) : (
        <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          <input
            type="color"
            value={isValidHex(hexInput) ? (hexInput.startsWith('#') ? hexInput : `#${hexInput}`) : '#ff6213'}
            onChange={(e) => setHexInput(e.target.value)}
            style={{ width: 30, height: 30, padding: 0, border: 'none', borderRadius: 6, cursor: 'pointer', flexShrink: 0 }}
          />
          <input
            type="text"
            value={hexInput}
            onChange={(e) => setHexInput(e.target.value)}
            placeholder="#ff6213"
            className="emb-field-input"
            style={{ flex: 1 }}
          />
          <button
            type="button"
            onClick={addCustomColor}
            disabled={!isValidHex(hexInput)}
            style={{
              border: 'none', borderRadius: 6, padding: '7px 10px', fontSize: 12, fontWeight: 700, cursor: 'pointer',
              background: isValidHex(hexInput) ? '#ff6213' : '#f2b28f', color: '#fff', flexShrink: 0,
            }}
          >
            Guardar
          </button>
        </div>
      )}
    </div>
  );
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
    return <ColorField suggested={field.swatches} value={value} onChange={onChange} />;
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

      {selectedBlock.tipo === 'logo' && (
        <div className="emb-field">
          <div className="emb-field-label-row">
            <span className="emb-field-label">Elegir logo</span>
            <FieldTip tip="Se lee directo de public/images/logo/ -- sube nuevos archivos ahí para que aparezcan aquí." />
          </div>
          <LogoPickerField value={selectedBlock.props.url} onChange={(v) => onChangeProp('url', v)} />
        </div>
      )}

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
