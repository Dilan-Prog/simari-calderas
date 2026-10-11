<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequirement;
use App\Models\Products;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\TechnicalService;
use App\Models\Warehouse;
use App\Services\MrpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MrpController extends Controller
{
    public function __construct(private MrpService $service)
    {
    }

    private function projectsAvailable(): bool
    {
        return class_exists(Project::class) && Schema::hasTable('projects');
    }

    public function index(Request $request)
    {
        $filters = [
            'q'          => trim((string) $request->get('q', '')),
            'status'     => (string) $request->get('status', ''),
            'origin'     => (string) $request->get('origin', ''),
            'project_id' => (string) $request->get('project_id', ''),
            'from'       => (string) $request->get('from', ''),
            'to'         => (string) $request->get('to', ''),
            'shortfall'  => (string) $request->get('shortfall', ''),
        ];

        $reqs = MaterialRequirement::with('customer:id,first_name,last_name')
            ->withCount('lines')
            ->withCount(['lines as short_lines_count' => fn ($q) => $q->where('shortfall', '>', 0)])
            ->when($filters['q'] !== '', fn ($q) => $q->where('number', 'like', '%' . $filters['q'] . '%'))
            ->when(in_array($filters['status'], MaterialRequirement::STATUSES, true), fn ($q) => $q->where('status', $filters['status']))
            ->when(in_array($filters['origin'], MaterialRequirement::ORIGINS, true), fn ($q) => $q->where('origin_type', $filters['origin']))
            ->when($filters['project_id'] !== '', fn ($q) => $q->where('project_id', (int) $filters['project_id']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when($filters['shortfall'] === '1', fn ($q) => $q->whereHas('lines', fn ($l) => $l->where('shortfall', '>', 0)))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $projects = $this->projectsAvailable()
            ? Project::orderByDesc('id')->limit(300)->get(['id', 'project_number', 'name'])
            : collect();

        $chips = [];
        $labels = ['q' => 'Folio', 'status' => 'Estado', 'origin' => 'Origen', 'project_id' => 'Proyecto', 'from' => 'Desde', 'to' => 'Hasta', 'shortfall' => 'Con faltante'];
        foreach ($filters as $k => $v) {
            if ($v === '') {
                continue;
            }
            $shown = match ($k) {
                'status'     => MaterialRequirement::statusLabel($v),
                'origin'     => (new MaterialRequirement(['origin_type' => $v]))->originLabel(),
                'project_id' => optional($projects->firstWhere('id', (int) $v))->project_number ?? ('#' . $v),
                'shortfall'  => 'Sí',
                default      => $v,
            };
            $chips[] = ['key' => $k, 'label' => $labels[$k], 'value' => $shown, 'url' => $request->fullUrlWithQuery([$k => null, 'page' => null])];
        }

        return view('admin.mrp.index', compact('reqs', 'filters', 'projects', 'chips'));
    }

    public function create(Request $request)
    {
        $origin = $request->get('origin', 'sales_order');
        $originId = (int) $request->get('origin_id', 0);

        $orders = SalesOrder::with('customer:id,first_name,last_name')->where('status', '!=', 'cancelado')
            ->orderByDesc('id')->limit(200)->get(['id', 'order_number', 'customer_id', 'status']);
        $services = TechnicalService::where('status', '!=', 'cancelled')->orderByDesc('id')->limit(200)
            ->get(['id', 'service_number', 'status']);
        $projects = $this->projectsAvailable()
            ? Project::orderByDesc('id')->limit(200)->get(['id', 'project_number', 'name'])
            : collect();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.mrp.create', compact('origin', 'originId', 'orders', 'services', 'projects', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'origin'            => 'required|in:sales_order,project,technical_service,manual',
            'origin_id'         => 'nullable|integer',
            'warehouse_id'      => 'nullable|integer|exists:warehouses,id',
            'needed_by_date'    => 'nullable|date',
            'include_chemicals' => 'nullable|boolean',
            'notes'             => 'nullable|string|max:5000',
            'lines'             => 'nullable|array',
            'lines.*.product_id' => 'nullable|integer|exists:products,id',
            'lines.*.quantity'  => 'nullable|numeric|gt:0|max:99999999',
            'lines.*.unit'      => 'nullable|string|max:30',
        ]);

        $opts = [
            'warehouse_id'      => $data['warehouse_id'] ?? null,
            'needed_by_date'    => $data['needed_by_date'] ?? null,
            'include_chemicals' => $request->boolean('include_chemicals'),
            'notes'             => $data['notes'] ?? null,
            'user_id'           => auth()->id(),
        ];

        try {
            switch ($data['origin']) {
                case 'sales_order':
                    $req = $this->service->buildForSalesOrder(SalesOrder::findOrFail((int) ($data['origin_id'] ?? 0)), $opts);
                    break;
                case 'technical_service':
                    $req = $this->service->buildForTechnicalService(TechnicalService::findOrFail((int) ($data['origin_id'] ?? 0)), $opts);
                    break;
                case 'project':
                    abort_unless($this->projectsAvailable(), 404);
                    $req = $this->service->buildForProject(Project::findOrFail((int) ($data['origin_id'] ?? 0)), $opts);
                    break;
                default:
                    $lines = array_values(array_filter($data['lines'] ?? [], fn ($l) => !empty($l['product_id']) && !empty($l['quantity'])));
                    if (!$lines) {
                        throw ValidationException::withMessages(['lines' => 'Agrega al menos un material.']);
                    }
                    $req = $this->service->buildManual($opts, $lines);
            }
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()->route('admin.mrp.show', $req)->with('success', "Requerimiento {$req->number} calculado.");
    }

    /** Atajo desde la ficha de un pedido: calcula (o recalcula) y abre el requerimiento. */
    public function fromSalesOrder(SalesOrder $salesOrder)
    {
        $req = $this->service->buildForSalesOrder($salesOrder, ['user_id' => auth()->id()]);

        return redirect()->route('admin.mrp.show', $req)->with('success', "Requerimiento {$req->number} calculado.");
    }

    public function show(MaterialRequirement $requirement)
    {
        $requirement->load(['lines.product', 'lines.supplier', 'lines.purchaseOrder', 'customer']);
        $groups = $this->service->suggestPurchases($requirement);
        $suppliers = collect($groups)->contains(fn ($g) => !$g['has_supplier'])
            ? Supplier::orderBy('company_name')->get(['id', 'company_name', 'contact_name'])
            : collect();

        $step = $this->service->processStep($requirement);
        $steps = MrpService::PROCESS_STEPS;
        $currentIdx = $step === 'fin' ? count($steps) : ($step === null ? -1 : array_search($step, array_column($steps, 'key'), true));

        $totals = [
            'lines'     => $requirement->lines->count(),
            'short'     => $requirement->lines->filter(fn ($l) => $l->shortfall > 0.00005)->count(),
            'reserved'  => $requirement->lines->filter(fn ($l) => $l->own_reserved > 0.00005)->count(),
        ];
        $warehouse = $requirement->warehouse_id ? Warehouse::find($requirement->warehouse_id) : null;

        return view('admin.mrp.show', compact('requirement', 'groups', 'suppliers', 'steps', 'currentIdx', 'totals', 'warehouse'));
    }

    public function recalculate(MaterialRequirement $requirement)
    {
        $this->service->recalculate($requirement);

        return back()->with('success', 'Requerimiento recalculado.');
    }

    public function reserve(MaterialRequirement $requirement)
    {
        $this->service->reserve($requirement);

        return back()->with('success', 'Existencias reservadas para este requerimiento.');
    }

    public function release(MaterialRequirement $requirement)
    {
        $this->service->release($requirement);

        return back()->with('success', 'Reservas liberadas.');
    }

    public function cancel(MaterialRequirement $requirement)
    {
        $this->service->cancel($requirement);

        return back()->with('success', 'Requerimiento cancelado y reservas liberadas.');
    }

    public function generatePurchaseOrders(Request $request, MaterialRequirement $requirement)
    {
        $data = $request->validate([
            'selection'   => 'nullable|array',
            'selection.*' => 'integer',
            'suppliers'   => 'nullable|array',
            'suppliers.*' => 'nullable|integer|exists:suppliers,id',
        ]);

        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('purchase-orders', 'create'), 403, 'No tienes permiso para crear órdenes de compra.');

        $selection = $data['selection'] ?? [];
        if (!$selection) {
            throw ValidationException::withMessages(['selection' => 'Selecciona al menos una línea para comprar.']);
        }

        $pos = $this->service->generatePurchaseOrders($requirement, $selection, array_filter($data['suppliers'] ?? []));

        return redirect()->route('admin.mrp.show', $requirement)
            ->with('success', 'Se generó ' . ($pos->count() === 1 ? '1 orden de compra' : $pos->count() . ' órdenes de compra') . ': ' . $pos->pluck('po_number')->implode(', ') . '.');
    }

    /** Acción en lote desde el listado: genera OC con los faltantes que ya tienen proveedor de cada requerimiento marcado. */
    public function generatePurchaseOrdersBatch(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ], ['ids.required' => 'Selecciona al menos un requerimiento.']);

        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('purchase-orders', 'create'), 403, 'No tienes permiso para crear órdenes de compra.');

        $created = collect();
        $skipped = 0;
        foreach (MaterialRequirement::whereIn('id', $data['ids'])->where('status', '!=', 'cancelado')->get() as $req) {
            $selection = collect($this->service->suggestPurchases($req))
                ->filter(fn ($g) => $g['has_supplier'])
                ->flatMap(fn ($g) => collect($g['lines'])->map(fn ($it) => $it['line']->id))
                ->all();
            if (!$selection) {
                $skipped++;

                continue;
            }
            $created = $created->merge($this->service->generatePurchaseOrders($req, $selection));
        }

        $msg = $created->isEmpty()
            ? 'Ningún requerimiento seleccionado tenía faltantes con proveedor asignado.'
            : 'Se generó ' . ($created->count() === 1 ? '1 orden de compra' : $created->count() . ' órdenes de compra') . ': ' . $created->pluck('po_number')->implode(', ') . '.'
              . ($skipped ? " {$skipped} requerimiento(s) sin faltantes con proveedor se omitieron." : '');

        return redirect()->route('admin.mrp.index')->with('success', $msg);
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $items = Products::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->orderBy('name')->limit(15)
            ->get(['id', 'name', 'sku', 'stock', 'stock_unit'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'unit' => $p->stock_unit, 'stock' => $p->stock]);

        return response()->json($items);
    }
}
