// Wrapper de fetch para el API de canvas de workflows.
// Todas las funciones usan credentials same-origin, header X-CSRF-TOKEN y Content-Type application/json.

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

function baseHeaders() {
  return {
    'X-CSRF-TOKEN': getCsrfToken(),
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
}

async function handleResponse(response) {
  if (response.ok) {
    if (response.status === 204) return null;
    try {
      return await response.json();
    } catch (e) {
      return null;
    }
  }

  if (response.status === 419) {
    throw new Error('Sesión expirada, recarga la página');
  }

  if (response.status === 422) {
    let body = null;
    try {
      body = await response.json();
    } catch (e) {
      // no-op, body sigue null
    }
    if (body) {
      if (body.message) {
        throw new Error(body.message);
      }
      if (body.errors) {
        const messages = Object.values(body.errors).flat().join(' ');
        throw new Error(messages || 'Error de validación');
      }
    }
    throw new Error('Error de validación');
  }

  throw new Error(`Error ${response.status}: ${response.statusText}`);
}

export async function getCanvas(url) {
  const response = await fetch(url, {
    method: 'GET',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function createStep(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function updateStep(baseUrl, id, payload) {
  const url = `${baseUrl.replace(/\/$/, '')}/${id}`;
  const response = await fetch(url, {
    method: 'PUT',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function deleteStep(baseUrl, id) {
  const url = `${baseUrl.replace(/\/$/, '')}/${id}`;
  const response = await fetch(url, {
    method: 'DELETE',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function saveLayout(layoutUrl, positions) {
  const response = await fetch(layoutUrl, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify({ positions }),
  });
  return handleResponse(response);
}

export async function runTest(testUrl, sampleData) {
  const response = await fetch(testUrl, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify({ sample_data: sampleData }),
  });
  return handleResponse(response);
}

// ── Variables ──────────────────────────────────────────────────

export async function getVariables(url) {
  const response = await fetch(url, {
    method: 'GET',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function createVariable(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function deleteVariable(baseUrl, id) {
  const url = `${baseUrl.replace(/\/$/, '')}/${id}`;
  const response = await fetch(url, {
    method: 'DELETE',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

// ── Notas (StickyNote) ────────────────────────────────────────

export async function getNotes(url) {
  const response = await fetch(url, {
    method: 'GET',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function createNote(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function updateNote(baseUrl, id, payload) {
  const url = `${baseUrl.replace(/\/$/, '')}/${id}`;
  const response = await fetch(url, {
    method: 'PUT',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function deleteNote(baseUrl, id) {
  const url = `${baseUrl.replace(/\/$/, '')}/${id}`;
  const response = await fetch(url, {
    method: 'DELETE',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

// ── Workflow: rename/trigger, toggle, undo/redo ─────────────────

export async function updateWorkflow(updateUrl, payload) {
  const response = await fetch(updateUrl, {
    method: 'PUT',
    headers: baseHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  return handleResponse(response);
}

export async function toggleWorkflow(toggleUrl) {
  const response = await fetch(toggleUrl, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function undoWorkflow(undoUrl) {
  const response = await fetch(undoUrl, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}

export async function redoWorkflow(redoUrl) {
  const response = await fetch(redoUrl, {
    method: 'POST',
    headers: baseHeaders(),
    credentials: 'same-origin',
  });
  return handleResponse(response);
}
