import React, { useEffect, useRef, useState } from 'react';
import { TOKENS } from '../builder/blockTypes.js';
import * as templatesApi from '../api/templatesApi.js';

const RAW_PREVIEW_PLACEHOLDER =
  '<p style="font-family:sans-serif;color:#9CA3AF;padding:16px;">Escribe HTML en el editor para verlo aquí.</p>';

/**
 * CodeEditorMode.jsx
 *
 * Vista avanzada del editor de Plantillas (modo código): puerto a React
 * de la lógica de `resources/js/admin/email-template-editor.js` (token
 * picker por posición de cursor + preview en vivo), con el catálogo de
 * tokens corregido (TOKENS de blockTypes.js -- {{contact.*}}/{{deal.*}}/
 * {{unsubscribe_url}}, no el catálogo en español del mockup).
 *
 * Comportamiento create-vs-edit, igual que el JS original:
 *  - Plantilla NUEVA (sin `templateId`): el iframe muestra el HTML crudo
 *    del textarea tal cual, SIN resolver tokens (no hay registro en BD
 *    contra el cual resolver nada todavía).
 *  - Plantilla EXISTENTE (`templateId` presente): el iframe se llena vía
 *    `templatesApi.preview(templateId)`, que resuelve los tokens contra
 *    un Customer de ejemplo en el backend
 *    (EmailTemplateController::preview -> EmailTemplateService::render).
 *    Igual que el editor viejo, ese endpoint resuelve contra el
 *    `html_body` YA GUARDADO en BD, no contra el valor del textarea en
 *    este instante -- por eso aquí se refresca de forma debounced en vez
 *    de en cada tecla, para darle tiempo al autoguardado (dueño de
 *    TemplateEditorShell) de persistir el cambio primero.
 *
 * Props: html, subject, onChangeHtml, onChangeSubject, templateId
 *   (opcional -- prop añadida sobre el set literal pedido porque sin un
 *   id no hay forma de saber si la plantilla ya existe ni a qué llamar
 *   `templatesApi.preview()`; ver nota de reconciliación en el reporte
 *   final).
 */
export default function CodeEditorMode({ html, subject, onChangeHtml, onChangeSubject, templateId }) {
  const isEdit = Boolean(templateId);
  const textareaRef = useRef(null);
  const pendingCursorRef = useRef(null);
  const debounceRef = useRef(null);

  const [previewMode, setPreviewMode] = useState('desktop');
  const [resolvedHtml, setResolvedHtml] = useState('');
  const [resolvedSubject, setResolvedSubject] = useState('');
  const [loadingPreview, setLoadingPreview] = useState(false);
  const [previewError, setPreviewError] = useState(null);

  // Restaura la posición del cursor tras insertar un token (el textarea
  // es controlado, así que el cursor se resetea en cada re-render salvo
  // que lo forcemos de vuelta después de aplicar el nuevo valor).
  useEffect(() => {
    if (pendingCursorRef.current != null && textareaRef.current) {
      const pos = pendingCursorRef.current;
      pendingCursorRef.current = null;
      textareaRef.current.focus();
      textareaRef.current.setSelectionRange(pos, pos);
    }
  }, [html]);

  // ── Preview: modo creación (sin id) -- HTML crudo, sin resolver tokens ──
  useEffect(() => {
    if (isEdit) return;
    setResolvedHtml(html || RAW_PREVIEW_PLACEHOLDER);
    setResolvedSubject(subject || '');
  }, [isEdit, html, subject]);

  // ── Preview: modo edición -- fetch real, debounced ────────────────
  useEffect(() => {
    if (!isEdit) return;

    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      let cancelled = false;
      setLoadingPreview(true);
      setPreviewError(null);

      templatesApi
        .preview(templateId)
        .then((data) => {
          if (cancelled) return;
          setResolvedSubject(data?.subject ?? '');
          setResolvedHtml(data?.html ?? '');
        })
        .catch((err) => {
          if (cancelled) return;
          setPreviewError(err?.message || 'No se pudo generar la vista previa.');
        })
        .finally(() => {
          if (cancelled) return;
          setLoadingPreview(false);
        });

      return () => {
        cancelled = true;
      };
    }, 500);

    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isEdit, templateId, html, subject]);

  const insertToken = (code) => {
    const textarea = textareaRef.current;
    const current = html || '';
    const start = textarea ? textarea.selectionStart ?? current.length : current.length;
    const end = textarea ? textarea.selectionEnd ?? current.length : current.length;
    const before = current.slice(0, start);
    const after = current.slice(end);
    const next = before + code + after;
    pendingCursorRef.current = start + code.length;
    onChangeHtml(next);
  };

  const previewWidth = previewMode === 'mobile' ? '380px' : '600px';

  return (
    <div className="emb-code">
      <div className="emb-code-col emb-code-col--editor">
        <div className="emb-code-panel-header">
          <div className="emb-code-panel-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2">
              <path d="M8 6l-5 6 5 6M16 6l5 6-5 6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            Cuerpo HTML
          </div>
          <div className="emb-code-char-count">{(html || '').length} caracteres</div>
        </div>

        <div className="emb-code-tokens">
          <div className="emb-code-tokens-title">Insertar token dinámico</div>
          <div className="emb-code-tokens-row">
            {TOKENS.map(([code, desc]) => (
              <button key={code} type="button" className="emb-code-token-btn" title={desc} onClick={() => insertToken(code)}>
                {code}
              </button>
            ))}
          </div>
          <div className="emb-code-tokens-hint">
            Al enviar, cada token se reemplaza con el dato del destinatario. Si el campo está vacío, se sustituye por texto en blanco.
          </div>
        </div>

        <textarea
          ref={textareaRef}
          className="emb-code-textarea"
          spellCheck="false"
          value={html || ''}
          onChange={(e) => onChangeHtml(e.target.value)}
        />

        <div className="emb-code-footer">
          <div className="emb-code-footer-hint">Editor de código. También puedes usar el armador visual de bloques en Vista sencilla.</div>
        </div>
      </div>

      <div className="emb-code-col emb-code-col--preview">
        <div className="emb-code-panel-header emb-code-panel-header--preview">
          <div className="emb-code-panel-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M2 12s3.8-6.5 10-6.5S22 12 22 12s-3.8 6.5-10 6.5S2 12 2 12z" />
              <circle cx="12" cy="12" r="2.6" />
            </svg>
            Vista previa en vivo
          </div>
          <div className="emb-preview-toggle">
            <button
              type="button"
              className={'emb-preview-toggle-btn' + (previewMode !== 'mobile' ? ' emb-preview-toggle-btn--active' : '')}
              onClick={() => setPreviewMode('desktop')}
            >
              Escritorio
            </button>
            <button
              type="button"
              className={'emb-preview-toggle-btn' + (previewMode === 'mobile' ? ' emb-preview-toggle-btn--active' : '')}
              onClick={() => setPreviewMode('mobile')}
            >
              Móvil
            </button>
          </div>
        </div>

        <div className="emb-code-subject-line">
          <span className="emb-code-subject-label">Asunto:</span>{' '}
          <strong>{previewError ? '—' : isEdit ? resolvedSubject || subject || '—' : subject || '—'}</strong>
          {loadingPreview && <span className="emb-code-subject-loading"> · actualizando…</span>}
        </div>

        <div className="emb-code-iframe-wrap">
          <div className="emb-code-iframe-shell" style={{ width: previewWidth }}>
            <iframe
              title="Vista previa del correo"
              srcDoc={previewError ? '<p style="font-family:sans-serif;color:#b91c1c;padding:16px;">' + previewError + '</p>' : resolvedHtml}
              className="emb-code-iframe"
            />
          </div>
        </div>

        <div className="emb-code-footer emb-code-footer--preview">
          {isEdit
            ? 'Los tokens se resuelven contra un cliente de ejemplo real. La vista se actualiza tras guardar los cambios.'
            : 'Los tokens se muestran sin resolver hasta guardar la plantilla por primera vez.'}
        </div>
      </div>
    </div>
  );
}
