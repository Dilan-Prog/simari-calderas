<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Webhook;

/**
 * Endpoint de solo-lectura de metadatos (id/name/url de los Webhooks
 * registrados) para poblar el selector de "Webhook guardado" del nodo
 * "Llamar webhook" en el inspector del canvas de Automatizaciones. Mismo
 * patrón que WorkflowWebhookCredentialController.
 */
class WorkflowRegisteredWebhookController extends Controller
{
    public function index()
    {
        return response()->json(Webhook::orderBy('name')->get(['id', 'name', 'url']));
    }
}
