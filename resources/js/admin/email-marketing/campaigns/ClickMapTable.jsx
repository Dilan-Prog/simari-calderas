import React from 'react';

/**
 * Mapa de clics agrupado por URL (mockup líneas 690-716).
 *
 * NOTA: el backend real solo entrega {url, count} por URL -- no una
 * "etiqueta" descriptiva separada como el mockup de ejemplo. Se muestra la
 * URL como título y se omite la segunda línea decorativa.
 */
export default function ClickMapTable({ clickMap }) {
  const items = clickMap || [];
  const total = items.reduce((sum, c) => sum + (c.count || 0), 0);

  return (
    <div className="em-clickmap-card">
      <div className="em-card-head">
        <div>
          <div className="em-card-head-title">Mapa de clics</div>
          <div className="em-card-head-sub">Clics agrupados por URL dentro del correo</div>
        </div>
        <div className="em-card-head-count">{total} clics</div>
      </div>

      {items.length === 0 ? (
        <div className="em-clickmap-empty">Todavía no hay clics registrados para esta campaña.</div>
      ) : (
        <>
          <div className="em-clickmap-row em-clickmap-row-head">
            <div>URL destino</div>
            <div className="em-align-right">Clics</div>
            <div className="em-align-right">% del total</div>
          </div>
          {items.map((cm) => {
            const p = total ? Math.round((cm.count / total) * 100) : 0;
            return (
              <div key={cm.url} className="em-clickmap-row">
                <div className="em-clickmap-url" title={cm.url}>
                  {cm.url}
                </div>
                <div className="em-align-right em-clickmap-count">{cm.count}</div>
                <div className="em-clickmap-bar-cell">
                  <div className="em-clickmap-bar-track">
                    <div className="em-clickmap-bar-fill" style={{ width: `${p}%` }} />
                  </div>
                  <span>{p}%</span>
                </div>
              </div>
            );
          })}
        </>
      )}
    </div>
  );
}
