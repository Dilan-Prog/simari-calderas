<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\TechnicalService;
use Illuminate\Http\Request;

/**
 * Servicios tecnicos visibles para el cliente del portal.
 *
 * Reglas: solo servicios propios (customer_id), nunca borradores internos (si las
 * solicitudes que el propio cliente envio desde el portal, que se muestran como
 * "Solicitud enviada")
 * y nunca datos monetarios (total_cost, invoice_id, from_quote_id): las
 * columnas se seleccionan de forma explicita para que ni siquiera lleguen
 * al modelo.
 */
class TechnicalServiceController extends Controller
{
    /** Estados visibles para el cliente (sin 'draft'). */
    public const VISIBLE_STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    /** Columnas seguras (sin total_cost, invoice_id ni from_quote_id). */
    private const SAFE_COLUMNS = [
        'id', 'service_number', 'customer_id', 'service_type_id',
        'service_date', 'service_time', 'estimated_duration', 'priority',
        'address', 'reference', 'description', 'status',
        // Para detectar solicitudes del propio cliente (ver scopeCustomerRequests).
        'created_by_user_id', 'requested_at',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status');
        if (! in_array($status, [...self::VISIBLE_STATUSES, 'draft'], true)) {
            $status = null;
        }

        $services = TechnicalService::query()
            ->select(self::SAFE_COLUMNS)
            ->where('customer_id', auth('customer')->id())
            ->where(fn ($q) => $q->whereIn('status', self::VISIBLE_STATUSES)->orWhere(fn ($r) => $r->customerRequests()))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['serviceType:id,name', 'assignedTechnicians'])
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('customers.technical-services.index', [
            'services' => $services,
            'status'   => $status,
        ]);
    }

    public function show(int $service)
    {
        $service = TechnicalService::query()
            ->select(self::SAFE_COLUMNS)
            ->where('customer_id', auth('customer')->id())
            ->where(fn ($q) => $q->whereIn('status', self::VISIBLE_STATUSES)->orWhere(fn ($r) => $r->customerRequests()))
            ->with(['assignedTechnicians', 'plannedMaterials', 'serviceType:id,name'])
            ->findOrFail($service);

        return view('customers.technical-services.show', compact('service'));
    }
}
