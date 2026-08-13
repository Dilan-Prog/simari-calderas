<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tag;
use App\Models\User;
use App\Services\DealForecastService;
use App\Services\DealReportService;
use App\Services\DealService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class DealController extends Controller
{
    public function __construct(
        private DealService $dealService,
        private DealReportService $dealReportService,
        private DealForecastService $dealForecastService
    ) {}

    /**
     * Fase 14: página única de Negocios con las 4 vistas (kanban/lista/
     * tabla/forecast). El toggle entre vistas y el cálculo de forecast
     * ponderado son 100% client-side (JS del módulo frontend, ver
     * resources/js/admin/deals-board.js) a partir de un único payload que
     * esta vista arma en PHP — no hay 3 rutas/queries divergentes como
     * antes de esta fase.
     *
     * Contrato de variables con la vista (resources/views/admin/deals/
     * index.blade.php, documentado también en su cabecera):
     *   $pipelines  Pipelines de canal "deals".
     *   $pipeline   Pipeline seleccionado (?pipeline_id=, o el default).
     *   $stages     Etapas de $pipeline, ordenadas.
     *   $deals      TODOS los deals de $pipeline (abiertos y cerrados,
     *               no paginado — el filtrado/paginado de las vistas
     *               lista/tabla es client-side sobre este payload), con
     *               stage/owner/customer/tags precargados para evitar N+1.
     *   $owners, $tags  Catálogos completos para los selects de filtro.
     *
     * DealForecastService::weightedPipeline()/repPerformance()/
     * forecastByCloseDate() quedan disponibles como servicio reutilizable
     * (consumido por tests y por cualquier futuro endpoint/reporte), pero
     * esta vista en particular recalcula el ponderado en JS sobre $deals
     * en vez de pedirlo aquí — evita mandar el mismo dato dos veces.
     */
    public function index(Request $request)
    {
        $pipelines = Pipeline::query()->deals()->orderBy('name')->get();

        if ($request->filled('pipeline_id')) {
            $pipeline = $pipelines->firstWhere('id', (int) $request->pipeline_id);
        } else {
            $pipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
        }

        $stages = collect();
        $deals = collect();

        if ($pipeline) {
            $stages = PipelineStage::where('pipeline_id', $pipeline->id)
                ->orderBy('order')
                ->get();

            $deals = Deal::with(['stage', 'owner', 'customer', 'tags'])
                ->where('pipeline_id', $pipeline->id)
                ->get();
        }

        $owners = User::orderBy('first_name')->orderBy('last_name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.deals.index', compact(
            'pipelines',
            'pipeline',
            'stages',
            'deals',
            'owners',
            'tags'
        ));
    }

    /**
     * Alias de compatibilidad para la ruta admin.deals.table (declarada
     * antes de esta Fase 14, ver routes/admin.php — no se toca esa línea
     * de ruta ya existente): ahora que index()/table()/board() se
     * fusionaron en una sola página con las 4 vistas conmutables, ambas
     * rutas sirven exactamente la misma respuesta.
     */
    public function table(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Query base compartida por la lista/tabla y por exportCsv() — evita
     * que ambas se desincronicen en qué filtros soportan.
     */
    private function filteredDealsQuery(Request $request)
    {
        $query = Deal::with(['stage', 'pipeline', 'owner', 'customer', 'tags'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('folio', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->pipeline_id);
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->filled('stage_id')) {
            $query->where('pipeline_stage_id', $request->stage_id);
        }

        if ($request->filled('tag_id')) {
            $tagId = $request->tag_id;
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        return $query;
    }

    public function create()
    {
        $pipelines = Pipeline::query()->deals()->with('stages')->orderBy('name')->get();
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.deals.create', compact('pipelines', 'customers', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pipeline_id'            => 'required|exists:pipelines,id',
            'pipeline_stage_id'      => 'nullable|exists:pipeline_stages,id',
            'name'                   => 'required|string|max:255',
            'amount'                 => 'nullable|numeric|min:0',
            'currency'               => 'nullable|in:MXN,USD',
            'expected_close_date'    => 'nullable|date',
            'owner_id'               => 'nullable|exists:users,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'company_snapshot'       => 'nullable|string|max:255',
            'contact_snapshot_name'  => 'nullable|string|max:180',
            'contact_snapshot_email' => 'nullable|email|max:255',
            'contact_snapshot_phone' => 'nullable|string|max:30',
            'source'                 => 'nullable|string|max:100',
            'notes'                  => 'nullable|string',
        ]);

        $deal = $this->dealService->create($data);

        return redirect()->route('admin.deals.show', $deal)
            ->with('success', "Negociación {$deal->folio} creada exitosamente.");
    }

    public function edit(Deal $deal)
    {
        $pipelines = Pipeline::query()->deals()->with('stages')->orderBy('name')->get();
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.deals.edit', compact('deal', 'pipelines', 'customers', 'users'));
    }

    public function update(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'pipeline_id'            => 'required|exists:pipelines,id',
            'name'                   => 'required|string|max:255',
            'amount'                 => 'nullable|numeric|min:0',
            'currency'               => 'nullable|in:MXN,USD',
            'expected_close_date'    => 'nullable|date',
            'owner_id'               => 'nullable|exists:users,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'company_snapshot'       => 'nullable|string|max:255',
            'contact_snapshot_name'  => 'nullable|string|max:180',
            'contact_snapshot_email' => 'nullable|email|max:255',
            'contact_snapshot_phone' => 'nullable|string|max:30',
            'source'                 => 'nullable|string|max:100',
            'status'                 => 'nullable|in:open,won,lost',
            'lost_reason'            => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $this->dealService->update($deal, $data);

        return redirect()->route('admin.deals.show', $deal)
            ->with('success', "Negociación {$deal->folio} actualizada exitosamente.");
    }

    /**
     * Sirve tanto la vista Blade normal como, si el cliente manda
     * Accept: application/json (fetch() del drawer lateral del rediseño
     * de Fase 14), el detalle completo en JSON — sin recargar la página.
     */
    public function show(Request $request, Deal $deal)
    {
        $deal->load([
            'stage',
            'pipeline',
            'owner',
            'customer',
            'contacts',
            'quotes',
            'tags',
            'stageHistory.fromStage',
            'stageHistory.toStage',
        ]);

        if ($request->wantsJson()) {
            return response()->json($deal);
        }

        return view('admin.deals.show', compact('deal'));
    }

    /**
     * Endpoint AJAX para mover el deal de etapa desde el kanban.
     */
    public function moveStage(Request $request, Deal $deal)
    {
        $request->validate([
            'to_stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $toStage = PipelineStage::findOrFail($request->to_stage_id);

        try {
            $deal = $this->dealService->moveStage($deal, $toStage, auth()->user());
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        return response()->json($deal->fresh(['stage', 'owner', 'customer']));
    }

    /**
     * Acciones masivas de la barra flotante de selección múltiple del
     * rediseño de Negocios: mover de etapa, reasignar dueño o eliminar
     * (soft delete) un lote de deals. Todo dentro de una sola transacción
     * — si algo falla a la mitad, ningún deal del lote queda a medias.
     */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array|min:1',
            'ids.*'        => 'integer|exists:deals,id',
            'action'       => ['required', Rule::in(['move_stage', 'assign_owner', 'delete'])],
            'stage_id'     => 'required_if:action,move_stage|nullable|exists:pipeline_stages,id',
            'owner_id'     => 'required_if:action,assign_owner|nullable|exists:users,id',
        ]);

        $affected = DB::transaction(function () use ($data) {
            $deals = Deal::whereIn('id', $data['ids'])->get();

            switch ($data['action']) {
                case 'move_stage':
                    $toStage = PipelineStage::findOrFail($data['stage_id']);

                    foreach ($deals as $deal) {
                        $this->dealService->moveStage($deal, $toStage, auth()->user());
                    }

                    break;

                case 'assign_owner':
                    Deal::whereIn('id', $data['ids'])->update(['owner_id' => $data['owner_id']]);

                    break;

                case 'delete':
                    Deal::whereIn('id', $data['ids'])->delete();

                    break;
            }

            return $deals->count();
        });

        return response()->json([
            'success' => true,
            'affected' => $affected,
        ]);
    }

    /**
     * Exporta a CSV el listado de Negocios respetando los mismos filtros
     * que la vista de lista/tabla (filteredDealsQuery, compartida con
     * index()). Reutiliza el export genérico FromCollection ya usado por
     * ReportBuilderService (Fase 4) en vez de escribir CSV a mano.
     */
    public function exportCsv(Request $request)
    {
        $deals = $this->filteredDealsQuery($request)->get();

        $rows = $deals->map(function (Deal $deal) {
            return [
                $deal->folio,
                $deal->name,
                $deal->pipeline?->name,
                $deal->stage?->name,
                $deal->amount,
                $deal->currency,
                $deal->expected_close_date?->format('Y-m-d'),
                $deal->owner?->full_name,
                $deal->customer
                    ? trim($deal->customer->first_name . ' ' . $deal->customer->last_name)
                    : $deal->company_snapshot,
                $deal->status,
                $deal->source,
            ];
        });

        $headings = [
            'Folio', 'Nombre', 'Pipeline', 'Etapa', 'Monto', 'Moneda',
            'Fecha de cierre esperada', 'Propietario', 'Cliente/Empresa',
            'Estado', 'Origen',
        ];

        $filename = 'negocios-' . now()->format('Y-m-d') . '.csv';

        return Excel::download(new ReportExport($rows, $headings), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();

        return redirect()->route('admin.deals.index')
            ->with('success', "Negociación {$deal->folio} eliminada.");
    }
}
