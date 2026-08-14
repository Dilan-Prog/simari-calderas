import React from 'react';

/**
 * Barra "Volver" que aparece arriba de cualquier sub-pantalla (formulario/
 * detalle) dentro de una pestaña -- mismo bloque que el mockup repite en
 * todas las pestañas (líneas 54-63 del mockup), factorizado aquí porque
 * las 4 pestañas lo necesitan igual.
 */
export default function SubHeader({ title, hint, onBack }) {
  return (
    <div className="em-subheader">
      <button type="button" className="em-btn-back" onClick={onBack}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4">
          <path d="M15 18l-6-6 6-6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        Volver
      </button>
      <div className="em-subheader-title">{title}</div>
      {hint ? <div className="em-subheader-hint">{hint}</div> : null}
    </div>
  );
}
