<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Buscador de clientes para la app móvil (paso «Cliente» del asistente de
 * reportes y «Programar servicio»). Solo lectura, clientes activos.
 *
 * Permiso: lo necesita quien da de alta reportes o servicios, aunque su rol no
 * tenga el módulo de Clientes, así que basta con cualquiera de
 * service-reports,create · technical-services,create · clients,view
 * (en config/modules.php el módulo de clientes es `clients`).
 */
class CustomerController extends Controller
{
    /** Combinaciones módulo/acción que dan acceso al buscador. */
    private const ALLOWED = [
        ['service-reports', 'create'],
        ['technical-services', 'create'],
        ['clients', 'view'],
    ];

    /** GET /api/mobile/v1/clientes?search=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowed = collect(self::ALLOWED)->contains(fn ($p) => $user->hasPermission($p[0], $p[1]));
        if (! $allowed) {
            return response()->json(['message' => 'No tienes permiso para acceder a este módulo.'], 403);
        }

        $data = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Misma búsqueda que Backend\ServiceReportController::searchCustomers,
        // más email y teléfono.
        $query = Customer::query()
            ->with('defaultAddress')
            ->where('status', 'active')
            ->orderByRaw("COALESCE(NULLIF(company, ''), CONCAT(first_name, ' ', last_name))")
            ->orderBy('id');

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like)
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                  ->orWhere('company', 'like', $like)
                  ->orWhere('rfc', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like);
            });
        }

        $paginator = $query->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'data' => CustomerResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
