import React from 'react';

function pct(part, total) {
  if (!total) return '0%';
  return `${((part / total) * 100).toFixed(1)}%`;
}

/**
 * 5 tarjetas de KPI (mockup líneas 677-687) a partir de las métricas reales
 * que ya calcula EmailCampaignController::show() ({total, opened, clicked,
 * bounced, unsubscribed}).
 */
export default function CampaignKpis({ metrics }) {
  const m = metrics || { total: 0, opened: 0, clicked: 0, bounced: 0, unsubscribed: 0 };

  const cards = [
    { label: 'Enviados', value: m.total, sub: 'destinatarios', dot: '#9ca3af' },
    { label: 'Abiertos', value: m.opened, sub: pct(m.opened, m.total), dot: 'var(--secondary-color)' },
    { label: 'Clics', value: m.clicked, sub: pct(m.clicked, m.total), dot: '#0284c7' },
    { label: 'Rebotados', value: m.bounced, sub: pct(m.bounced, m.total), dot: '#dc2626' },
    { label: 'Cancelados', value: m.unsubscribed, sub: pct(m.unsubscribed, m.total), dot: '#9ca3af' },
  ];

  return (
    <div className="em-kpi-grid">
      {cards.map((c) => (
        <div key={c.label} className="em-kpi-card">
          <div className="em-kpi-label">
            <span className="em-kpi-dot" style={{ background: c.dot }} />
            {c.label}
          </div>
          <div className="em-kpi-value">{c.value}</div>
          <div className="em-kpi-sub">{c.sub}</div>
        </div>
      ))}
    </div>
  );
}
