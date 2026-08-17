import React, { useEffect, useRef, useState } from 'react';
import BlockBuilderEditor from './BlockBuilderEditor.jsx';
import CodeEditorMode from '../codeEditor/CodeEditorMode.jsx';
import { useBlockHistory } from './useBlockHistory.js';
import { buildEmailHtml } from './buildEmailHtml.js';
import { BLOQUES_BASE } from './blockTypes.js';
import { useDebouncedAutosave } from '../hooks/useDebouncedAutosave.js';
import * as templatesApi from '../api/templatesApi.js';

const TIPO_OPTIONS = [
  { value: 'campana', label: 'Campaña' },
  { value: 'secuencia', label: 'Secuencia' },
  { value: 'transaccional', label: 'Transaccional' },
];

function seedFrom(template, starter) {
  if (template) {
    const blocks = Array.isArray(template.blocks_json) ? template.blocks_json : [];
    // Si builder_mode dice "blocks" pero blocks_json vino vacío/null (dato
    // legado de antes de que existiera el armador, o una fila inconsistente),
    // mostrar la vista sencilla igual dejaría un lienzo vacío mientras el
    // contenido real vive en html_body -- exactamente el bug reportado
    // ("no aparece la plantilla en vista sencilla, en avanzada sí"). Cae a
    // "avanzada" en ese caso para no esconder contenido real que sí existe.
    const hasRealBlocks = blocks.length > 0;
    const hasHtmlOnly = !hasRealBlocks && Boolean(template.html_body);
    return {
      name: template.name || '',
      subject: template.subject || '',
      tplTipo: template.type || 'campana',
      blocks,
      codeHtml: template.html_body || '',
      mode: template.builder_mode === 'code' || hasHtmlOnly ? 'avanzada' : 'sencilla',
      templateId: template.id ?? null,
    };
  }
  if (starter) {
    return {
      name: starter.nombre || '',
      subject: starter.asunto || '',
      tplTipo: starter.tplTipo || 'campana',
      blocks: BLOQUES_BASE(starter.tipo),
      codeHtml: starter.html || '',
      mode: 'sencilla',
      templateId: null,
    };
  }
  return { name: '', subject: '', tplTipo: 'campana', blocks: [], codeHtml: '', mode: 'sencilla', templateId: null };
}

/**
 * TemplateEditorShell.jsx
 *
 * Punto de integración que la app contenedora (dueña del resto de
 * `email-marketing/`) monta para editar una plantilla, ya sea existente
 * (`template`), a partir de un punto de partida (`starter`, uno de los
 * `STARTERS` de blockTypes.js) o en blanco (ambos null). Coordina:
 *  - Los campos "propios" de la plantilla (nombre/asunto/tipo) y el
 *    interruptor Vista sencilla / Vista avanzada.
 *  - El historial de bloques (`useBlockHistory`), única fuente de
 *    verdad para el arreglo de bloques -- BlockBuilderEditor es un
 *    componente de presentación que solo reporta arreglos nuevos hacia
 *    arriba, no guarda su propio historial (ver nota en ese archivo).
 *  - El autoguardado: sin `id` (plantilla nueva sin guardar aún) no hay
 *    nada que autoguardar -- el primer clic en "Guardar plantilla" hace
 *    `templatesApi.create()` y, a partir de ahí, cada cambio dispara
 *    `useDebouncedAutosave` -> `templatesApi.update(id, payload)`.
 *
 * Props: { template, starter, initialMode, onSaved, onCancel }
 */
export default function TemplateEditorShell({ template, starter, initialMode, onSaved, onCancel }) {
  const seedRef = useRef(seedFrom(template, starter));
  const seed = seedRef.current;

  const [name, setName] = useState(seed.name);
  const [subject, setSubject] = useState(seed.subject);
  const [tplTipo, setTplTipo] = useState(seed.tplTipo);
  const [mode, setMode] = useState(initialMode || seed.mode);
  const [codeHtml, setCodeHtml] = useState(seed.codeHtml);
  const [previewMode, setPreviewMode] = useState('desktop');
  const [templateId, setTemplateId] = useState(seed.templateId);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState(null);
  const [showModeWarning, setShowModeWarning] = useState(false);

  const { blocks, mutate, undo, redo, canUndo, canRedo } = useBlockHistory(seed.blocks);

  const buildPayload = () => ({
    name,
    subject,
    html_body: mode === 'sencilla' ? buildEmailHtml(blocks) : codeHtml,
    blocks_json: mode === 'sencilla' ? blocks : null,
    builder_mode: mode === 'sencilla' ? 'blocks' : 'code',
    type: tplTipo,
  });

  // ── Autoguardado: no hace nada mientras no exista `templateId` (no
  //    se puede autoguardar un registro que todavía no existe en BD).
  const autosave = useDebouncedAutosave((payload) => {
    if (!templateId) return;
    return templatesApi.update(templateId, payload);
  }, 1500);

  const firstRenderRef = useRef(true);
  useEffect(() => {
    if (firstRenderRef.current) {
      firstRenderRef.current = false;
      return;
    }
    if (templateId) {
      autosave.triggerSave(buildPayload());
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [name, subject, tplTipo, mode, blocks, codeHtml, templateId]);

  const handleBlocksChangeFromBuilder = (newBlocks) => {
    mutate(() => newBlocks);
  };

  const handleRequestCodeView = (generatedHtml) => {
    setCodeHtml(generatedHtml);
    setMode('avanzada');
  };

  const handleSave = async () => {
    setSaving(true);
    setSaveError(null);
    try {
      // Vacía cualquier autoguardado pendiente para no pisar el guardado
      // explícito con un payload más viejo llegando tarde.
      if (typeof autosave.flushNow === 'function') autosave.flushNow();

      const payload = buildPayload();
      let saved;
      if (templateId) {
        saved = await templatesApi.update(templateId, payload);
      } else {
        saved = await templatesApi.create(payload);
        setTemplateId(saved?.id ?? null);
      }
      onSaved(saved);
    } catch (err) {
      setSaveError(err?.message || 'No se pudo guardar la plantilla.');
    } finally {
      setSaving(false);
    }
  };

  // autosave.status es un código interno en inglés ('idle'|'saving'|'saved',
  // ver useDebouncedAutosave.js) -- nunca se muestra tal cual, siempre se
  // traduce aquí antes de pasarlo al toolbar.
  const AUTOSAVE_LABELS = {
    saving: 'Guardando…',
    saved: 'Guardado',
    idle: 'Los cambios se guardan automáticamente',
  };
  const autosaveLabel = templateId
    ? (AUTOSAVE_LABELS[autosave.status] || 'Los cambios se guardan automáticamente')
    : 'Sin guardar todavía';

  return (
    <div className="emb-shell">
      <div className="emb-shell-header">
        <div className="emb-shell-header-fields">
          <input
            className="emb-shell-input emb-shell-input--name"
            type="text"
            placeholder="Nombre de la plantilla"
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
          <input
            className="emb-shell-input emb-shell-input--subject"
            type="text"
            placeholder="Asunto del correo"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
          />
          <select className="emb-shell-select" value={tplTipo} onChange={(e) => setTplTipo(e.target.value)}>
            {TIPO_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        <div className="emb-shell-header-actions">
          <div className="emb-shell-mode-toggle">
            <button
              type="button"
              className={'emb-shell-mode-btn' + (mode === 'sencilla' ? ' emb-shell-mode-btn--active' : '')}
              onClick={() => {
                // Bug real detectado en producción (2026-08-15): si la
                // plantilla se diseñó/editó en Vista avanzada (HTML crudo)
                // y por eso `blocks` sigue vacío, cambiar a Vista sencilla
                // arranca el lienzo en blanco -- y el autoguardado (1.5s
                // después, sin que el usuario haga nada más) persiste ese
                // lienzo vacío, BORRANDO el HTML real en silencio. Se avisa
                // explícitamente antes de permitir el cambio en ese caso,
                // en vez de perder datos sin decir nada.
                if (mode === 'avanzada' && blocks.length === 0 && codeHtml.trim() !== '') {
                  setShowModeWarning(true);
                  return;
                }
                setMode('sencilla');
              }}
            >
              Vista sencilla
            </button>
            <button
              type="button"
              className={'emb-shell-mode-btn' + (mode === 'avanzada' ? ' emb-shell-mode-btn--active' : '')}
              onClick={() => setMode('avanzada')}
            >
              Vista avanzada
            </button>
          </div>
          <button type="button" className="emb-shell-cancel-btn" onClick={onCancel}>
            Cancelar
          </button>
          <button type="button" className="emb-shell-save-btn" disabled={saving} onClick={handleSave}>
            {saving ? 'Guardando…' : 'Guardar plantilla'}
          </button>
        </div>
      </div>

      {saveError && <div className="emb-shell-error">{saveError}</div>}

      {mode === 'sencilla' ? (
        <BlockBuilderEditor
          blocks={blocks}
          onBlocksChange={handleBlocksChangeFromBuilder}
          canUndo={canUndo}
          canRedo={canRedo}
          onUndo={undo}
          onRedo={redo}
          previewMode={previewMode}
          onPreviewModeChange={setPreviewMode}
          onRequestCodeView={handleRequestCodeView}
          autosaveStatus={autosaveLabel}
        />
      ) : (
        <CodeEditorMode html={codeHtml} subject={subject} onChangeHtml={setCodeHtml} onChangeSubject={setSubject} templateId={templateId} />
      )}

      {showModeWarning && (
        <div className="emb-confirm-overlay active" onClick={() => setShowModeWarning(false)}>
          <div className="emb-confirm-box" onClick={(e) => e.stopPropagation()}>
            <div className="emb-confirm-icon-wrap">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" strokeWidth="2">
                <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A2 2 0 0 0 3.82 21h16.36a2 2 0 0 0 1.71-2.96L13.71 3.86a2 2 0 0 0-3.42 0z" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </div>
            <div className="emb-confirm-title">¿Cambiar a Vista sencilla?</div>
            <div className="emb-confirm-desc">
              Esta plantilla se armó en Vista avanzada (HTML) y no tiene bloques equivalentes en Vista sencilla.
              Cambiar ahora mostrará un lienzo <strong>vacío</strong>, y en cuanto autoguarde (unos segundos) se{' '}
              <strong>borrará el HTML actual</strong> sin poder deshacerlo.
            </div>
            <div className="emb-confirm-actions">
              <button type="button" className="emb-confirm-btn-cancel" onClick={() => setShowModeWarning(false)}>
                Cancelar
              </button>
              <button
                type="button"
                className="emb-confirm-btn-danger"
                onClick={() => {
                  setShowModeWarning(false);
                  setMode('sencilla');
                }}
              >
                Sí, continuar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
