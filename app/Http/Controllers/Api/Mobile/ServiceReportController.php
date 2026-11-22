<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Backend\ServiceReportController as BackendServiceReportController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ServiceReportDetailResource;
use App\Http\Resources\Mobile\ServiceReportResource;
use App\Models\ServiceReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reportes de servicio para la app móvil (solo lectura).
 *
 * Visibilidad: espejo exacto del panel (Backend\ServiceReportController::index
 * y ::show), que hoy NO acota por técnico — cualquier usuario con
 * service-reports,view ve todos los reportes. Si el panel llega a acotar,
 * replicarlo en visibleQuery().
 */
class ServiceReportController extends Controller
{
    /** GET /api/mobile/v1/reportes?search=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = $this->visibleQuery($request)
            ->with(['assignedUser', 'customer'])
            ->latest('service_date')
            ->orderByDesc('id');

        // Misma búsqueda que el panel: folio, nombre o empresa del cliente.
        if (! empty($data['search'])) {
            $search = $data['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('report_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_company', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'data' => ServiceReportResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /** GET /api/mobile/v1/reportes/{id} — 404 si no existe o no es visible. */
    public function show(Request $request, int $id): JsonResponse
    {
        $report = $this->visibleQuery($request)
            ->with(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'customer', 'images', 'service', 'rating'])
            ->findOrFail($id);

        return response()->json([
            'data' => (new ServiceReportDetailResource($report))->resolve($request),
        ]);
    }

    /**
     * GET /api/mobile/v1/reportes/{report}/pdf (URL firmada, sin token).
     * Reutiliza tal cual el PDF del panel (Backend\ServiceReportController::previewPdf,
     * misma vista admin.service-reports.pdf que downloadPdf) pero en modo inline
     * para que se pueda abrir en el navegador o un WebView.
     */
    public function pdf(ServiceReport $report)
    {
        return app(BackendServiceReportController::class)->previewPdf($report);
    }

    /** Reportes que el usuario puede ver (hoy: todos, igual que el panel). */
    protected function visibleQuery(Request $request): Builder
    {
        return ServiceReport::query();
    }
}
