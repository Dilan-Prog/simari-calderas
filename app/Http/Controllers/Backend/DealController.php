<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\UserColumnPreference;
use App\Services\DealReportService;
use App\Services\DealService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DealController extends Controller
{
    public function __construct(
        private DealService $dealService,
        private DealReportService $dealReportService
    ) {}

    /**
     * Vista de KANBAN — carga el pipeline activo (por defecto is_default,
     * o el primero si no hay default) con sus stages y los deals abiertos
     * de cada stage. Acepta ?pipeline_id= para cambiar de pipeline.
     */
    public function index(Request $request)
    {
        $pipelines = Pipeline::orderBy('name')->get();

        if ($request->filled('pipeline_id')) {
            $pipeline = $pipelines->firstWhere('id', (int) $request->pipeline_id);
        } else {
            $pipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
        }

        $stages = collect();
        $dealsByStage = collect();

        if ($pipeline) {
            $stages = PipelineStage::where('pipeline_id', $pipeline->id)
                ->orderBy('order')
                ->get();

            $dealsByStage = Deal::with(['stage', 'owner', 'customer'])
                ->where('pipeline_id', $pipeline->id)
                ->open()
                ->get()
                ->groupBy('pipeline_stage_id');
        }

        return view('admin.deals.board', compact('pipelines', 'pipeline', 'stages', 'dealsByStage'));
    }

    /**
     * Vista de TABLA — listado paginado con filtros server-side.
     */
    public function table(Request $request)
    {
        $query = Deal::with(['stage', 'pipeline', 'owner', 'customer'])->latest();

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

        $deals = $query->paginate(15)->withQueryString();

        $visibleColumns = UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'deals.index')
            ->value('columns');

        $pipelines = Pipeline::orderBy('name')->get();
        $owners = User::orderBy('name')->get();

        return view('admin.deals.index', compact('deals', 'visibleColumns', 'pipelines', 'owners'));
    }

    public function create()
    {
        $pipelines = Pipeline::with('stages')->orderBy('name')->get();
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
        $pipelines = Pipeline::with('stages')->orderBy('name')->get();
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

    public function show(Deal $deal)
    {
        $deal->load([
            'stage',
            'pipeline',
            'owner',
            'customer',
            'contacts',
            'quotes',
            'stageHistory.fromStage',
            'stageHistory.toStage',
        ]);

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

    public function destroy(Deal $deal)
    {
        $deal->delete();

        return redirect()->route('admin.deals.index')
            ->with('success', "Negociación {$deal->folio} eliminada.");
    }
}
