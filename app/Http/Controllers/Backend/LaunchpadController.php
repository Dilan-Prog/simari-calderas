<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Erp\ErpNavigation;
use Illuminate\Http\Request;

/**
 * Launchpad (inicio por rol estilo SAP Fiori) y búsqueda global Ctrl+K.
 */
class LaunchpadController extends Controller
{
    public function index(ErpNavigation $nav)
    {
        $user = auth()->user();

        return view('admin.erp.launchpad', [
            'areas' => $nav->tilesFor($user),
            'kpis'  => $nav->kpis($user),
        ]);
    }

    /** JSON para la barra de comandos: módulos accesibles + documentos que coinciden por folio. */
    public function search(Request $request, ErpNavigation $nav)
    {
        $q = trim((string) $request->query('q', ''));

        return response()->json([
            'modules'   => $nav->searchModules(auth()->user(), $q),
            'documents' => mb_strlen($q) >= 2 ? $nav->searchDocuments(auth()->user(), $q) : [],
        ]);
    }
}
