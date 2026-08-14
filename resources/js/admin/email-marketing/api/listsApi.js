// Fetch wrappers para EmailListController (rutas admin.email-lists.*).
//
// DISCREPANCIA vs. contrato asumido: EmailListController::store()/update()
// validan filter_definition como `nullable|json` -- esa regla exige un
// STRING JSON-válido, no un array anidado. Cuando el body completo viaja
// como JSON (Content-Type: application/json), Laravel ya decodifica el
// body entero antes de validar, así que un array anidado llegaría como
// array PHP (no string) y la regla `json` fallaría. Por eso aquí se hace
// JSON.stringify() del array de condiciones ANTES de meterlo en el
// payload -- apiFetch hace JSON.stringify() del payload completo después,
// así que filter_definition viaja como un string JSON escapado dentro del
// JSON externo, tal como el controller espera (json_decode() explícito).

import { apiFetch, getRootDataset } from './httpClient';

function url(suffix = '') {
  const { listsUrl } = getRootDataset();
  return suffix ? `${listsUrl.replace(/\/$/, '')}/${suffix}` : listsUrl;
}

export function list() {
  return apiFetch(url());
}

export function show(id) {
  return apiFetch(url(id));
}

export function options() {
  return apiFetch(url('opciones'));
}

export function customersPicker() {
  return apiFetch(url('clientes-disponibles'));
}

/**
 * condition: {field, operator, value} con operator en español
 * (igual|distinto|mayor|menor|contiene) -- el backend traduce a los
 * operadores en inglés que usa WorkflowConditionEvaluator.
 */
export function estimateRecipients(condition) {
  return apiFetch(url('estimar-destinatarios'), { method: 'POST', body: condition });
}

/**
 * payload: {name, type: 'static'|'active', condition?: {field,operator,value}, customer_ids?: number[]}
 * Envuelve `condition` en un arreglo de 1 elemento y lo serializa a string
 * JSON antes de enviarlo (ver nota arriba).
 */
function buildBody({ name, type, condition, customerIds }) {
  const body = { name, type };

  if (type === 'active' && condition) {
    body.filter_definition = JSON.stringify([condition]);
  }

  if (type === 'static' && customerIds) {
    body.customer_ids = customerIds;
  }

  return body;
}

export function create(payload) {
  return apiFetch(url(), { method: 'POST', body: buildBody(payload) });
}

export function update(id, payload) {
  return apiFetch(url(id), { override: 'PUT', body: buildBody(payload) });
}

export function destroy(id) {
  return apiFetch(url(id), { override: 'DELETE' });
}

export function addMembers(id, customerIds) {
  return apiFetch(url(`${id}/miembros`), { method: 'POST', body: { customer_ids: customerIds } });
}

export function removeMember(id, customerId) {
  return apiFetch(url(`${id}/miembros/${customerId}`), { override: 'DELETE' });
}

export default { list, show, options, customersPicker, estimateRecipients, create, update, destroy, addMembers, removeMember };
