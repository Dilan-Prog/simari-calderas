import React, { useEffect, useRef, useState } from 'react';
import listsApi from '../api/listsApi';

// Verbatim del mockup (líneas 873-881) -- campo de Cliente disponible para
// filtrar listas activas.
export const CAMPOS = [
  { value: 'ciudad', label: 'Ciudad' },
  { value: 'estado', label: 'Estado' },
  { value: 'empresa', label: 'Empresa' },
  { value: 'giro', label: 'Giro industrial' },
  { value: 'total_compras', label: 'Total de compras' },
  { value: 'fecha_registro', label: 'Fecha de registro' },
];

// Operadores en español -- EmailListController::estimateRecipients() solo
// acepta exactamente estos 5 valores (igual|distinto|mayor|menor|contiene).
export const OPS = {
  igual: 'es igual a',
  distinto: 'es distinto de',
  mayor: 'es mayor que',
  menor: 'es menor que',
  contiene: 'contiene',
};

/**
 * Selector Campo/Operador/Valor + texto de regla en vivo + conteo estimado
 * de destinatarios, debounced contra EmailListController::estimateRecipients().
 */
export default function ActiveFilterBuilder({ condition, onChange, onEstimateChange }) {
  const [estimating, setEstimating] = useState(false);
  const timerRef = useRef(null);

  const field = condition.field || CAMPOS[0].value;
  const operator = condition.operator || 'igual';
  const value = condition.value || '';

  useEffect(() => {
    if (timerRef.current) clearTimeout(timerRef.current);

    timerRef.current = setTimeout(() => {
      setEstimating(true);
      listsApi
        .estimateRecipients({ field, operator, value })
        .then((res) => {
          if (onEstimateChange) onEstimateChange(res.count);
        })
        .catch(() => {
          if (onEstimateChange) onEstimateChange(null);
        })
        .finally(() => setEstimating(false));
    }, 450);

    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [field, operator, value]);

  const campoLabel = (CAMPOS.find((c) => c.value === field) || CAMPOS[0]).label;
  const reglaTexto = `${campoLabel} ${OPS[operator]} "${value || '…'}"`;

  return (
    <div className="em-filter-box">
      <div className="em-filter-grid">
        <div>
          <div className="em-field-label">Campo de Cliente</div>
          <select value={field} onChange={(e) => onChange({ ...condition, field: e.target.value })}>
            {CAMPOS.map((c) => (
              <option key={c.value} value={c.value}>
                {c.label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <div className="em-field-label">Operador</div>
          <select value={operator} onChange={(e) => onChange({ ...condition, operator: e.target.value })}>
            {Object.keys(OPS).map((op) => (
              <option key={op} value={op}>
                {OPS[op].charAt(0).toUpperCase() + OPS[op].slice(1)}
              </option>
            ))}
          </select>
        </div>
        <div>
          <div className="em-field-label">Valor</div>
          <input
            type="text"
            placeholder="Ej. Monterrey"
            value={value}
            onChange={(e) => onChange({ ...condition, value: e.target.value })}
          />
        </div>
      </div>
      <div className="em-filter-rule">
        Se incluirá a todo cliente donde <strong>{reglaTexto}</strong>
        {estimating ? ' · calculando…' : ''}
      </div>
      <div className="em-filter-note">Una sola condición por lista. Combinar varias con Y / O no está disponible por ahora.</div>
    </div>
  );
}
