// Wrapper de fetch para el API de Email Marketing (Plantillas/Listas/Campañas/Secuencias).
// Mismo patrón que resources/js/admin/canvas/api/stepsApi.js: credentials
// same-origin, X-CSRF-TOKEN desde el meta tag, y X-HTTP-METHOD-OVERRIDE en
// vez de confiar en que el verbo HTTP real (PUT/DELETE) sobreviva proxies/WAF
// intermedios -- Laravel lee ese header igual que un verbo real
// (Request::getMethod()), así que funciona aunque las rutas reales usen
// Route::put()/Route::delete().

let _rootDataset = null;

/**
 * Lee una sola vez el dataset del div raíz #email-marketing-root
 * (data-templates-url, data-lists-url, data-campaigns-url, data-sequences-url, …).
 */
export function getRootDataset() {
  if (_rootDataset) return _rootDataset;

  const el = document.getElementById('email-marketing-root');
  _rootDataset = el ? el.dataset : {};

  return _rootDataset;
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

/**
 * apiFetch(url, { method, body, override })
 *
 * - Si `override` viene definido ('PUT'/'DELETE'), el fetch real se manda
 *   como POST con el header X-HTTP-METHOD-OVERRIDE: <override>.
 * - Si no, se usa `method` tal cual (default 'GET').
 * - 204 -> null. !ok -> throw Error con .status y .errors (forma 422 de
 *   Laravel: {message, errors: {campo: [mensajes]}}) para que el caller
 *   pueda mostrar errores de validación por campo.
 */
export async function apiFetch(url, { method = 'GET', body, override } = {}) {
  const headers = {
    'X-CSRF-TOKEN': getCsrfToken(),
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (override) {
    headers['X-HTTP-METHOD-OVERRIDE'] = override;
  }

  const res = await fetch(url, {
    method: override ? 'POST' : method,
    credentials: 'same-origin',
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) {
    return null;
  }

  if (!res.ok) {
    let payload = null;
    try {
      payload = await res.json();
    } catch (e) {
      // el body no era JSON (p. ej. 500 con página de error HTML) — se
      // deja payload en null, el Error de abajo cae al fallback genérico.
    }

    const message = (payload && payload.message) || `Error ${res.status}: ${res.statusText}`;
    const error = new Error(message);
    error.status = res.status;
    error.errors = payload && payload.errors ? payload.errors : null;
    throw error;
  }

  try {
    return await res.json();
  } catch (e) {
    return null;
  }
}

export default apiFetch;
