import React from 'react';

const PRIMARY_LABELS = {
  plantillas: 'Nueva plantilla',
  listas: 'Nueva lista',
  campanas: 'Nueva campaña',
  secuencias: 'Nueva secuencia',
};

/**
 * Encabezado de la página: breadcrumb + título + botón primario cuyo
 * texto cambia según la pestaña activa (mockup líneas 27-42).
 */
export default function Header({ activeTab, onPrimaryAction }) {
  const label = PRIMARY_LABELS[activeTab] || 'Nuevo';

  return (
    <div className="em-page-header">
      <div className="em-breadcrumb">
        <span>Panel de Control</span>
        <span className="em-breadcrumb-sep">›</span>
        <span className="em-breadcrumb-current">Email Marketing</span>
      </div>

      <div className="em-title-row">
        <div>
          <h1 className="em-title">Email Marketing</h1>
          <div className="em-subtitle">
            Plantillas, listas de contactos, campañas y secuencias de correo para Equiterm Industries.
          </div>
        </div>
        <div className="em-title-actions">
          <button type="button" className="em-btn-primary" onClick={onPrimaryAction}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6">
              <path d="M12 5v14M5 12h14" strokeLinecap="round" />
            </svg>
            {label}
          </button>
        </div>
      </div>
    </div>
  );
}
