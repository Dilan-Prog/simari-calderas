import React, { useEffect, useRef, useState } from 'react';
import SubHeader from '../SubHeader';
import templatesApi from '../api/templatesApi';
// El armador de bloques / editor de código vive en builder/, construido en
// paralelo por otro agente bajo el mismo contrato de props documentado más
// abajo. NO se toca ese directorio desde aquí.
import TemplateEditorShell from '../builder/TemplateEditorShell';

// 3 tipos de plantilla que ya existen en el sistema -- solo se muestran
// como referencia decorativa en el estado vacío (clic en cualquiera abre
// el mismo paso 1 "¿con qué quieres empezar?", igual que "Crear primera
// plantilla"; el mockup no distingue el destino de estas tarjetas).
const TPL_TYPES = [
  { tipo: 'Campaña', nombre: 'Promoción trimestral', asunto: 'Nuevas bombas de calor con 12% de descuento' },
  { tipo: 'Secuencia', nombre: 'Seguimiento de cotización', asunto: '¿Revisaste la cotización que te enviamos?' },
  { tipo: 'Transaccional', nombre: 'Orden enviada', asunto: 'Tu orden #{folio} ya va en camino' },
];

// 3 puntos de partida reales del paso 2 (mockup STARTERS, líneas 1070-1078).
// `tplTipo` usa el mismo vocabulario que el mockup portado (minúsculas, sin
// acentos) -- es el valor que el armador de bloques del agente hermano usa
// para elegir el set base de bloques (BLOQUES_BASE) y para el campo `type`
// que finalmente se guarda.
const STARTERS = [
  {
    tipo: 'Campaña',
    tplTipo: 'campana',
    nombre: 'Promoción trimestral',
    asunto: 'Nuevas bombas de calor con 12% de descuento',
  },
  {
    tipo: 'Secuencia',
    tplTipo: 'secuencia',
    nombre: 'Seguimiento de cotización',
    asunto: '¿Revisaste la cotización que te enviamos?',
  },
  {
    tipo: 'Transaccional',
    tplTipo: 'transaccional',
    nombre: 'Orden enviada',
    asunto: 'Tu orden ya va en camino',
  },
];

const TYPE_LABELS = {
  campana: 'Campaña',
  campaign: 'Campaña',
  secuencia: 'Secuencia',
  sequence: 'Secuencia',
  transaccional: 'Transaccional',
  transactional: 'Transaccional',
};

function typeLabel(type) {
  return TYPE_LABELS[type] || type || '—';
}

export default function TemplatesTab({ createSignal, onChanged, userInitials }) {
  const [templates, setTemplates] = useState(null); // null = cargando
  const [meta, setMeta] = useState(null);
  const [sub, setSub] = useState(null); // null | 'start' | 'editor'
  const [editingTemplate, setEditingTemplate] = useState(null);
  const [pickedStarter, setPickedStarter] = useState(null);
  const [pickedMode, setPickedMode] = useState('sencilla');

  const firstRun = useRef(true);

  const load = (url) => {
    const request = url
      ? fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((r) => r.json())
      : templatesApi.list();

    request
      .then((res) => {
        setTemplates((res.data && res.data.data) || []);
        setMeta(res.data || null);
      })
      .catch(() => {
        setTemplates([]);
        setMeta(null);
      });
  };

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    if (firstRun.current) {
      firstRun.current = false;
      return;
    }
    setSub('start');
  }, [createSignal]);

  const openStart = () => setSub('start');

  const openEditorWithStarter = (starter, mode) => {
    setEditingTemplate(null);
    setPickedStarter(starter);
    setPickedMode(mode);
    setSub('editor');
  };

  const openEditForRow = (template) => {
    setEditingTemplate(template);
    setPickedStarter(null);
    // Sin mode explícito aquí a propósito: TemplateEditorShell decide el
    // modo inicial a partir de la plantilla real (seedFrom), incluyendo el
    // fallback a "avanzada" cuando builder_mode dice "blocks" pero
    // blocks_json vino vacío/null. Si aquí se forzara 'sencilla' por
    // builder_mode, pisaría ese fallback (initialMode gana sobre seed.mode).
    setPickedMode(null);
    setSub('editor');
  };

  const closeEditor = () => {
    setSub(null);
    setEditingTemplate(null);
    setPickedStarter(null);
  };

  const handleSaved = () => {
    closeEditor();
    load();
    if (onChanged) onChanged();
  };

  const handleDelete = (template) => {
    if (!window.confirm(`¿Eliminar la plantilla "${template.name}"? Esta acción no se puede deshacer.`)) return;
    templatesApi.destroy(template.id).then(() => {
      load();
      if (onChanged) onChanged();
    });
  };

  if (sub === 'editor') {
    return (
      <TemplateEditorShell
        template={editingTemplate}
        starter={pickedStarter}
        initialMode={pickedMode}
        onSaved={handleSaved}
        onCancel={closeEditor}
      />
    );
  }

  if (sub === 'start') {
    return (
      <div className="em-view">
        <SubHeader title="Nueva plantilla" hint="Paso 1 de 2 · elige un punto de partida" onBack={closeEditor} />
        <div className="em-start-wrap">
          <h2 className="em-start-title">¿Con qué quieres empezar?</h2>
          <div className="em-start-desc">
            Elige un punto de partida y llegarás al editor con el asunto y el contenido ya listos para ajustar.
          </div>

          <div className="em-start-grid">
            {STARTERS.map((st) => (
              <div
                key={st.nombre}
                className="em-start-card"
                onClick={() => openEditorWithStarter(st, 'sencilla')}
              >
                <div className="em-start-card-eyebrow">{st.tipo}</div>
                <div className="em-start-card-title">{st.nombre}</div>
                <div className="em-start-card-sub">{st.asunto}</div>
                <div className="em-start-card-cta">Usar este →</div>
              </div>
            ))}
          </div>

          <div className="em-start-actions">
            <button type="button" className="em-btn-dashed" onClick={() => openEditorWithStarter(null, 'sencilla')}>
              Lienzo vacío · armar con bloques
            </button>
            <button type="button" className="em-btn-dashed" onClick={() => openEditorWithStarter(null, 'avanzada')}>
              Escribir HTML a mano
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (templates === null) {
    return <div className="em-loading">Cargando plantillas…</div>;
  }

  if (templates.length === 0) {
    return (
      <div className="em-empty">
        <div className="em-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9">
            <rect x="2.5" y="5" width="19" height="14" rx="2.5" />
            <path d="M3 7l9 6 9-6" strokeLinecap="round" />
          </svg>
        </div>
        <h2 className="em-empty-title">Aún no hay plantillas de correo</h2>
        <div className="em-empty-desc">
          Una plantilla guarda el <strong>asunto</strong> y el <strong>cuerpo HTML</strong> del correo. Puedes
          insertar tokens dinámicos para personalizar cada envío con datos del contacto o del negocio.
        </div>
        <button type="button" className="em-btn-primary em-empty-cta" onClick={openStart}>
          Crear primera plantilla
        </button>

        <div className="em-start-grid em-empty-preview-grid">
          {TPL_TYPES.map((tt) => (
            <div key={tt.nombre} className="em-preview-card" onClick={openStart}>
              <div className="em-start-card-eyebrow">{tt.tipo}</div>
              <div className="em-start-card-title">{tt.nombre}</div>
              <div className="em-start-card-sub">{tt.asunto}</div>
            </div>
          ))}
        </div>
        <div className="em-empty-footnote">
          Los tres tipos de plantilla existentes en el sistema. Comenzarás con un HTML base editable o un armador de
          bloques, no con datos precargados.
        </div>
      </div>
    );
  }

  return (
    <div className="em-view">
      <div className="em-table-card">
        <div className="em-table-scroll">
          <table className="em-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Asunto</th>
                <th>Tipo</th>
                <th>Modo</th>
                <th>Creada</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {templates.map((t) => (
                <tr key={t.id}>
                  <td className="em-td-primary">{t.name}</td>
                  <td className="em-td-secondary">{t.subject}</td>
                  <td>
                    <span className="em-pill">{typeLabel(t.type)}</span>
                    {t.is_system && (
                      <span className="em-pill" title="Plantilla del sistema: editable, no se puede eliminar" style={{ marginLeft: 6 }}>
                        Sistema
                      </span>
                    )}
                  </td>
                  <td className="em-td-muted">{t.builder_mode === 'blocks' ? 'Bloques' : 'Código'}</td>
                  <td className="em-td-muted">{t.created_at ? new Date(t.created_at).toLocaleDateString('es-MX') : '—'}</td>
                  <td>
                    <div className="em-actions">
                      <button type="button" className="em-action-btn" title="Editar" onClick={() => openEditForRow(t)}>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </button>
                      {t.is_system ? (
                        <button
                          type="button"
                          className="em-action-btn"
                          title="Plantilla del sistema — no se puede eliminar"
                          disabled
                        >
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <rect x="4" y="10" width="16" height="10" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" strokeLinecap="round" strokeLinejoin="round" />
                          </svg>
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="em-action-btn em-action-btn-delete"
                          title="Eliminar"
                          onClick={() => handleDelete(t)}
                        >
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13" strokeLinecap="round" strokeLinejoin="round" />
                          </svg>
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {meta && (meta.prev_page_url || meta.next_page_url) && (
          <div className="em-pagination-bar">
            <div className="em-pagination-info">
              <strong>{meta.total}</strong> plantillas
            </div>
            <div className="em-actions">
              <button type="button" className="em-btn-dashed" disabled={!meta.prev_page_url} onClick={() => load(meta.prev_page_url)}>
                Anterior
              </button>
              <button type="button" className="em-btn-dashed" disabled={!meta.next_page_url} onClick={() => load(meta.next_page_url)}>
                Siguiente
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
