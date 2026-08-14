import React, { useEffect, useRef, useState } from 'react';
import SubHeader from '../SubHeader';
import CampaignKpis from '../campaigns/CampaignKpis';
import ClickMapTable from '../campaigns/ClickMapTable';
import RecipientsList from '../campaigns/RecipientsList';
import campaignsApi from '../api/campaignsApi';
import templatesApi from '../api/templatesApi';
import listsApi from '../api/listsApi';

const STATUS_LABELS = {
  draft: { label: 'Borrador', cls: 'em-pill-neutral' },
  scheduled: { label: 'Programada', cls: 'em-pill-warning' },
  sending: { label: 'Enviando', cls: 'em-pill-warning' },
  sent: { label: 'Enviada', cls: 'em-pill-success' },
};

function statusPill(status) {
  return STATUS_LABELS[status] || { label: status || '—', cls: 'em-pill-neutral' };
}

export default function CampaignsTab({ createSignal, onChanged, onGoToTab }) {
  const [campaigns, setCampaigns] = useState(null); // null = cargando
  const [meta, setMeta] = useState(null);
  const [sub, setSub] = useState(null); // null | 'crear' | 'detail'
  const [detailId, setDetailId] = useState(null);
  const [detail, setDetail] = useState(null);

  const [templateOptions, setTemplateOptions] = useState(null);
  const [listOptions, setListOptions] = useState(null);

  const [campNombre, setCampNombre] = useState('');
  const [templateId, setTemplateId] = useState('');
  const [listId, setListId] = useState('');
  const [envio, setEnvio] = useState('inmediato'); // 'inmediato' | 'programado'
  const [campFecha, setCampFecha] = useState('');
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState(null);

  const firstRun = useRef(true);

  const load = (url) => {
    const request = url
      ? fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((r) => r.json())
      : campaignsApi.list();

    request
      .then((res) => {
        setCampaigns((res.data && res.data.data) || []);
        setMeta(res.data || null);
      })
      .catch(() => {
        setCampaigns([]);
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
    openCrear();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [createSignal]);

  const loadOptions = () => {
    templatesApi.options().then(setTemplateOptions).catch(() => setTemplateOptions([]));
    listsApi.options().then(setListOptions).catch(() => setListOptions([]));
  };

  const openCrear = (prefill) => {
    loadOptions();
    setCampNombre((prefill && prefill.name) || '');
    setTemplateId((prefill && prefill.template_id) || '');
    setListId((prefill && prefill.list_id) || '');
    setEnvio('inmediato');
    setCampFecha('');
    setSaveError(null);
    setSub('crear');
  };

  const openDetail = (id) => {
    setDetailId(id);
    setDetail(null);
    setSub('detail');
    campaignsApi.show(id).then(setDetail);
  };

  const closeSub = () => {
    setSub(null);
    setDetail(null);
    setDetailId(null);
  };

  const handleCreate = () => {
    if (!campNombre.trim() || !templateId || !listId) return;
    if (envio === 'programado' && !campFecha) return;

    setSaving(true);
    setSaveError(null);

    campaignsApi
      .create({ template_id: Number(templateId), name: campNombre, list_id: Number(listId) })
      .then((campaign) => {
        const followUp =
          envio === 'programado'
            ? campaignsApi.schedule(campaign.id, campFecha)
            : campaignsApi.send(campaign.id);

        return followUp.then(() => campaign);
      })
      .then((campaign) => {
        load();
        if (onChanged) onChanged();
        openDetail(campaign.id);
      })
      .catch((err) => {
        setSaveError(err.errors || { name: [err.message] });
      })
      .finally(() => setSaving(false));
  };

  const handleDelete = (campaign) => {
    if (!window.confirm(`¿Eliminar la campaña "${campaign.name}"? Esta acción no se puede deshacer.`)) return;
    campaignsApi.destroy(campaign.id).then(() => {
      load();
      if (onChanged) onChanged();
    });
  };

  const handleDuplicate = () => {
    if (!detail) return;
    openCrear({
      name: `${detail.campaign.name} (copia)`,
      template_id: detail.campaign.template_id,
      list_id: detail.campaign.list_id,
    });
  };

  if (sub === 'crear') {
    const noTemplates = templateOptions && templateOptions.length === 0;
    const noLists = listOptions && listOptions.length === 0;
    const puedeGuardar = campNombre.trim() && templateId && listId && (envio !== 'programado' || campFecha) && !saving;

    return (
      <div className="em-view">
        <SubHeader title="Nueva campaña" hint="Plantilla, lista y cuándo enviar" onBack={closeSub} />
        <div className="em-camp-create-wrap">
          <input
            type="text"
            className="em-input-title"
            placeholder="Nombre de la campaña"
            value={campNombre}
            onChange={(e) => setCampNombre(e.target.value)}
          />
          <div className="em-camp-create-hint">Tres decisiones y queda lista.</div>

          <div className="em-camp-field">
            <span className="em-camp-field-num">1</span>
            <div className="em-camp-field-body">
              <div className="em-camp-field-label">¿Qué correo se envía?</div>
              <div className="em-camp-field-hint">La plantilla define el asunto y el contenido.</div>
              <select value={templateId} onChange={(e) => setTemplateId(e.target.value)}>
                <option value="">Elegir plantilla…</option>
                {(templateOptions || []).map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.name}
                  </option>
                ))}
              </select>
              {noTemplates && (
                <div className="em-camp-empty-msg">
                  <span>Todavía no hay plantillas guardadas.</span>
                  <a href="#" onClick={(e) => { e.preventDefault(); onGoToTab('plantillas'); }}>
                    Crear una
                  </a>
                </div>
              )}
            </div>
          </div>

          <div className="em-camp-field">
            <span className="em-camp-field-num">2</span>
            <div className="em-camp-field-body">
              <div className="em-camp-field-label">¿A quién le llega?</div>
              <div className="em-camp-field-hint">La lista define a los destinatarios.</div>
              <select value={listId} onChange={(e) => setListId(e.target.value)}>
                <option value="">Elegir lista…</option>
                {(listOptions || []).map((l) => (
                  <option key={l.id} value={l.id}>
                    {l.name}
                  </option>
                ))}
              </select>
              {noLists && (
                <div className="em-camp-empty-msg">
                  <span>Todavía no hay listas de contactos.</span>
                  <a href="#" onClick={(e) => { e.preventDefault(); onGoToTab('listas'); }}>
                    Crear una
                  </a>
                </div>
              )}
            </div>
          </div>

          <div className="em-camp-field">
            <span className="em-camp-field-num">3</span>
            <div className="em-camp-field-body">
              <div className="em-camp-field-label">¿Cuándo se envía?</div>
              <div className="em-camp-envio-grid">
                <button
                  type="button"
                  className={`em-camp-envio-card ${envio === 'inmediato' ? 'is-active' : ''}`}
                  onClick={() => setEnvio('inmediato')}
                >
                  <div className="em-camp-envio-title">Enviar ahora</div>
                  <div className="em-camp-envio-desc">En cuanto guardes.</div>
                </button>
                <button
                  type="button"
                  className={`em-camp-envio-card ${envio === 'programado' ? 'is-active' : ''}`}
                  onClick={() => setEnvio('programado')}
                >
                  <div className="em-camp-envio-title">Programar</div>
                  <div className="em-camp-envio-desc">En la fecha que elijas.</div>
                </button>
              </div>
              {envio === 'programado' && (
                <input
                  type="datetime-local"
                  className="em-input-lg em-camp-fecha"
                  value={campFecha}
                  onChange={(e) => setCampFecha(e.target.value)}
                />
              )}
            </div>
          </div>

          {saveError && (
            <div className="em-field-error em-camp-error">
              {Object.values(saveError).flat().join(' ')}
            </div>
          )}

          <div className="em-camp-footer">
            <div className="em-camp-footer-text">
              {!templateId || !listId
                ? 'Falta elegir la plantilla y/o la lista para poder enviar.'
                : envio === 'programado'
                ? 'Se programará para la fecha elegida.'
                : 'Se enviará en cuanto guardes.'}
            </div>
            <div className="em-form-actions">
              <button type="button" className="em-btn-secondary" onClick={closeSub}>
                Cancelar
              </button>
              <button type="button" className="em-btn-primary" onClick={handleCreate} disabled={!puedeGuardar}>
                {saving ? 'Guardando…' : envio === 'programado' ? 'Programar campaña' : 'Enviar campaña'}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (sub === 'detail') {
    if (!detail) {
      return (
        <div className="em-view">
          <SubHeader title="Detalle de campaña" hint="Analítica por destinatario y mapa de clics" onBack={closeSub} />
          <div className="em-loading">Cargando campaña…</div>
        </div>
      );
    }

    const { campaign, metrics, click_map: clickMap, sends } = detail;
    const status = statusPill(campaign.status);

    return (
      <div className="em-view">
        <SubHeader title="Detalle de campaña" hint="Analítica por destinatario y mapa de clics" onBack={closeSub} />

        <div className="em-camp-detail-head">
          <div>
            <div className="em-camp-detail-title-row">
              <h2 className="em-camp-detail-title">{campaign.name}</h2>
              <span className={`em-pill ${status.cls}`}>{status.label}</span>
            </div>
            <div className="em-camp-detail-meta">
              <div>
                <span className="em-meta-key">Plantilla:</span> <strong>{campaign.template ? campaign.template.name : '—'}</strong>
              </div>
              <div>
                <span className="em-meta-key">Lista:</span> <strong>{campaign.list ? campaign.list.name : '—'}</strong>
              </div>
              <div>
                <span className="em-meta-key">Enviada:</span>{' '}
                <strong>{campaign.sent_at ? new Date(campaign.sent_at).toLocaleString('es-MX') : 'Todavía no'}</strong>
              </div>
            </div>
          </div>
          <div className="em-camp-detail-actions">
            <div className="em-ab-chip" title="Sin lógica de envío A/B implementada">
              Prueba A/B <span className="em-ab-badge">Próximamente</span>
            </div>
            <button type="button" className="em-btn-secondary" onClick={handleDuplicate}>
              Duplicar campaña
            </button>
          </div>
        </div>

        <CampaignKpis metrics={metrics} />

        <div className="em-detail-grid">
          <ClickMapTable clickMap={clickMap} />
          <RecipientsList sends={sends} />
        </div>
      </div>
    );
  }

  if (campaigns === null) {
    return <div className="em-loading">Cargando campañas…</div>;
  }

  if (campaigns.length === 0) {
    return (
      <div className="em-empty">
        <div className="em-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9">
            <path d="M21.5 3.5L11 14M21.5 3.5l-7 17.5-3.5-7-7-3.5 17.5-7z" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </div>
        <h2 className="em-empty-title">Aún no has creado campañas</h2>
        <div className="em-empty-desc">
          Una campaña une una <strong>plantilla</strong> con una <strong>lista</strong> y se envía de inmediato o en
          la fecha que programes. Después del envío verás la analítica por destinatario.
        </div>

        <div className="em-camp-steps">
          {[
            { n: '1', title: 'Elige plantilla', sub: 'Asunto y cuerpo HTML' },
            { n: '2', title: 'Elige lista', sub: 'Estática o activa' },
            { n: '3', title: 'Programa o envía', sub: 'Fecha o envío inmediato' },
          ].map((cs, i, arr) => (
            <React.Fragment key={cs.n}>
              <div className="em-camp-step">
                <div className="em-camp-step-num">PASO {cs.n}</div>
                <div className="em-camp-step-title">{cs.title}</div>
                <div className="em-camp-step-sub">{cs.sub}</div>
              </div>
              {i < arr.length - 1 && <span className="em-camp-step-arrow">→</span>}
            </React.Fragment>
          ))}
        </div>

        <button type="button" className="em-btn-primary em-empty-cta" onClick={() => openCrear()}>
          Crear primera campaña
        </button>
        <div className="em-empty-footnote">Necesitas al menos una plantilla y una lista para poder programar un envío.</div>
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
                <th>Plantilla</th>
                <th>Lista</th>
                <th>Estatus</th>
                <th>Enviada</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {campaigns.map((c) => {
                const status = statusPill(c.status);
                return (
                  <tr key={c.id}>
                    <td className="em-td-primary">{c.name}</td>
                    <td className="em-td-secondary">{c.template ? c.template.name : '—'}</td>
                    <td className="em-td-secondary">{c.list ? c.list.name : '—'}</td>
                    <td>
                      <span className={`em-pill ${status.cls}`}>{status.label}</span>
                    </td>
                    <td className="em-td-muted">{c.sent_at ? new Date(c.sent_at).toLocaleDateString('es-MX') : '—'}</td>
                    <td>
                      <div className="em-actions">
                        <button type="button" className="em-action-btn" title="Ver detalle" onClick={() => openDetail(c.id)}>
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" strokeLinecap="round" strokeLinejoin="round" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </button>
                        <button type="button" className="em-action-btn em-action-btn-delete" title="Eliminar" onClick={() => handleDelete(c)}>
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13" strokeLinecap="round" strokeLinejoin="round" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        {meta && (meta.prev_page_url || meta.next_page_url) && (
          <div className="em-pagination-bar">
            <div className="em-pagination-info">
              <strong>{meta.total}</strong> campañas
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
