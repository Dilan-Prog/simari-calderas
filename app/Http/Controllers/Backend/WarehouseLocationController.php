<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ubicaciones dentro de un almacén (pasillo/anaquel) y detalle de saldos por
 * almacén. Permiso: `inventory` (mismo que el módulo de Inventario).
 */
class WarehouseLocationController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $warehouse = $request->filled('warehouse_id')
            ? $warehouses->firstWhere('id', (int) $request->input('warehouse_id'))
            : $warehouses->first();

        $locations = collect();
        $balances = collect();
        if ($warehouse) {
            $locations = WarehouseLocation::where('warehouse_id', $warehouse->id)->withCount('stockBalances')->orderBy('code')->get();

            $q = WarehouseProductStock::with(['product:id,name,sku', 'location:id,code,name'])
                ->where('warehouse_id', $warehouse->id)->where('quantity', '!=', 0);
            if ($s = trim((string) $request->input('search'))) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
            }
            if ($request->filled('location_id')) {
                $request->input('location_id') === 'none'
                    ? $q->whereNull('location_id')
                    : $q->where('location_id', (int) $request->input('location_id'));
            }
            $balances = $q->get()->sortBy(fn ($b) => mb_strtolower($b->product->name ?? ''))->values();
        }

        return view('admin.warehouse-locations.index', compact('warehouses', 'warehouse', 'locations', 'balances'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'code' => ['required', 'string', 'max:30', Rule::unique('warehouse_locations')->where('warehouse_id', $request->input('warehouse_id'))],
            'name' => 'nullable|string|max:100',
        ], ['code.unique' => 'Ya existe una ubicación con ese código en el almacén.']);

        WarehouseLocation::create($data + ['is_active' => true]);

        return redirect()->route('admin.warehouse-locations.index', ['warehouse_id' => $data['warehouse_id']])->with('success', 'Ubicación creada.');
    }

    public function update(Request $request, WarehouseLocation $location)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('warehouse_locations')->where('warehouse_id', $location->warehouse_id)->ignore($location->id)],
            'name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ], ['code.unique' => 'Ya existe una ubicación con ese código en el almacén.']);

        $location->update([
            'code' => $data['code'], 'name' => $data['name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.warehouse-locations.index', ['warehouse_id' => $location->warehouse_id])->with('success', 'Ubicación actualizada.');
    }

    public function destroy(WarehouseLocation $location)
    {
        $wid = $location->warehouse_id;
        // Los saldos quedan sin ubicación (FK set null); no se pierde inventario.
        $location->delete();

        return redirect()->route('admin.warehouse-locations.index', ['warehouse_id' => $wid])->with('success', 'Ubicación eliminada; sus saldos quedaron sin ubicación.');
    }

    /** Asigna (o quita) la ubicación de un saldo producto×almacén. No altera cantidades. */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'balance_id' => 'required|integer|exists:warehouse_product_stock,id',
            'location_id' => 'nullable|integer|exists:warehouse_locations,id',
        ]);

        $balance = WarehouseProductStock::findOrFail($data['balance_id']);
        $locationId = $data['location_id'] ?? null;

        if ($locationId && !WarehouseLocation::where('id', $locationId)->where('warehouse_id', $balance->warehouse_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'La ubicación no pertenece a ese almacén.'], 422);
        }

        $balance->update(['location_id' => $locationId]);

        return response()->json(['success' => true]);
    }
}
