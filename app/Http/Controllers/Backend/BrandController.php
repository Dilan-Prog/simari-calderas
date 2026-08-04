<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandBulkEditView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    // Reutilizado por index() (implícito, 10/página) y bulkEditIndex().
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200, 300, 400, 500];

    // Campos editables desde la edición masiva — 'name' no está aquí porque
    // va fijo/pinneado en la tabla (ver BULK_EDIT_VIEW_COLUMNS más abajo).
    private const BULK_EDIT_FIELDS = ['name', 'slug', 'description', 'logo_url', 'is_active', 'seo_title', 'seo_description'];

    // Copiada a mano (no derivada de BULK_EDIT_FIELDS) para que un cambio
    // futuro a una lista no afecte silenciosamente a la otra: son las
    // columnas que el usuario puede mostrar/ocultar en el editor en lote.
    private const BULK_EDIT_VIEW_COLUMNS = ['slug', 'description', 'logo_url', 'is_active', 'seo_title', 'seo_description'];

    private const BULK_EDIT_TEXT_MAX = [
        'name' => 120,
        'slug' => 150,
        'seo_title' => 60,
        'seo_description' => 160,
    ];

    private const BULK_EDIT_VIEWS_MAX_PER_USER = 10;

    public function index()
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->paginate(10);

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'brands.index')
            ->value('columns');

        return view('admin.brands.index', compact('brands', 'visibleColumns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120|unique:brands,name',
            'slug'        => 'required|string|max:150|unique:brands,slug',
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $brand = new Brand();
        $brand->name        = $request->name;
        $brand->slug        = Str::slug($request->slug);
        $brand->description = $request->description ?? null;
        $brand->logo_url    = $request->logo_url    ?? null;
        $brand->is_active   = $request->boolean('is_active', true);
        $brand->save();

        return response()->json([
            'success' => true,
            'brand'   => $brand->loadCount('products'),
        ]);
    }

    public function edit(string $id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:120|unique:brands,name,' . $id,
            'slug'        => 'required|string|max:150|unique:brands,slug,' . $id,
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $brand->name        = $request->name;
        $brand->slug        = Str::slug($request->slug);
        $brand->description = $request->description ?? null;
        $brand->logo_url    = $request->logo_url    ?? null;
        $brand->is_active   = $request->boolean('is_active', true);
        $brand->save();

        return response()->json([
            'success' => true,
            'brand'   => $brand->loadCount('products'),
        ]);
    }

    public function destroy(string $id)
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        if ($brand->products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar una marca que tiene productos asociados.',
            ], 422);
        }

        $brand->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Filtros compartidos por bulkEditIndex() — búsqueda por nombre/slug y
     * estado. Mucho más simple que el equivalente de productos (sin
     * stock/categoría, que no existen en Marcas). No toca index(), que
     * sigue filtrando en JS del lado del cliente.
     */
    private function filteredBrandsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Brand::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return $query;
    }

    /**
     * Editor en lote tipo hoja de cálculo — mismo patrón que
     * ProductController::bulkEditIndex(), adaptado al modelo mucho más
     * simple de Brand (sin categoría/specs/FAQ que precargar).
     */
    public function bulkEditIndex(Request $request)
    {
        $query = $this->filteredBrandsQuery($request)->orderBy('name');

        $totalFiltered = (clone $query)->count();
        $perPageInput  = $request->input('per_page', 25);
        $showAll       = $perPageInput === 'all';
        $perPage       = $showAll ? $totalFiltered : (int) $perPageInput;

        $brands = $showAll
            ? $query->get()
            : $query->paginate(max($perPage, 1))->withQueryString();

        // Vistas de columnas guardadas por el usuario actual (hasta 10) —
        // ver storeBulkEditView()/updateBulkEditView()/destroyBulkEditView().
        $savedViews = BrandBulkEditView::where('user_id', auth()->id())
            ->orderBy('id')
            ->get(['id', 'name', 'columns']);

        return view('admin.brands.bulk_edit', compact('brands', 'totalFiltered', 'savedViews'))
            ->with('perPageOptions', self::PER_PAGE_OPTIONS);
    }

    /**
     * Valida y sanea un cambio individual del editor en lote. Devuelve
     * [ok, valorLimpio, mensajeDeError] — nunca lanza, para que un cambio
     * inválido no tumbe los demás cambios del mismo guardado.
     */
    private function validateBulkEditChange(string $field, $value, int $brandId): array
    {
        // FIX (mismo bug ya corregido en Colecciones/Productos): el slug se
        // normaliza ANTES de comprobar unicidad, no después — si no, un
        // slug con distinto casing pasaría la validación y luego reventaría
        // el UPDATE con un choque de índice único.
        if ($field === 'slug') {
            $v = Str::slug(trim((string) $value));

            if ($v === '') {
                return [false, null, 'El slug no puede quedar vacío.'];
            }
            if (mb_strlen($v) > self::BULK_EDIT_TEXT_MAX['slug']) {
                return [false, null, 'Máximo ' . self::BULK_EDIT_TEXT_MAX['slug'] . ' caracteres.'];
            }
            if (Brand::where('slug', $v)->where('id', '!=', $brandId)->exists()) {
                return [false, null, 'El slug ya está en uso.'];
            }

            return [true, $v, null];
        }

        if (array_key_exists($field, self::BULK_EDIT_TEXT_MAX)) {
            $v = trim((string) $value);

            if ($field === 'name') {
                if ($v === '') {
                    return [false, null, 'El nombre no puede quedar vacío.'];
                }
                if (Brand::where('name', $v)->where('id', '!=', $brandId)->exists()) {
                    return [false, null, 'El nombre ya está en uso.'];
                }
            }

            if (mb_strlen($v) > self::BULK_EDIT_TEXT_MAX[$field]) {
                return [false, null, 'Máximo ' . self::BULK_EDIT_TEXT_MAX[$field] . ' caracteres.'];
            }

            return [true, $v === '' ? null : $v, null];
        }

        switch ($field) {
            case 'description':
            case 'logo_url':
                $v = trim((string) $value);

                return [true, $v === '' ? null : $v, null];

            case 'is_active':
                return [true, (bool) $value, null];
        }

        return [false, null, 'Campo no soportado.'];
    }

    public function bulkEditSave(Request $request)
    {
        $request->validate([
            'changes' => 'required|array|min:1',
            'changes.*.id' => 'required|integer|exists:brands,id',
            'changes.*.field' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!in_array($value, self::BULK_EDIT_FIELDS, true)) {
                    $fail('Campo no soportado.');
                }
            }],
        ]);

        $results = [];
        // [brandId => [field => valorLimpio, ...], ...] — solo los cambios
        // que pasaron la validación individual llegan aquí.
        $validByBrandId = [];

        foreach ($request->input('changes') as $change) {
            $id = (int) $change['id'];
            $field = $change['field'];
            [$ok, $clean, $error] = $this->validateBulkEditChange($field, $change['value'] ?? null, $id);

            if ($ok) {
                $validByBrandId[$id][$field] = $clean;
            }

            $results[] = ['id' => $id, 'field' => $field, 'ok' => $ok, 'error' => $error];
        }

        if (!empty($validByBrandId)) {
            DB::transaction(function () use ($validByBrandId) {
                foreach ($validByBrandId as $id => $fields) {
                    $brand = Brand::find($id);
                    if (!$brand) {
                        continue;
                    }
                    foreach ($fields as $field => $value) {
                        $brand->{$field} = $value;
                    }
                    $brand->save();
                }
            });
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    private function bulkEditViewValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in(self::BULK_EDIT_VIEW_COLUMNS)],
        ];
    }

    /**
     * true si el usuario actual ya tiene otra vista con este nombre
     * (case-insensitive, sin espacios en los extremos). $excludeId permite
     * ignorar la propia fila al renombrar en updateBulkEditView().
     */
    private function bulkEditViewNameTaken(string $name, ?int $excludeId = null): bool
    {
        return BrandBulkEditView::where('user_id', auth()->id())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->exists();
    }

    public function storeBulkEditView(Request $request)
    {
        $request->validate($this->bulkEditViewValidationRules());

        $existingCount = BrandBulkEditView::where('user_id', auth()->id())->count();
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

        $view = BrandBulkEditView::create([
            'user_id' => auth()->id(),
            'name' => trim($request->name),
            'columns' => $request->columns,
        ]);

        return response()->json([
            'success' => true,
            'view' => $view->only(['id', 'name', 'columns']),
        ]);
    }

    public function updateBulkEditView(Request $request, string $id)
    {
        $view = BrandBulkEditView::findOrFail($id);
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
        ]);

        return response()->json([
            'success' => true,
            'view' => $view->only(['id', 'name', 'columns']),
        ]);
    }

    public function destroyBulkEditView(string $id)
    {
        $view = BrandBulkEditView::findOrFail($id);
        abort_if($view->user_id !== auth()->id(), 403);

        $view->delete();

        return response()->json(['success' => true]);
    }
}
