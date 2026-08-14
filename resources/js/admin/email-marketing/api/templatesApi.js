// Fetch wrappers para EmailTemplateController (rutas admin.email-templates.*).
// Nombres de función EXACTOS -- resources/js/admin/email-marketing/builder/TemplateEditorShell.jsx
// (del agente hermano) los importa tal cual, no renombrar.

import { apiFetch, getRootDataset } from './httpClient';

function url(suffix = '') {
  const { templatesUrl } = getRootDataset();
  return suffix ? `${templatesUrl.replace(/\/$/, '')}/${suffix}` : templatesUrl;
}

export function list() {
  return apiFetch(url());
}

export function create(payload) {
  return apiFetch(url(), { method: 'POST', body: payload });
}

export function update(id, payload) {
  return apiFetch(url(id), { override: 'PUT', body: payload });
}

export function destroy(id) {
  return apiFetch(url(id), { override: 'DELETE' });
}

export function options() {
  return apiFetch(url('opciones'));
}

export function preview(id) {
  return apiFetch(url(`${id}/preview`));
}

export default { list, create, update, destroy, options, preview };
