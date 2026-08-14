import React from 'react';

const TAB_DEFS = [
  { id: 'plantillas', label: 'Plantillas' },
  { id: 'listas', label: 'Listas' },
  { id: 'campanas', label: 'Campañas' },
  { id: 'secuencias', label: 'Secuencias' },
];

/**
 * Barra de pestañas con contador — subrayado y color naranja para la
 * pestaña activa (mockup líneas 45-52).
 */
export default function Tabs({ active, onChange, counts }) {
  return (
    <div className="em-tabs">
      {TAB_DEFS.map((t) => {
        const on = active === t.id;
        const badge = counts && counts[t.id] != null ? counts[t.id] : '0';

        return (
          <button
            type="button"
            key={t.id}
            className={`em-tab ${on ? 'is-active' : ''}`}
            onClick={() => onChange(t.id)}
          >
            {t.label}
            <span className={`em-tab-badge ${on ? 'is-active' : ''}`}>{badge}</span>
          </button>
        );
      })}
    </div>
  );
}
