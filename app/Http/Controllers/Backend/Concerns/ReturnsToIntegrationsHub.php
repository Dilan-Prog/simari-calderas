<?php

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Redirect "de regreso" para las acciones de la pantalla Integraciones.
 *
 * La pantalla es un hub con un menú lateral (panel) y pestañas internas (tab).
 * Un `back()` normal pierde el estado porque los navegadores no mandan el
 * fragmento (#) en el Referer y, si JS no corrió, tampoco el panel activo. Aquí
 * se redirige SIEMPRE a admin.integrations.index con ?panel=...&tab=..., tomando
 * los valores de los campos ocultos `_panel` / `_tab` que el JS de la vista
 * agrega al enviar cualquier formulario, o -- si no llegaron -- del panel por
 * defecto de la acción (así funciona incluso sin JavaScript).
 */
trait ReturnsToIntegrationsHub
{
    /** Paneles válidos del hub (data-panel de la vista). */
    protected function integrationsHubPanels(): array
    {
        return ['smtp', 'whatsapp-api', 'webhooks', 'mercadopago', 'api-clients'];
    }

    protected function backToIntegrations(Request $request, string $defaultPanel, ?string $defaultTab = null): RedirectResponse
    {
        $requestedPanel = $request->input('_panel');
        $panel = is_string($requestedPanel) && in_array($requestedPanel, $this->integrationsHubPanels(), true)
            ? $requestedPanel
            : $defaultPanel;

        $requestedTab = $request->input('_tab');
        $tab = is_string($requestedTab) && preg_match('/^[a-z0-9_-]{1,40}$/', $requestedTab)
            ? $requestedTab
            : ($panel === $defaultPanel ? $defaultTab : null);

        return redirect()->route('admin.integrations.index', array_filter([
            'panel' => $panel,
            'tab'   => $tab,
        ]));
    }
}
