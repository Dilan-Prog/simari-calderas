// Fetch wrappers para EmailSequenceController (rutas admin.email-sequences.*).
//
// DISCREPANCIAS vs. contrato asumido (verificadas contra el controller real):
// 1. index() SÍ devuelve `{data: [...]}` (no un arreglo plano en el nivel
//    superior como decía el contrato) -- el controller hace
//    `response()->json(['data' => EmailSequence::...->get()])`. Se lee
//    `.data` aquí para exponer un arreglo plano a los consumidores.
// 2. store() exige `steps.*.order` (entero, requerido) además de
//    template_id/delay_days -- EmailSequenceController::store() valida
//    'steps.*.order' => 'required|integer|min:1'. Se calcula aquí como el
//    índice 1-based de cada paso.
// 3. addStep() también puede llevar `order` opcional (si no se manda, el
//    backend usa max(order)+1).

import { apiFetch, getRootDataset } from './httpClient';

function url(suffix = '') {
  const { sequencesUrl } = getRootDataset();
  return suffix ? `${sequencesUrl.replace(/\/$/, '')}/${suffix}` : sequencesUrl;
}

export async function list() {
  const res = await apiFetch(url());
  return res && res.data ? res.data : [];
}

export function show(id) {
  return apiFetch(url(id));
}

/**
 * payload: {name, owner_id?, is_active?, steps: [{template_id, delay_days}]}
 * Agrega `order` (1-based) a cada step antes de enviar.
 */
export function create(payload) {
  const steps = (payload.steps || []).map((step, index) => ({
    template_id: step.template_id,
    delay_days: step.delay_days,
    order: index + 1,
  }));

  return apiFetch(url(), { method: 'POST', body: { ...payload, steps } });
}

export function update(id, payload) {
  return apiFetch(url(id), { override: 'PUT', body: payload });
}

export function destroy(id) {
  return apiFetch(url(id), { override: 'DELETE' });
}

export function addStep(id, { templateId, delayDays, order }) {
  return apiFetch(url(`${id}/pasos`), {
    method: 'POST',
    body: { template_id: templateId, delay_days: delayDays, order },
  });
}

export function removeStep(stepId) {
  return apiFetch(url(`pasos/${stepId}`), { override: 'DELETE' });
}

export function enrollCustomer(id, customerId) {
  return apiFetch(url(`${id}/inscribir`), { method: 'POST', body: { customer_id: customerId } });
}

export default { list, show, create, update, destroy, addStep, removeStep, enrollCustomer };
