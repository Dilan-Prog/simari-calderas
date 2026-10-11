<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\ProductionOrder;
use App\Models\Products;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\Erp\DocumentFlow;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductionOrderController extends Controller
{
    public function __construct(private ProductionService $service)
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
            'product'    => trim((string) $request->get('product', '')),
            'project_id' => (string) $request->get('project_id', ''),
            'overdue'    => (string) $request->get('overdue', ''),
        ];

        $orders = ProductionOrder::with('product:id,name,sku')
            ->when($filters['q'] !== '', fn ($q) => $q->where('number', 'like', '%' . $filters['q'] . '%'))
            ->when(in_array($filters['status'], ProductionOrder::STATUSES, true), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['product'] !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%' . $filters['product'] . '%')->orWhere('sku', 'like', '%' . $filters['product'] . '%')))
            ->when($filters['project_id'] !== '', fn ($q) => $q->where('project_id', (int) $filters['project_id']))
            ->when($filters['overdue'] === '1', fn ($q) => $q->overdue())
            ->orderByRaw('due_date IS NULL, due_date asc')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $projects = $this->projectsAvailable()
            ? Project::orderByDesc('id')->limit(300)->get(['id', 'project_number', 'name'])
            : collect();

        $chips = [];
        $labels = ['q' => 'Folio', 'status' => 'Estado', 'product' => 'Producto', 'project_id' => 'Proyecto', 'overdue' => 'Vencidas'];
        foreach ($filters as $k => $v) {
            if ($v === '') {
                continue;
            }
            $shown = match ($k) {
                'status'     => ProductionOrder::statusLabel($v),
                'project_id' => optional($projects->firstWhere('id', (int) $v))->project_number ?? ('#' . $v),
                'overdue'    => 'Sí',
                default      => $v,
            };
            $chips[] = ['key' => $k, 'label' => $labels[$k], 'value' => $shown, 'url' => $request->fullUrlWithQuery([$k => null, 'page' => null])];
        }

        return view('admin.production-orders.index', compact('orders', 'filters', 'projects', 'chips'));
    }

    public function create(Request $request)
    {
        return view('admin.production-orders.form', $this->formData(null, $request));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['user_id'] = auth()->id();
        $order = $this->service->create($data);

        return redirect()->route('admin.production-orders.show', $order)->with('success', "Orden {$order->number} creada.");
    }

    public function show(ProductionOrder $productionOrder)
    {
        $order = $productionOrder->load(['product', 'bom', 'warehouse', 'salesOrder', 'requirement', 'components.product', 'logs.user']);

        return view('admin.production-orders.show', ['order' => $order, 'project' => $order->project]);
    }

    public function edit(ProductionOrder $productionOrder)
    {
        abort_unless($productionOrder->isOpen(), 403, 'La orden ya no se puede editar.');

        return view('admin.production-orders.form', $this->formData($productionOrder, request()));
    }

    public function update(Request $request, ProductionOrder $productionOrder)
    {
        abort_unless($productionOrder->isOpen(), 403, 'La orden ya no se puede editar.');
        $data = $this->validated($request, false);

        $productionOrder->update([
            'due_date'       => $data['due_date'] ?? null,
            'start_date'     => $data['start_date'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'project_id'     => $data['project_id'] ?? null,
            'sales_order_id' => $data['sales_order_id'] ?? null,
        ]);

        return redirect()->route('admin.production-orders.show', $productionOrder)->with('success', 'Orden actualizada.');
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, ['borrador', 'cancelada'], true)) {
            return back()->withErrors(['status' => 'Solo se pueden eliminar órdenes en borrador o canceladas.']);
        }
        DocumentFlow::unlinkAll($productionOrder);
        $productionOrder->delete();

        return redirect()->route('admin.production-orders.index')->with('success', 'Orden eliminada.');
    }

    // ───────────── Acciones del ciclo ─────────────

    public function release(ProductionOrder $productionOrder)
    {
        $this->service->release($productionOrder, auth()->id());

        return back()->with('success', 'Orden liberada: componentes reservados.');
    }

    public function consume(Request $request, ProductionOrder $productionOrder)
    {
        $data = $request->validate([
            'quantities'   => 'nullable|array',
            'quantities.*' => 'nullable|numeric|min:0|max:99999999',
            'note'         => 'nullable|string|max:500',
        ]);
        $q = null;
        if (!empty($data['quantities'])) {
            $q = array_map(fn ($v) => (float) $v, $data['quantities']);
        }
        $this->service->consume($productionOrder, $q, auth()->id(), $data['note'] ?? null);

        return back()->with('success', 'Consumo registrado y descontado del almacén.');
    }

    public function finish(Request $request, ProductionOrder $productionOrder)
    {
        $data = $request->validate([
            'quantity' => 'nullable|numeric|gt:0|max:99999999',
            'note'     => 'nullable|string|max:500',
            'token'    => 'nullable|string|max:64',
        ]);
        $this->service->finish(
            $productionOrder,
            isset($data['quantity']) ? (float) $data['quantity'] : null,
            auth()->id(), $data['note'] ?? null, $data['token'] ?? null
        );

        return back()->with('success', 'Producción registrada: entrada de producto terminado aplicada.');
    }

    public function cancel(Request $request, ProductionOrder $productionOrder)
    {
        $data = $request->validate(['return_consumed' => 'nullable|boolean', 'note' => 'nullable|string|max:500']);
        $this->service->cancel($productionOrder, auth()->id(), $request->boolean('return_consumed'), $data['note'] ?? null);

        return back()->with('success', 'Orden cancelada.');
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $withBom = Bom::where('is_active', true)->select('product_id');
        $items = Products::query()
            ->whereIn('id', $withBom)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->orderBy('name')->limit(15)
            ->get(['id', 'name', 'sku', 'stock', 'stock_unit'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'unit' => $p->stock_unit, 'stock' => $p->stock]);

        return response()->json($items);
    }

    // ───────────── Utilidades ─────────────

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'warehouse_id'   => 'nullable|integer|exists:warehouses,id',
            'project_id'     => 'nullable|integer',
            'sales_order_id' => 'nullable|integer|exists:sales_orders,id',
            'due_date'       => 'nullable|date',
            'start_date'     => 'nullable|date',
            'notes'          => 'nullable|string|max:5000',
        ];
        if ($creating) {
            $rules['product_id'] = 'required|integer|exists:products,id';
            $rules['quantity'] = 'required|numeric|gt:0|max:99999999';
        }

        return $request->validate($rules);
    }

    private function formData(?ProductionOrder $order, Request $request): array
    {
        return [
            'order'      => $order,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'projects'   => $this->projectsAvailable() ? Project::orderByDesc('id')->limit(300)->get(['id', 'project_number', 'name']) : collect(),
            'orders'     => SalesOrder::where('status', '!=', 'cancelado')->orderByDesc('id')->limit(200)->get(['id', 'order_number']),
            'preProject' => (int) $request->get('project_id', 0),
            'preOrder'   => (int) $request->get('sales_order_id', 0),
            'preProduct' => ($pid = (int) $request->get('product_id', 0)) ? Products::find($pid, ['id', 'name']) : null,
        ];
    }
}
