import React, { useEffect, useRef, useState } from 'react';
import SubHeader from '../SubHeader';
import CustomerPicker from '../lists/CustomerPicker';
import ActiveFilterBuilder from '../lists/ActiveFilterBuilder';
import listsApi from '../api/listsApi';

const EMPTY_CONDITION = { field: 'ciudad', operator: 'igual', value: '' };

export default function ListsTab({ createSignal, onChanged }) {
  const [lists, setLists] = useState(null); // null = cargando
  const [meta, setMeta] = useState(null);
  const [sub, setSub] = useState(null); // null | 'form'
  const [editingList, setEditingList] = useState(null); // null = creando

  const [name, setName] = useState('');
  const [type, setType] = useState('static'); // 'static' | 'active'
  const [condition, setCondition] = useState(EMPTY_CONDITION);
  const [customerIds, setCustomerIds] = useState([]);
  const [estimate, setEstimate] = useState(null);
  const [memberBusy, setMemberBusy] = useState(false);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState(null);

  const firstRun = useRef(true);

  const load = (url) => {
    const request = url
      ? fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then((r) => r.json())
      : listsApi.list();

    request
      .then((res) => {
        setLists((res.data && res.data.data) || []);
        setMeta(res.data || null);
      })
      .catch(() => {
        setLists([]);
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
    openForm(null, 'static');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [createSignal]);

  const resetForm = () => {
    setName('');
    setType('static');
    setCondition(EMPTY_CONDITION);
    setCustomerIds([]);
    setEstimate(null);
    setErrors(null);
  };

  const openForm = (list, forcedType) => {
    resetForm();
    setEditingList(list || null);
    if (list) {
      setName(list.name);
      setType(list.type);
      if (list.type === 'active' && Array.isArray(list.filter_definition) && list.filter_definition[0]) {
        setCondition(list.filter_definition[0]);
      }
      if (list.type === 'static' && Array.isArray(list.members)) {
        setCustomerIds(list.members.map((m) => m.id));
      }
    } else if (forcedType) {
      setType(forcedType);
    }
    setSub('form');
  };

  const closeForm = () => {
    setSub(null);
    setEditingList(null);
    resetForm();
  };

  const handleToggleCustomer = (customerId) => {
    if (editingList) {
      // Lista ya existente: cada toggle pega de inmediato a add-members /
      // remove-member -- EmailListController::update() no acepta
      // customer_ids, así que no hay forma de "batchear" esto con el guardado.
      const isMember = customerIds.includes(customerId);
      setMemberBusy(true);
      const request = isMember
        ? listsApi.removeMember(editingList.id, customerId)
        : listsApi.addMembers(editingList.id, [customerId]);

      request
        .then((updated) => {
          setCustomerIds((updated.members || []).map((m) => m.id));
          if (onChanged) onChanged();
        })
        .finally(() => setMemberBusy(false));
    } else {
      setCustomerIds((ids) => (ids.includes(customerId) ? ids.filter((id) => id !== customerId) : [...ids, customerId]));
    }
  };

  const handleSave = () => {
    if (!name.trim()) {
      setErrors({ name: ['El nombre es obligatorio.'] });
      return;
    }

    setSaving(true);
    setErrors(null);

    const payload = { name, type, condition: type === 'active' ? condition : undefined, customerIds };

    const request = editingList ? listsApi.update(editingList.id, payload) : listsApi.create(payload);

    request
      .then(() => {
        load();
        if (onChanged) onChanged();
        closeForm();
      })
      .catch((err) => {
        setErrors(err.errors || { name: [err.message] });
      })
      .finally(() => setSaving(false));
  };

  const handleDelete = (list) => {
    if (!window.confirm(`¿Eliminar la lista "${list.name}"? Esta acción no se puede deshacer.`)) return;
    listsApi.destroy(list.id).then(() => {
      load();
      if (onChanged) onChanged();
    });
  };

  if (sub === 'form') {
    const modoTexto = type === 'static' ? 'Lista estática' : 'Lista activa';
    const estimadoNota =
      type === 'static'
        ? 'Miembros agregados manualmente.'
        : 'Se recalcula al enviar la campaña. Este número es una estimación con los clientes actuales.';
    const contactos = type === 'static' ? customerIds.length : estimate;

    return (
      <div className="em-view">
        <SubHeader
          title={editingList ? 'Editar lista de contactos' : 'Nueva lista de contactos'}
          hint="Elige el modo de la lista y define sus miembros"
          onBack={closeForm}
        />
        <div className="em-list-form-grid">
          <div className="em-list-form-main">
            <div className="em-field-label">Nombre de la lista</div>
            <input
              type="text"
              className="em-input-lg"
              placeholder="Ej. Clientes industriales de Monterrey"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
            {errors && errors.name && <div className="em-field-error">{errors.name[0]}</div>}

            <div className="em-field-label em-field-label-spaced">Tipo de lista</div>
            <div className="em-mode-grid">
              <div className={`em-mode-card ${type === 'static' ? 'is-active' : ''}`} onClick={() => !editingList && setType('static')}>
                <div className="em-mode-card-head">
                  <span className="em-mode-dot" />
                  <div className="em-mode-card-title">Estática</div>
                </div>
                <div className="em-mode-card-desc">Tú agregas y quitas contactos uno por uno desde el buscador de clientes.</div>
              </div>
              <div className={`em-mode-card ${type === 'active' ? 'is-active' : ''}`} onClick={() => !editingList && setType('active')}>
                <div className="em-mode-card-head">
                  <span className="em-mode-dot" />
                  <div className="em-mode-card-title">Activa</div>
                </div>
                <div className="em-mode-card-desc">Se resuelve con una condición: campo, operador y valor.</div>
              </div>
            </div>
            {editingList && <div className="em-field-hint">El tipo de una lista no se puede cambiar una vez creada.</div>}

            {type === 'static' ? (
              <div className="em-field-block">
                <div className="em-field-label">Miembros</div>
                <CustomerPicker selectedIds={customerIds} onToggle={handleToggleCustomer} busy={memberBusy} />
              </div>
            ) : (
              <div className="em-field-block">
                <div className="em-field-label">Condición del filtro</div>
                <ActiveFilterBuilder condition={condition} onChange={setCondition} onEstimateChange={setEstimate} />
              </div>
            )}

            <div className="em-form-actions">
              <button type="button" className="em-btn-primary" onClick={handleSave} disabled={saving}>
                {saving ? 'Guardando…' : 'Guardar lista'}
              </button>
              <button type="button" className="em-btn-secondary" onClick={closeForm}>
                Cancelar
              </button>
            </div>
          </div>

          <div className="em-list-form-side">
            <div className="em-side-label">Resumen</div>
            <div className="em-summary-card">
              <div className="em-summary-name">{name || 'Lista sin nombre'}</div>
              <div className="em-summary-badge">{modoTexto}</div>
              <div className="em-summary-block">
                <div className="em-side-label">Contactos {type === 'static' ? '' : 'estimados'}</div>
                <div className="em-summary-value">{contactos == null ? '—' : contactos}</div>
                <div className="em-summary-hint">{estimadoNota}</div>
              </div>
            </div>
            <div className="em-side-note">
              Una lista sin contactos puede guardarse; la campaña simplemente no tendrá destinatarios hasta que
              existan clientes que cumplan.
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (lists === null) {
    return <div className="em-loading">Cargando listas…</div>;
  }

  if (lists.length === 0) {
    return (
      <div className="em-empty">
        <div className="em-empty-icon">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9">
            <circle cx="9" cy="8" r="3.4" />
            <path d="M2.8 19.5c0-3.3 2.8-5.2 6.2-5.2s6.2 1.9 6.2 5.2" strokeLinecap="round" />
            <path d="M17 8.5h4.4M17 12h4.4" strokeLinecap="round" />
          </svg>
        </div>
        <h2 className="em-empty-title">Aún no hay listas de contactos</h2>
        <div className="em-empty-desc">
          Una lista define a quién le llega una campaña. Puedes armarla a mano (<strong>estática</strong>) o dejar
          que se resuelva sola con un filtro sobre los datos de tus clientes (<strong>activa</strong>).
        </div>

        <div className="em-mode-grid em-empty-mode-grid">
          <div className="em-preview-card" onClick={() => openForm(null, 'static')}>
            <div className="em-mode-card-head">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
                <path d="M4 7h16M4 12h16M4 17h10" strokeLinecap="round" />
              </svg>
              <div className="em-mode-card-title">Lista estática</div>
            </div>
            <div className="em-mode-card-desc">Tú agregas y quitas contactos uno por uno. La membresía no cambia por sí sola.</div>
          </div>
          <div className="em-preview-card" onClick={() => openForm(null, 'active')}>
            <div className="em-mode-card-head">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.1">
                <path d="M3 5h18l-7 8v6l-4 2v-8L3 5z" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              <div className="em-mode-card-title">Lista activa</div>
            </div>
            <div className="em-mode-card-desc">Se resuelve al momento del envío con una condición. Cada cliente nuevo que cumpla entra solo.</div>
          </div>
        </div>

        <button type="button" className="em-btn-primary em-empty-cta" onClick={() => openForm(null, 'static')}>
          Crear primera lista
        </button>
        <div className="em-empty-footnote">
          Hoy los contactos se agregan desde los clientes ya registrados en el CRM. La importación de archivos CSV
          no está disponible.
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
                <th>Tipo</th>
                <th>Miembros</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {lists.map((l) => (
                <tr key={l.id}>
                  <td className="em-td-primary">{l.name}</td>
                  <td>
                    <span className="em-pill">{l.type === 'static' ? 'Estática' : 'Activa'}</span>
                  </td>
                  <td className="em-td-muted">{l.type === 'static' ? l.members_count ?? 0 : '—'}</td>
                  <td>
                    <div className="em-actions">
                      <button
                        type="button"
                        className="em-action-btn"
                        title={l.type === 'static' ? 'Ver / editar miembros' : 'Editar'}
                        onClick={() => listsApi.show(l.id).then((full) => openForm(full))}
                      >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      </button>
                      <button type="button" className="em-action-btn em-action-btn-delete" title="Eliminar" onClick={() => handleDelete(l)}>
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
        {meta && (meta.prev_page_url || meta.next_page_url) && (
          <div className="em-pagination-bar">
            <div className="em-pagination-info">
              <strong>{meta.total}</strong> listas
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
