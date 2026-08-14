import React from 'react';

/**
 * Timeline vertical de pasos de una secuencia (mockup líneas 793-829).
 * Puramente presentacional: recibe los pasos ya resueltos y delega toda
 * mutación al padre (SequencesTab), que decide cómo reconciliar los
 * cambios contra la API al guardar.
 */
export default function StepTimeline({ steps, templateOptions, onChangeStep, onRemoveStep, onAddStep }) {
  return (
    <div className="em-seq-timeline">
      {steps.map((step, i) => {
        const esperaLabel = i === 0 ? 'Enviar tras inscribir' : `Espera desde paso ${i}`;
        const espera = Number(step.delayDays) || 0;
        const nota =
          i === 0
            ? espera === 0
              ? 'Se envía el mismo día de la inscripción.'
              : `Se envía ${espera} días después de inscribir al contacto.`
            : `Se envía ${espera} días después del paso ${i}.`;

        return (
          <div key={step.key} className="em-seq-step">
            <div className="em-seq-step-num">{i + 1}</div>
            <div className="em-seq-step-card">
              <div className="em-seq-step-grid">
                <div>
                  <div className="em-field-label">Plantilla a enviar</div>
                  <select
                    value={step.templateId || ''}
                    onChange={(e) => onChangeStep(step.key, { templateId: e.target.value })}
                  >
                    <option value="">Selecciona una plantilla…</option>
                    {(templateOptions || []).map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <div className="em-field-label">{esperaLabel}</div>
                  <div className="em-seq-espera-row">
                    <input
                      type="number"
                      min="0"
                      value={step.delayDays}
                      onChange={(e) => onChangeStep(step.key, { delayDays: e.target.value })}
                    />
                    <span>días</span>
                  </div>
                </div>
                <button
                  type="button"
                  className="em-seq-step-remove"
                  title="Eliminar paso"
                  onClick={() => onRemoveStep(step.key)}
                  disabled={steps.length <= 1}
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                </button>
              </div>
              <div className="em-seq-step-note">{nota}</div>
            </div>
          </div>
        );
      })}

      <button type="button" className="em-seq-add-step" onClick={onAddStep}>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6">
          <path d="M12 5v14M5 12h14" strokeLinecap="round" />
        </svg>
        Agregar paso
      </button>
    </div>
  );
}
