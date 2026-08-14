import React, { useEffect, useRef, useState } from 'react';
import SubHeader from '../SubHeader';
import StepTimeline from '../sequences/StepTimeline';
import sequencesApi from '../api/sequencesApi';
import templatesApi from '../api/templatesApi';

// 3 presets verbatim del mockup (líneas 1080-1084).
const PRESETS = [
  { nombre: 'Seguimiento de cotización', esperas: [0, 3, 5], desc: 'Recordatorio suave a los 3 y 8 días de enviar la cotización.' },
  { nombre: 'Bienvenida de cliente nuevo', esperas: [0, 3], desc: 'Presentación el mismo día y catálogo tres días después.' },
  { nombre: 'Reactivación de cliente', esperas: [0, 7, 14], desc: 'Tres acercamientos repartidos en tres semanas.' },
];

let _stepKeySeq = 0;
function newStep(delayDays = 0, templateId = '', stepId = null) {
  _stepKeySeq += 1;
  return { key: `step-${_stepKeySeq}`, stepId, templateId, delayDays };
}

export default function SequencesTab({ createSignal, onChanged }) {
  const [sequences, setSequences] = useState(null); // null = cargando
  const [sub, setSub] = useState(null); // null | 'start' | 'builder'
  const [editingSequence, setEditingSequence] = useState(null); // null = creando
  const [originalSteps, setOriginalSteps] = useState([]); // pasos tal como venían del backend, para reconciliar al guardar

  const [templateOptions, setTemplateOptions] = useState(null);
  const [seqNombre, setSeqNombre] = useState('');
  const [seqSteps, setSeqSteps] = useState([newStep(0), newStep(3)]);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState(null);

  const firstRun = useRef(true);

  const load = () => {
    sequencesApi
      .list()
      .then((data) => setSequences(Array.isArray(data) ? data : []))
      .catch(() => setSequences([]));
  };

  useEffect(() => {
    load();
    templatesApi.options().then(setTemplateOptions).catch(() => setTemplateOptions([]));
  }, []);

  useEffect(() => {
    if (firstRun.current) {
      firstRun.current = false;
      return;
    }
    setSub('start');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [createSignal]);

  const openStart = () => setSub('start');

  const openBuilderFromPreset = (preset) => {
    setEditingSequence(null);
    setOriginalSteps([]);
    setSeqNombre(preset.nombre);
    setSeqSteps(preset.esperas.map((e) => newStep(e)));
    setSaveError(null);
    setSub('builder');
  };

  const openBuilderBlank = () => {
    setEditingSequence(null);
    setOriginalSteps([]);
    setSeqNombre('');
    setSeqSteps([newStep(0), newStep(3)]);
    setSaveError(null);
    setSub('builder');
  };

  const openBuilderForEdit = (sequence) => {
    sequencesApi.show(sequence.id).then((full) => {
      setEditingSequence(full);
      const steps = (full.steps || []).map((s) => newStep(s.delay_days, s.template_id, s.id));
      setOriginalSteps(steps.map((s) => ({ stepId: s.stepId, templateId: s.templateId, delayDays: s.delayDays })));
      setSeqNombre(full.name);
      setSeqSteps(steps.length ? steps : [newStep(0)]);
      setSaveError(null);
      setSub('builder');
    });
  };

  const closeSub = () => {
    setSub(null);
    setEditingSequence(null);
    setOriginalSteps([]);
  };

  const handleChangeStep = (key, patch) => {
    setSeqSteps((steps) => steps.map((s) => (s.key === key ? { ...s, ...patch } : s)));
  };

  const handleRemoveStep = (key) => {
    setSeqSteps((steps) => (steps.length > 1 ? steps.filter((s) => s.key !== key) : steps));
  };

  const handleAddStep = () => {
    setSeqSteps((steps) => [...steps, newStep(3)]);
  };

  const seqDias = seqSteps.reduce((acc, s) => acc + (Number(s.delayDays) || 0), 0);

  const handleSave = () => {
    if (!seqNombre.trim()) {
      setSaveError({ name: ['El nombre es obligatorio.'] });
      return;
    }
    if (seqSteps.some((s) => !s.templateId)) {
      setSaveError({ steps: ['Selecciona una plantilla para cada paso.'] });
      return;
    }

    setSaving(true);
    setSaveError(null);

    if (!editingSequence) {
      sequencesApi
        .create({
          name: seqNombre,
          steps: seqSteps.map((s) => ({ template_id: Number(s.templateId), delay_days: Number(s.delayDays) || 0 })),
        })
        .then(() => {
          load();
          if (onChanged) onChanged();
          closeSub();
        })
        .catch((err) => setSaveError(err.errors || { name: [err.message] }))
        .finally(() => setSaving(false));
      return;
    }

    // Edición: EmailSequenceController::update() solo toca name/owner_id/is_active,
    // no hay endpoint para reemplazar los steps en batch. Se reconcilia
    // manualmente contra add-step/remove-step:
    // - paso original que ya no está en la lista actual -> removeStep
    // - paso nuevo (sin stepId) -> addStep
    // - paso existente cuyos valores cambiaron -> removeStep + addStep
    //   (no existe endpoint de "editar paso"; esto puede resetear el
    //   progreso de inscripciones que ya estaban en ese paso exacto, es la
    //   única forma que expone el backend hoy).
    const currentByStepId = new Map(seqSteps.filter((s) => s.stepId).map((s) => [s.stepId, s]));
    const removals = [];
    const additions = [];

    originalSteps.forEach((orig) => {
      const current = currentByStepId.get(orig.stepId);
      if (!current) {
        removals.push(orig.stepId);
        return;
      }
      if (Number(current.templateId) !== Number(orig.templateId) || Number(current.delayDays) !== Number(orig.delayDays)) {
        removals.push(orig.stepId);
        additions.push({ templateId: current.templateId, delayDays: current.delayDays });
      }
    });

    seqSteps
      .filter((s) => !s.stepId)
      .forEach((s) => additions.push({ templateId: s.templateId, delayDays: s.delayDays }));

    const removeChain = removals.reduce((p, stepId) => p.then(() => sequencesApi.removeStep(stepId)), Promise.resolve());

    removeChain
      .then(() =>
        additions.reduce(
          (p, add) =>
            p.then(() =>
              sequencesApi.addStep(editingSequence.id, { templateId: Number(add.templateId), delayDays: Number(add.delayDays) || 0 })
            ),
          Promise.resolve()
        )
      )
      .then(() => sequencesApi.update(editingSequence.id, { name: seqNombre, is_active: editingSequence.is_active }))
      .then(() => {
        load();
        if (onChanged) onChanged();
        closeSub();
      })
      .catch((err) => setSaveError(err.errors || { name: [err.message] }))
      .finally(() => setSaving(false));
  };

  const handleDelete = (sequence) => {
    if (!window.confirm(`¿Eliminar la secuencia "${sequence.name}"? Esta acción no se puede deshacer.`)) return;
    sequencesApi.destroy(sequence.id).then(() => {
      load();
      if (onChanged) onChanged();
    });
  };

  if (sub === 'start') {
    return (
      <div className="em-view">
        <SubHeader title="Nueva secuencia" hint="Paso 1 de 2 · elige un ritmo de envío" onBack={closeSub} />
        <div className="em-start-wrap">
          <h2 className="em-start-title">Elige un ritmo de envío</h2>
          <div className="em-start-desc">
            Cada opción arma los pasos y las esperas por ti. Después puedes agregar, quitar o cambiar los días.
          </div>

          <div className="em-start-grid">
            {PRESETS.map((p) => {
              const dias = p.esperas.reduce((a, b) => a + b, 0);
              return (
                <div key={p.nombre} className="em-start-card" onClick={() => openBuilderFromPreset(p)}>
                  <div className="em-start-card-title">{p.nombre}</div>
                  <div className="em-preset-meta">{p.esperas.length} correos · {dias} días</div>
                  <div className="em-preset-dots">
                    {p.esperas.map((_, i) => (
                      <React.Fragment key={i}>
                        <span className="em-preset-dot" />
                        {i < p.esperas.length - 1 && <span className="em-preset-line" />}
                      </React.Fragment>
                    ))}
                  </div>
                  <div className="em-start-card-sub">{p.desc}</div>
                </div>
              );
            })}
          </div>

          <div className="em-start-actions">
            <button type="button" className="em-btn-dashed" onClick={openBuilderBlank}>
              Armar mis propios pasos
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (sub === 'builder') {
    const puedeGuardar = seqNombre.trim() && seqSteps.every((s) => s.templateId) && !saving;

    return (
      <div className="em-view">
        <SubHeader title={editingSequence ? 'Editar secuencia' : 'Nueva secuencia'} hint="Pasos lineales con espera en días" onBack={closeSub} />
        <div className="em-seq-grid">
          <div className="em-seq-main">
            <div className="em-field-label">Nombre de la secuencia</div>
            <input
              type="text"
              className="em-input-lg em-input-narrow"
              placeholder="Ej. Seguimiento post-cotización"
              value={seqNombre}
              onChange={(e) => setSeqNombre(e.target.value)}
            />
            {saveError && saveError.name && <div className="em-field-error">{saveError.name[0]}</div>}

            <div className="em-field-label em-field-label-spaced">Pasos</div>
            <div className="em-field-hint em-field-hint-spaced">Se ejecutan en este orden. La espera se cuenta desde el paso anterior.</div>
            {saveError && saveError.steps && <div className="em-field-error">{saveError.steps[0]}</div>}

            <StepTimeline
              steps={seqSteps}
              templateOptions={templateOptions}
              onChangeStep={handleChangeStep}
              onRemoveStep={handleRemoveStep}
              onAddStep={handleAddStep}
            />

            <div className="em-form-actions">
              <button type="button" className="em-btn-primary" onClick={handleSave} disabled={!puedeGuardar}>
                {saving ? 'Guardando…' : 'Guardar secuencia'}
              </button>
              <button type="button" className="em-btn-secondary" onClick={closeSub}>
                Cancelar
              </button>
            </div>
          </div>

          <div className="em-seq-side">
            <div className="em-side-label">Duración total</div>
            <div className="em-summary-card">
              <div className="em-seq-duration-row">
                <div className="em-summary-value">{seqDias}</div>
                <div className="em-seq-duration-sub">días · {seqSteps.length} correos</div>
              </div>
              <div className="em-summary-hint">Del primer envío al último, si el contacto se inscribe hoy.</div>
            </div>

            <div className="em-side-label em-side-label-spaced">Contactos inscritos</div>
            <div className="em-summary-card em-summary-card-center">
              <div className="em-summary-value-lg">{editingSequence ? editingSequence.enrollments_count || 0 : 0}</div>
              <div className="em-summary-hint">
                {editingSequence && editingSequence.enrollments_count
                  ? 'Verás su paso actual y su fecha de próximo envío desde el detalle del contacto.'
                  : 'Nadie inscrito todavía. Al inscribir un contacto verás su paso actual y su fecha de próximo envío.'}
              </div>
            </div>
            <div className="em-side-note">Guarda la secuencia con todos sus pasos antes de inscribir contactos: los pasos se ejecutan siempre en orden.</div>
          </div>
        </div>
      </div>
    );
  }

  if (sequences === null) {
    return <div className="em-loading">Cargando secuencias…</div>;
  }

  if (sequences.length === 0) {
    return (
      <div className="em-empty">
        <div className="em-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9">
            <circle cx="12" cy="5" r="2.4" />
            <circle cx="12" cy="12" r="2.4" />
            <circle cx="12" cy="19" r="2.4" />
            <path d="M12 7.4v2.2M12 14.4v2.2" strokeLinecap="round" />
          </svg>
        </div>
        <h2 className="em-empty-title">Aún no hay secuencias</h2>
        <div className="em-empty-desc">
          Una secuencia envía varias plantillas en orden, con días de espera entre una y otra. Cada contacto inscrito
          avanza paso por paso según su fecha de próximo envío.
        </div>
        <button type="button" className="em-btn-primary em-empty-cta" onClick={openStart}>
          Crear primera secuencia
        </button>
        <div className="em-empty-footnote">
          La secuencia es lineal: no hay bifurcaciones ni condiciones del tipo "si abrió el correo". Si necesitas un
          camino distinto, crea otra secuencia.
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
                <th>Pasos</th>
                <th>Estado</th>
                <th>Inscritos</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {sequences.map((s) => (
                <tr key={s.id}>
                  <td className="em-td-primary">{s.name}</td>
                  <td className="em-td-muted">{(s.steps && s.steps.length) || 0}</td>
                  <td>
                    <span className={`em-pill ${s.is_active ? 'em-pill-success' : 'em-pill-neutral'}`}>
                      {s.is_active ? 'Activa' : 'Pausada'}
                    </span>
                  </td>
                  <td className="em-td-muted">{s.enrollments_count ?? 0}</td>
                  <td>
                    <div className="em-actions">
                      <button type="button" className="em-action-btn" title="Editar" onClick={() => openBuilderForEdit(s)}>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </button>
                      <button type="button" className="em-action-btn em-action-btn-delete" title="Eliminar" onClick={() => handleDelete(s)}>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
