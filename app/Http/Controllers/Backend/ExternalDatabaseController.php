<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\ExternalDatabaseConnectionService;

/**
 * Endpoints de solo-lectura de metadatos (nombres de credenciales tipo
 * mysql, sus tablas, sus columnas) para poblar el formulario estructurado
 * del nodo "Base de datos externa" en el inspector del canvas. Nunca
 * serializa el Credential completo ni su payload — solo id/name, o listas
 * planas de strings.
 */
class ExternalDatabaseController extends Controller
{
    public function index()
    {
        $credentials = Credential::where('type', 'mysql')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($credentials);
    }

    public function tables(Credential $credential, ExternalDatabaseConnectionService $service)
    {
        abort_unless($credential->type === 'mysql', 422, 'La credencial no es de tipo mysql.');

        return response()->json($service->listTables($credential));
    }

    public function columns(Credential $credential, string $table, ExternalDatabaseConnectionService $service)
    {
        abort_unless($credential->type === 'mysql', 422, 'La credencial no es de tipo mysql.');

        return response()->json($service->listColumns($credential, $table));
    }
}
