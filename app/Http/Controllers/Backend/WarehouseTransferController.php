<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use App\Models\WarehouseTransfer;
use App\Services\WarehouseTransferService;
use Illuminate\Http\Request;

class WarehouseTransferController extends Controller
{
    public function __construct(private WarehouseTransferService $service)
    {
    }

    public function index(Request $request)
    {
        $query = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse'])->withCount('lines');

        if ($s = trim((string) $request->input('search'))) {
            $query->where('number', 'like', "%{$s}%");
        }
        if (in_array($request->input('status'), WarehouseTransfer::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', (int) $request->input('from_warehouse_id'));
        }
        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', (int) $request->input('to_warehouse_id'));
        }

        $transfers = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $stats = [
            'borrador' => WarehouseTransfer::where('status', 'borrador')->count(),
            'en_transito' => WarehouseTransfer::where('status', 'en_transito')->count(),
            'recibido' => WarehouseTransfer::where('status', 'recibido')->count(),
        ];

        return view('admin.warehouse-transfers.index', [
            'transfers' => $transfers,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return view('admin.warehouse-transfers.form', [
            'transfer' => null,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'initialLines' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            $transfer = $this->service->create(
                (int) $data['from_warehouse_id'], (int) $data['to_warehouse_id'], $data['lines'], $data['notes'] ?? null, auth()->id()
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.warehouse-transfers.show', $transfer)->with('success', "Traspaso {$transfer->number} creado.");
    }

    public function show(WarehouseTransfer $transfer)
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'lines.product', 'requestedBy', 'shippedBy', 'receivedBy']);

        $balances = WarehouseProductStock::where('warehouse_id', $transfer->from_warehouse_id)
            ->whereIn('product_id', $transfer->lines->pluck('product_id'))
            ->pluck('quantity', 'product_id');

        return view('admin.warehouse-transfers.show', compact('transfer', 'balances'));
    }

    public function edit(WarehouseTransfer $transfer)
    {
        if ($transfer->status !== 'borrador') {
            return redirect()->route('admin.warehouse-transfers.show', $transfer)->with('error', 'Solo se puede editar un traspaso en borrador.');
        }

        $transfer->load('lines.product');

        return view('admin.warehouse-transfers.form', [
            'transfer' => $transfer,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'initialLines' => $transfer->lines->map(fn ($l) => [
                'product_id' => $l->product_id, 'name' => $l->product->name, 'sku' => $l->product->sku, 'quantity' => $l->quantity,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, WarehouseTransfer $transfer)
    {
        $data = $this->validated($request);

        try {
            $this->service->updateDraft(
                $transfer, (int) $data['from_warehouse_id'], (int) $data['to_warehouse_id'], $data['lines'], $data['notes'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.warehouse-transfers.show', $transfer)->with('success', 'Traspaso actualizado.');
    }

    public function ship(WarehouseTransfer $transfer)
    {
        try {
            $this->service->ship($transfer, auth()->id());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Traspaso enviado: la mercancía quedó en tránsito.');
    }

    public function receive(Request $request, WarehouseTransfer $transfer)
    {
        $request->validate([
            'received' => 'nullable|array',
            'received.*' => 'nullable|integer|min:0',
            'receipt_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->service->receive($transfer, $request->input('received', []), $request->input('receipt_notes'), auth()->id());
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Traspaso recibido: el inventario del almacén destino fue actualizado.');
    }

    public function cancel(WarehouseTransfer $transfer)
    {
        try {
            $this->service->cancel($transfer, auth()->id());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Traspaso cancelado.');
    }

    public function destroy(WarehouseTransfer $transfer)
    {
        if ($transfer->status !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar traspasos en borrador.');
        }
        $transfer->delete();

        return redirect()->route('admin.warehouse-transfers.index')->with('success', 'Traspaso eliminado.');
    }

    /** Buscador de productos para el formulario: devuelve saldo en el almacén origen. */
    public function products(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $warehouseId = (int) $request->input('warehouse_id');

        $products = Products::where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->orderBy('name')->limit(15)->get(['id', 'name', 'sku']);

        $balances = WarehouseProductStock::where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $products->pluck('id'))->pluck('quantity', 'product_id');

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'available' => (int) ($balances[$p->id] ?? 0),
        ]));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'to_warehouse_id.different' => 'El almacén de origen y el de destino deben ser distintos.',
            'lines.required' => 'Agrega al menos un producto.',
        ]);
    }
}
