<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\ServiceReport;
use App\Models\TechnicalService;
use Illuminate\Http\Request;

/**
 * Historial de servicios realizados (status completed) del cliente autenticado.
 * Sin datos monetarios.
 */
class ServiceHistoryController extends Controller
{
    /** Estados de reporte que el cliente puede consultar como resultado del servicio. */
    private const REPORT_STATUSES = ['completed', 'signed'];

    public function index(Request $request)
    {
        $customerId = auth('customer')->id();

        $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'q'    => ['nullable', 'string', 'max:100'],
        ]);

        $year   = $request->filled('year') ? (int) $request->query('year') : null;
        $search = trim((string) $request->query('q', ''));

        $base = TechnicalService::query()
            ->where('customer_id', $customerId)
            ->where('status', 'completed');

        // Anios disponibles para el filtro (solo de sus servicios completados).
        $years = (clone $base)
            ->whereNotNull('service_date')
            ->selectRaw('YEAR(service_date) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();

        $services = (clone $base)
            ->select(['id', 'service_number', 'customer_id', 'service_type_id', 'service_date', 'service_time', 'status'])
            ->when($year, fn ($q) => $q->whereYear('service_date', $year))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_') . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('service_number', 'like', $like)
                      ->orWhereHas('serviceType', fn ($t) => $t->where('name', 'like', $like));
                });
            })
            ->with(['serviceType:id,name', 'assignedTechnicians'])
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Reportes de servicio de esta pagina (propios y ya cerrados), agrupados por servicio.
        $reports = ServiceReport::query()
            ->select(['id', 'report_number', 'service_id', 'customer_id', 'status'])
            ->where('customer_id', $customerId)
            ->whereIn('status', self::REPORT_STATUSES)
            ->whereIn('service_id', $services->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('service_id');

        return view('customers.history', [
            'services' => $services,
            'reports'  => $reports,
            'years'    => $years,
            'year'     => $year,
            'search'   => $search,
        ]);
    }
}
