<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Credential;

/**
 * Endpoint de solo-lectura de metadatos (nombre de credenciales tipo
 * webhook) para poblar el selector de credencial del nodo "Llamar webhook"
 * en el inspector del canvas de Automatizaciones. Nunca serializa el
 * Credential completo ni su payload — solo id/name.
 */
class WorkflowWebhookCredentialController extends Controller
{
    public function index()
    {
        $credentials = Credential::where('type', 'webhook')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($credentials);
    }
}
