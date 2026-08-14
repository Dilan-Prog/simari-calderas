import React from 'react';

/**
 * Estado de envío por destinatario (mockup líneas 718-735).
 *
 * NOTA (discrepancia con el contrato asumido): EmailCampaignController::show()
 * hace `$emailCampaign->sends()->with('clicks')->get()` -- NO carga la
 * relación `customer` de cada EmailSend. Para un envío con customer_id, el
 * JSON trae el id pero no nombre/correo del cliente. Aquí se hace fallback
 * a customer si algún día se carga, luego a guest_name/guest_email
 * (invitados), y por último a "Cliente #id" para no dejar la fila en blanco.
 */
function estadoDe(send) {
  if (send.bounced_at) return { label: 'Rebotado', cls: 'em-pill-danger' };
  if (send.unsubscribed_at) return { label: 'Cancelado', cls: 'em-pill-neutral' };
  if (send.clicked_at) return { label: 'Clic', cls: 'em-pill' };
  if (send.opened_at) return { label: 'Abierto', cls: 'em-pill-warning' };
  if (send.sent_at) return { label: 'Enviado', cls: 'em-pill-neutral' };
  return { label: 'Pendiente', cls: 'em-pill-neutral' };
}

function nombreDe(send) {
  if (send.customer) {
    return `${send.customer.first_name || ''} ${send.customer.last_name || ''}`.trim() || send.customer.email;
  }
  if (send.guest_name) return send.guest_name;
  if (send.customer_id) return `Cliente #${send.customer_id}`;
  return '—';
}

function emailDe(send) {
  return (send.customer && send.customer.email) || send.guest_email || '—';
}

export default function RecipientsList({ sends }) {
  const items = sends || [];
  const preview = items.slice(0, 8);

  return (
    <div className="em-recipients-card">
      <div className="em-card-head">
        <div>
          <div className="em-card-head-title">Destinatarios</div>
          <div className="em-card-head-sub">Estado individual de cada envío</div>
        </div>
      </div>

      {items.length === 0 ? (
        <div className="em-clickmap-empty">Esta campaña todavía no tiene destinatarios registrados.</div>
      ) : (
        <>
          {preview.map((s) => {
            const estado = estadoDe(s);
            return (
              <div key={s.id} className="em-recipient-row">
                <div className="em-recipient-info">
                  <div className="em-recipient-name">{nombreDe(s)}</div>
                  <div className="em-recipient-email">{emailDe(s)}</div>
                </div>
                <span className={`em-pill ${estado.cls}`}>{estado.label}</span>
              </div>
            );
          })}
          {items.length > preview.length && (
            <div className="em-recipients-more">Y {items.length - preview.length} destinatarios más.</div>
          )}
        </>
      )}
    </div>
  );
}
