<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InventoryBulkEditView;
use App\Models\InventoryMovement;
use App\Models\Products;
use App\Models\UserColumnPreference;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    private const MOVEMENT_TYPES = ['entrada', 'salida', 'ajuste'];

    // Reutilizado por bulkEditIndex() — mismo patrón que BrandController.
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200, 300, 400, 500];

    // Columnas informativas opcionales de la grilla producto×almacén — 'name',
    // 'sku' y 'quantity' (la editable) van fijas/pinneadas, no viven aquí.
    private const BULK_EDIT_VIEW_COLUMNS = ['category', 'brand'];
    private const BULK_EDIT_PINNED_COLUMNS = ['name', 'sku', 'quantity'];
    private const BULK_EDIT_VIEWS_MAX_PER_USER = 10;

    public function __construct(private InventoryService $inventoryService)
    {
    }

    // ============================================================
    // Movimientos
    // ============================================================

    public function index(Request $request)
    {
        $query = InventoryMovement::with(['warehouse', 'product']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('movement_type') && in_array($request->input('movement_type'), self::MOVEMENT_TYPES, true)) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', 'like', '%' . $request->input('reference_type') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();

        // Catálogo completo (solo id/name/sku) para el buscador de producto
        // en cliente — mismo patrón que SupplierProductController con
        // $allProducts, ver _modal_assign_product.blade.php.
        $products = Products::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        $visibleColumns = UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'inventory-movements.index')
            ->value('columns');

        return view('admin.inventory.index', compact('movements', 'warehouses', 'products', 'visibleColumns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'movement_type' => ['required', 'string', Rule::in(self::MOVEMENT_TYPES)],
            'quantity' => 'required|integer|min:1',
            // Solo relevante cuando movement_type = ajuste — decide si la
            // magnitud (siempre positiva en el request) sube o baja el
            // saldo. DECISIÓN DE DISEÑO: el ticket no especifica cómo un
            // ajuste manual (a diferencia del ajuste automático de la
            // edición masiva, que sí conoce el delta real) decide su
            // sentido, así que se agrega este selector explícito en vez de
            // asumir que "ajuste" siempre suma.
            'adjustment_direction' => ['nullable', 'required_if:movement_type,ajuste', 'string', 'in:increase,decrease'],
            'notes' => 'nullable|string|max:500',
        ]);

        $quantity = (int) $validated['quantity'];
        if ($validated['movement_type'] === 'ajuste' && ($validated['adjustment_direction'] ?? 'increase') === 'decrease') {
            $quantity = -$quantity;
        }

        try {
            $movement = $this->inventoryService->applyMovement(
                (int) $validated['warehouse_id'],
                (int) $validated['product_id'],
                $validated['movement_type'],
                $quantity,
                null,
                null,
                $validated['notes'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar el movimiento: ' . $e->getMessage(),
            ], 422);
        }

        $movement->load(['warehouse', 'product']);

        return response()->json(['success' => true, 'movement' => $movement]);
    }

    // ============================================================
    // Almacenes
    // ============================================================

    public function warehousesIndex()
    {
        $warehouses = Warehouse::withCount('movements')->orderBy('name')->get();

        return view('admin.inventory.warehouses', compact('warehouses'));
    }

    public function warehousesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:warehouses,name',
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse = new Warehouse();
        $warehouse->name = $validated['name'];
        $warehouse->location = $validated['location'] ?? null;
        $warehouse->is_active = $request->boolean('is_active', true);
        // $timestamps=false en el modelo (la tabla no tiene updated_at) — se
        // asigna a mano, igual que InventoryMovement en InventoryService.
        $warehouse->created_at = now();
        $warehouse->save();

        return response()->json(['success' => true, 'warehouse' => $warehouse]);
    }

    public function warehousesUpdate(Request $request, string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:warehouses,name,' . $id,
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse->name = $validated['name'];
        $warehouse->location = $validated['location'] ?? null;
        $warehouse->is_active = $request->boolean('is_active', true);
        $warehouse->save();

        return response()->json(['success' => true, 'warehouse' => $warehouse]);
    }

    public function warehousesDestroy(string $id)
    {
        $warehouse = Warehouse::withCount('movements')->findOrFail($id);

        if ($warehouse->movements_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "No puedes eliminar el almacén \"{$warehouse->name}\" porque tiene movimientos de inventario asociados.",
            ], 422);
        }

        // Las filas de warehouse_product_stock solo se crean dentro de
        // InventoryService::applyMovement() junto con un InventoryMovement,
        // así que si no hay movimientos tampoco puede haber saldo — el
        // check de arriba ya cubre el FK restrict de warehouse_product_stock.
        $warehouse->delete();

        return response()->json(['success' => true]);
    }

    // ============================================================
    // Edición masiva de stock (grilla producto × almacén seleccionado)
    // ============================================================

    private function filteredActiveProductsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Products::where('is_active', true)->with(['category', 'brand']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function bulkEditIndex(Request $request)
    {
        $warehouseId = $request->filled('warehouse_id')
            ? (int) $request->input('warehouse_id')
            : $this->inventoryService->defaultWarehouseId();

        $query = $this->filteredActiveProductsQuery($request)->orderBy('name');

        $totalFiltered = (clone $query)->count();
        $perPageInput = $request->input('per_page', 25);
        $showAll = $perPageInput === 'all';
        $perPage = $showAll ? $totalFiltered : (int) $perPageInput;

        $products = $showAll
            ? $query->get()
            : $query->paginate(max($perPage, 1))->withQueryString();

        $productIds = collect($products)->pluck('id');

        $stockMap = WarehouseProductStock::where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->pluck('quantity', 'product_id');

        $warehouses = Warehouse::orderBy('name')->get();

        $savedViews = InventoryBulkEditView::where('user_id', auth()->id())
            ->orderBy('id')
            ->get(['id', 'name', 'columns', 'widths']);

        return view('admin.inventory.bulk_edit', compact('products', 'totalFiltered', 'warehouseId', 'warehouses', 'stockMap', 'savedViews'))
            ->with('perPageOptions', self::PER_PAGE_OPTIONS);
    }

    /**
     * Valida y sanea la nueva cantidad de una celda del editor en lote.
     * Devuelve [ok, valorLimpio, mensajeDeError] — nunca lanza, mismo
     * contrato que BrandController::validateBulkEditChange().
     */
    private function validateBulkEditChange($value): array
    {
        if (!is_numeric($value) || (int) $value != $value) {
            return [false, null, 'La cantidad debe ser un número entero.'];
        }

        $v = (int) $value;

        if ($v < 0) {
            return [false, null, 'La cantidad no puede ser negativa.'];
        }

        return [true, $v, null];
    }

    public function bulkEditSave(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'changes' => 'required|array|min:1',
            'changes.*.id' => 'required|integer|exists:products,id',
        ]);

        $warehouseId = (int) $request->input('warehouse_id');
        $results = [];

        foreach ($request->input('changes') as $change) {
            $productId = (int) $change['id'];
            [$ok, $clean, $error] = $this->validateBulkEditChange($change['value'] ?? null);

            if ($ok) {
                $current = (int) (WarehouseProductStock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0);

                $delta = $clean - $current;

                if ($delta !== 0) {
                    try {
                        // DESVIACIÓN DOCUMENTADA respecto al ticket: el ticket
                        // pedía pasar siempre abs($delta) como cantidad. Eso
                        // pierde el signo y, combinado con la convención de
                        // InventoryService::applyMovement() (donde 'ajuste'
                        // usa el signo de $quantity tal cual), aplicaría
                        // SIEMPRE un aumento aunque el usuario haya bajado el
                        // stock — un bug real de dirección. Se pasa el delta
                        // con signo para que el ajuste mueva el saldo en el
                        // sentido correcto.
                        $this->inventoryService->applyMovement(
                            $warehouseId,
                            $productId,
                            'ajuste',
                            $delta,
                            null,
                            null,
                            'Ajuste por edición masiva'
                        );
                    } catch (\Throwable $e) {
                        $ok = false;
                        $error = 'No se pudo aplicar el ajuste: ' . $e->getMessage();
                    }
                }
            }

            $results[] = ['id' => $productId, 'field' => 'quantity', 'ok' => $ok, 'error' => $error];
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    private function bulkEditViewValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in(self::BULK_EDIT_VIEW_COLUMNS)],
            'widths' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $allowed = array_merge(self::BULK_EDIT_VIEW_COLUMNS, self::BULK_EDIT_PINNED_COLUMNS);
                foreach (array_keys($value) as $key) {
                    if (!in_array($key, $allowed, true)) {
                        $fail("Columna de ancho no soportada: {$key}.");
                    }
                }
            }],
            'widths.*' => ['integer', 'min:50', 'max:800'],
        ];
    }

    private function bulkEditViewNameTaken(string $name, ?int $excludeId = null): bool
    {
        return InventoryBulkEditView::where('user_id', auth()->id())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->exists();
    }

    public function storeBulkEditView(Request $request)
    {
        $request->validate($this->bulkEditViewValidationRules());

        $existingCount = InventoryBulkEditView::where('user_id', auth()->id())->count();
        if ($existingCount >= self::BULK_EDIT_VIEWS_MAX_PER_USER) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes el máximo de ' . self::BULK_EDIT_VIEWS_MAX_PER_USER . ' vistas guardadas. Elimina una para poder guardar otra.',
            ], 422);
        }

        if ($this->bulkEditViewNameTaken($request->name)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una vista guardada con ese nombre.',
            ], 422);
        }

        $view = InventoryBulkEditView::create([
            'user_id' => auth()->id(),
            'name' => trim($request->name),
            'columns' => $request->columns,
            'widths' => $request->widths,
        ]);

        return response()->json([
            'success' => true,
            'view' => $view->only(['id', 'name', 'columns', 'widths']),
        ]);
    }

    public function updateBulkEditView(Request $request, string $id)
    {
        $view = InventoryBulkEditView::findOrFail($id);
        abort_if($view->user_id !== auth()->id(), 403);

        $request->validate($this->bulkEditViewValidationRules());

        if ($this->bulkEditViewNameTaken($request->name, $view->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una vista guardada con ese nombre.',
            ], 422);
        }

        $view->update([
            'name' => trim($request->name),
            'columns' => $request->columns,
            'widths' => $request->widths,
        ]);

        return response()->json([
            'success' => true,
            'view' => $view->only(['id', 'name', 'columns', 'widths']),
        ]);
    }

    public function destroyBulkEditView(string $id)
    {
        $view = InventoryBulkEditView::findOrFail($id);
        abort_if($view->user_id !== auth()->id(), 403);

        $view->delete();

        return response()->json(['success' => true]);
    }
}
