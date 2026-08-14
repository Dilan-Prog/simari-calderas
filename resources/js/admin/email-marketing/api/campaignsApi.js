// Fetch wrappers para EmailCampaignController (rutas admin.email-campaigns.*).

import { apiFetch, getRootDataset } from './httpClient';

function url(suffix = '') {
  const { campaignsUrl } = getRootDataset();
  return suffix ? `${campaignsUrl.replace(/\/$/, '')}/${suffix}` : campaignsUrl;
}

export function list() {
  return apiFetch(url());
}

export function show(id) {
  return apiFetch(url(id));
}

export function create(payload) {
  return apiFetch(url(), { method: 'POST', body: payload });
}

export function destroy(id) {
  return apiFetch(url(id), { override: 'DELETE' });
}

export function send(id) {
  return apiFetch(url(`${id}/enviar`), { method: 'POST' });
}

export function schedule(id, scheduledAt) {
  return apiFetch(url(`${id}/programar`), { method: 'POST', body: { scheduled_at: scheduledAt } });
}

export default { list, show, create, destroy, send, schedule };
