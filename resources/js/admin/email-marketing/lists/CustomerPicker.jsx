import React, { useEffect, useMemo, useState } from 'react';
import listsApi from '../api/listsApi';

/**
 * Buscador + checkboxes de clientes para listas estáticas. Mismo patrón UX
 * que resources/views/admin/email-lists/create.blade.php: precarga todos
 * los clientes activos una sola vez (sin AJAX de búsqueda) y filtra 100%
 * en cliente por nombre/correo/empresa.
 *
 * El toggle de un checkbox no decide por sí mismo si debe llamar a la API
 * o solo mutar estado local -- eso lo decide el padre (ListsTab) según si
 * la lista ya existe (edición: cada toggle pega a add-members/remove-member
 * de inmediato) o se está creando (los ids se acumulan y se mandan juntos
 * al guardar).
 */
export default function CustomerPicker({ selectedIds, onToggle, busy }) {
  const [customers, setCustomers] = useState(null); // null = cargando
  const [term, setTerm] = useState('');

  useEffect(() => {
    listsApi
      .customersPicker()
      .then((data) => setCustomers(Array.isArray(data) ? data : []))
      .catch(() => setCustomers([]));
  }, []);

  const selectedSet = useMemo(() => new Set(selectedIds || []), [selectedIds]);

  const filtered = useMemo(() => {
    if (!customers) return [];
    const q = term.trim().toLowerCase();
    if (!q) return customers;
    return customers.filter((c) => {
      const haystack = `${c.first_name || ''} ${c.last_name || ''} ${c.company || ''} ${c.email || ''}`.toLowerCase();
      return haystack.includes(q);
    });
  }, [customers, term]);

  return (
    <div className="em-picker">
      <div className="em-picker-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <circle cx="11" cy="11" r="7" />
          <path d="M21 21l-4.3-4.3" strokeLinecap="round" />
        </svg>
        <input
          type="text"
          placeholder="Buscar cliente por nombre, correo o empresa…"
          value={term}
          onChange={(e) => setTerm(e.target.value)}
        />
      </div>

      <div className="em-picker-count">
        <strong>{selectedSet.size}</strong> clientes seleccionados
      </div>

      <div className="em-picker-box">
        {customers === null ? (
          <div className="em-picker-empty">Cargando clientes…</div>
        ) : filtered.length === 0 ? (
          <div className="em-picker-empty">
            {customers.length === 0 ? 'No hay clientes activos disponibles.' : 'Sin resultados para tu búsqueda.'}
          </div>
        ) : (
          <table className="em-picker-table">
            <thead>
              <tr>
                <th className="em-picker-check-col"></th>
                <th>Nombre</th>
                <th>Empresa</th>
                <th>Correo</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((c) => (
                <tr key={c.id} onClick={() => !busy && onToggle(c.id)} className="em-picker-row">
                  <td className="em-picker-check-col">
                    <input type="checkbox" checked={selectedSet.has(c.id)} disabled={busy} onChange={() => onToggle(c.id)} onClick={(e) => e.stopPropagation()} />
                  </td>
                  <td>{`${c.first_name || ''} ${c.last_name || ''}`.trim() || '—'}</td>
                  <td>{c.company || '—'}</td>
                  <td className="em-picker-email">{c.email}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
      <div className="em-picker-hint">Solo aparecen clientes con correo registrado.</div>
    </div>
  );
}
