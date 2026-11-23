<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Backend\ServiceReportController as BackendServiceReportController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ServiceReportDetailResource;
use App\Http\Resources\Mobile\ServiceReportResource;
use App\Models\ServiceReport;
use App\Services\MobileServiceReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reportes de servicio para la app móvil: listado, detalle, PDF y alta (store).
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

    /** Relaciones que necesita ServiceReportDetailResource (mismas que show()). */
    private const DETAIL_RELATIONS = ['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'customer', 'images', 'service', 'rating'];

    /**
     * POST /api/mobile/v1/reportes — alta de un reporte completo (y normalmente
     * firmado) capturado en el asistente de la app. Multipart; ver
     * MobileServiceReportService para el mapeo al modelo del panel.
     *
     * Idempotente por el header Idempotency-Key (o el campo idempotency_key):
     *  - 201 {data, warnings?}  reporte creado.
     *  - 200 {data} + header Idempotent-Replayed: true  la key ya existía
     *    (reintento de la bandeja de salida): no se crea ni se sube nada.
     *  - 422 validación (incluida la key faltante), 403 sin service-reports,create.
     *
     * `warnings`: avisos no fatales (fotos que no se pudieron procesar, firma
     * ilegible, cliente tomado del servicio). El reporte sí se guardó.
     */
    public function store(Request $request, MobileServiceReportService $mobile): JsonResponse
    {
        $user = $request->user();
        $key  = $mobile->idempotencyKey($request);

        if ($existing = $mobile->findByKey($key, $user)) {
            return $this->replayed($request, $existing);
        }

        $validated = $mobile->validate($request);

        [$report, $warnings, $replayed] = $mobile->create($validated, $request, $user, $key);

        if ($replayed) {
            return $this->replayed($request, $report);
        }

        $body = ['data' => (new ServiceReportDetailResource($report->load(self::DETAIL_RELATIONS)))->resolve($request)];
        if ($warnings) {
            $body['warnings'] = array_values($warnings);
        }

        return response()->json($body, 201);
    }

    private function replayed(Request $request, ServiceReport $report): JsonResponse
    {
        return response()
            ->json(['data' => (new ServiceReportDetailResource($report->load(self::DETAIL_RELATIONS)))->resolve($request)], 200)
            ->header('Idempotent-Replayed', 'true');
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
