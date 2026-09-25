<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 5 del plan de integración N8N. Abilities: `customers:read` (index,
 * show) / `customers:write` (store, update) -- config/api_abilities.php.
 *
 * Reglas de validación calcadas de
 * app/Http/Controllers/Backend/ClientManageController.php (store()/update()),
 * el único lugar donde hoy se valida un Customer -- no hay FormRequest
 * dedicado que extraer. Único cambio real: esta API recibe first_name/
 * last_name como campos discretos en vez del "full_name" de un solo input
 * que ese formulario de panel parte a mano (explode(' ', ..., 2)); no tiene
 * sentido forzar ese mismo atajo de UI a un consumidor JSON como N8N.
 *
 * IMPORTANTE: nunca asignar password_hash aquí a partir de un valor que
 * venga del request -- el alta/():cambio de contraseña del portal es un
 * flujo aparte (ClientManageController::grantAccess(), fuera del alcance de
 * esta fase) y no se expone vía esta API.
 */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->string('email') . '%');
        }

        if ($request->filled('rfc')) {
            $query->where('rfc', 'like', '%' . $request->string('rfc') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 25;

        $customers = $query->orderBy('id')->paginate($perPage);

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => new CustomerResource($customer)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $customer = new Customer();
        $customer->first_name = $data['first_name'];
        $customer->last_name = $data['last_name'] ?? '';
        $customer->email = isset($data['email']) ? strtolower($data['email']) : null;
        $customer->phone = $data['phone'];
        $customer->company = $data['company'];
        $customer->rfc = $data['rfc'] ?? null;
        $customer->tipo_persona = $data['tipo_persona'] ?? null;
        $customer->notes = $data['notes'] ?? null;
        $customer->document_type = $data['document_type'];
        $customer->source = $data['source'];
        $customer->status = $data['status'] ?? 'active';
        // Sin acceso al portal hasta que se otorgue explícitamente (mismo
        // criterio que ClientManageController::store()).
        $customer->password_hash = null;
        $customer->portal_access = false;
        $customer->save();

        return response()->json(['data' => new CustomerResource($customer)], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $data = $this->validated($request, $customer->id);

        $customer->first_name = $data['first_name'];
        $customer->last_name = $data['last_name'] ?? '';
        $customer->email = isset($data['email']) ? strtolower($data['email']) : null;
        $customer->phone = $data['phone'];
        $customer->company = $data['company'];
        $customer->rfc = $data['rfc'] ?? null;
        $customer->tipo_persona = $data['tipo_persona'] ?? null;
        $customer->notes = $data['notes'] ?? null;
        $customer->document_type = $data['document_type'];
        $customer->source = $data['source'];
        $customer->status = $data['status'] ?? $customer->status;
        $customer->save();

        return response()->json(['data' => new CustomerResource($customer)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $uniqueEmail = 'unique:customers,email' . ($ignoreId ? ",{$ignoreId}" : '');

        return $request->validate([
            'first_name'    => ['required', 'string', 'max:150', 'regex:/^[a-zA-ZÀ-ÿ\s]+$/'],
            'last_name'     => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-ZÀ-ÿ\s]+$/'],
            // NOT NULL sin default en la tabla `customers` (confirmado en el
            // esquema real) -- a diferencia de ClientManageController (que
            // los valida como nullable y confía en que el formulario del
            // panel siempre los manda), aquí se marcan required para que un
            // caller de la API reciba un 422 claro en vez de un 500 por
            // violar la constraint de la BD.
            'company'       => ['required', 'string', 'max:150', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
            'email'         => ['nullable', 'email', 'max:150', $uniqueEmail],
            'phone'         => ['required', 'string', 'max:30'],
            'rfc'           => ['nullable', 'string', 'max:20'],
            'tipo_persona'  => ['nullable', 'in:fisica,moral'],
            'document_type' => ['required', 'in:ine,pasaporte,curp,cfdi'],
            'source'        => ['required', 'in:web,whatsapp,admin,campaña,referido'],
            'status'        => ['nullable', 'in:active,inactive,suspended'],
            'notes'         => ['nullable', 'string', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
        ]);
    }
}
