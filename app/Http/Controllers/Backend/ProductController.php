<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Support\UploadPath;
use App\Traits\ImageUploadTrait;
use App\Traits\NormalizesProductFields;
use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\ProductImage;
use App\Models\ProductDocument;
use App\Models\ProductBulkEditView;
use App\Models\ProductSpecName;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ImageUploadTrait;
    use NormalizesProductFields;

    // FIX (Documentación tab): maps each of the 6 upload slots in the view
    // to its product_documents.type enum value.
    private const DOCUMENT_FIELDS = [
        'doc_ficha'         => 'ficha',
        'doc_manual'        => 'manual',
        'doc_catalogo'      => 'catalogo',
        'doc_certificacion' => 'certificacion',
        'doc_garantia'      => 'garantia',
        'doc_otro'          => 'otro',
    ];

    /**
     * Persists newly added gallery images — both uploaded files and
     * "usar URL" entries — in the exact order the user arranged them in the
     * unified preview grid. The client sends image_source_order[] (a list
     * of 'file'/'url' tokens reflecting the final on-screen order) since
     * files and URLs travel as two separate form fields and would otherwise
     * lose their relative interleaving.
     */
    private function saveProductImages(Products $product, Request $request, int $startingSortOrder): void
    {
        $uploadedPaths = $request->hasFile('images') ? $this->uploadImages($request->file('images')) : [];

        $urlPaths = [];
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $url) {
                $path = $this->downloadImageFromUrl($url);
                if ($path) {
                    $urlPaths[] = $path;
                }
            }
        }

        // FIX (image duplication): images picked from the "Biblioteca"
        // reuse an existing ProductImage's own path directly — no HTTP
        // fetch, no re-encode, no new physical file. IDs are validated
        // against product_images above, so this can only ever copy a path
        // that's already legitimately on disk.
        $existingPaths = [];
        if ($request->filled('existing_image_ids')) {
            $byId = ProductImage::whereIn('id', $request->existing_image_ids)->pluck('image_url', 'id');
            foreach ($request->existing_image_ids as $id) {
                if (isset($byId[$id])) {
                    $existingPaths[] = $byId[$id];
                }
            }
        }

        if (empty($uploadedPaths) && empty($urlPaths) && empty($existingPaths)) {
            return;
        }

        $sortOrder   = $startingSortOrder;
        $fileIdx     = 0;
        $urlIdx      = 0;
        $existingIdx = 0;

        foreach ($request->input('image_source_order', []) as $type) {
            $path = null;
            if ($type === 'file' && array_key_exists($fileIdx, $uploadedPaths)) {
                $path = $uploadedPaths[$fileIdx++];
            } elseif ($type === 'url' && array_key_exists($urlIdx, $urlPaths)) {
                $path = $urlPaths[$urlIdx++];
            } elseif ($type === 'existing' && array_key_exists($existingIdx, $existingPaths)) {
                $path = $existingPaths[$existingIdx++];
            }
            if ($path !== null) {
                ProductImage::create(['product_id' => $product->id, 'image_url' => $path, 'sort_order' => $sortOrder++]);
            }
        }

        // Anything not accounted for by image_source_order (missing/stale
        // on the client) is still appended, so an upload never silently
        // disappears.
        foreach (array_slice($uploadedPaths, $fileIdx) as $path) {
            ProductImage::create(['product_id' => $product->id, 'image_url' => $path, 'sort_order' => $sortOrder++]);
        }
        foreach (array_slice($urlPaths, $urlIdx) as $path) {
            ProductImage::create(['product_id' => $product->id, 'image_url' => $path, 'sort_order' => $sortOrder++]);
        }
        foreach (array_slice($existingPaths, $existingIdx) as $path) {
            ProductImage::create(['product_id' => $product->id, 'image_url' => $path, 'sort_order' => $sortOrder++]);
        }
    }

    /**
     * FIX (image duplication): now that "Biblioteca" reuses can share a
     * physical file across multiple ProductImage rows, deleting one row must
     * not unlink the file if another row still points at the same path.
     */
    private function deleteImageIfUnreferenced(ProductImage $img): void
    {
        $stillReferenced = ProductImage::where('image_url', $img->image_url)
            ->where('id', '!=', $img->id)
            ->exists();

        if (!$stillReferenced) {
            $this->deleteImage($img->image_url);
        }
    }

    /**
     * Replace the product's document of the given type with the uploaded
     * file. Each of the 6 slots in the UI is a single fixed spot, so a new
     * upload replaces (not adds to) the previous one for that type.
     */
    private function saveProductDocument(Products $product, $file, string $type): void
    {
        $existing = $product->documents()->where('type', $type)->first();
        if ($existing) {
            $this->deleteImage($existing->path);
            $existing->delete();
        }

        $dir = UploadPath::full('product-documents');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = uniqid() . '.' . $ext;
        $file->move($dir, $filename);

        ProductDocument::create([
            'product_id'    => $product->id,
            'type'          => $type,
            'path'          => 'product-documents/' . $filename,
            'original_name' => $file->getClientOriginalName(),
            'created_at'    => now(),
        ]);
    }
    // Options exposed to the "Mostrar" per-page selector in index.blade.php.
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200, 300, 400, 500];

    /**
     * Filtros compartidos por index() (catálogo) y bulkEditIndex() (editor
     * en lote) — mismo criterio de búsqueda/categoría/stock/estado en ambas
     * pantallas. $columns permite a cada pantalla pedir solo lo que necesita.
     */
    private function filteredProductsQuery(Request $request, array $columns): \Illuminate\Database\Eloquent\Builder
    {
        $query = Products::query()->select($columns);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if (in_array($request->input('stock'), ['in_stock', 'out_of_stock'], true)) {
            $request->input('stock') === 'out_of_stock'
                ? $query->where('stock', '<=', 0)
                : $query->where('stock', '>', 0);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->filteredProductsQuery($request, [
            'id',
            'name',
            'sku',
            'price',
            'cost',
            'stock',
            // FIX BUG 11/9: stock_unit is now a real column (see BUG 9) —
            // added here so index.blade.php's inventory summary shows the
            // real unit instead of always falling back to 'unidades'.
            'stock_unit',
            'is_active',
            'is_featured',
            'cover_image_url',
            'category_id',
            'brand_id',
        ])->with(['category', 'brand', 'images']);

        // Totals over the filtered set, computed before pagination/get()
        // consumes the query builder below.
        $totalFiltered  = (clone $query)->count();
        $stockSum       = (clone $query)->sum('stock');
        $firstStockUnit = (clone $query)->value('stock_unit');

        $perPageInput = $request->input('per_page', 25);
        $showAll      = $perPageInput === 'all';
        $perPage      = $showAll ? $totalFiltered : (int) $perPageInput;

        $products = $showAll
            ? $query->get()
            : $query->paginate(max($perPage, 1))->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact(
            'products',
            'categories',
            'totalFiltered',
            'stockSum',
            'firstStockUnit'
        ))->with('perPageOptions', self::PER_PAGE_OPTIONS);
    }

    private const BULK_EDIT_FIELDS = [
        'name', 'model', 'short_description', 'description',
        'price', 'compare_price', 'price_includes_tax', 'cost', 'stock', 'stock_unit', 'currency', 'availability',
        'category_id', 'brand_id', 'is_active', 'publish_on_website', 'is_featured', 'is_new', 'is_recommended',
        'tags', 'specifications', 'faqs',
        'seo_title', 'seo_description', 'seo_keywords', 'og_title', 'og_description', 'og_image',
    ];

    private const BULK_EDIT_STOCK_UNITS = ['pieza', 'juego', 'kit', 'metro', 'kg', 'litro'];
    private const BULK_EDIT_CURRENCIES = ['MXN', 'USD'];
    private const BULK_EDIT_AVAILABILITY = ['available', 'out_of_stock', 'on_order'];

    // Copiada a mano (no derivada de BULK_EDIT_FIELDS con array_diff) para
    // que un cambio futuro a una lista no afecte silenciosamente a la otra:
    // son las columnas que el usuario puede mostrar/ocultar en el editor en
    // lote (ver $bulkEditColumns en bulk_edit.blade.php) — 'name' no está
    // porque Nombre va fijo/siempre visible, no es una columna que se pueda
    // ocultar dentro de una vista guardada.
    private const BULK_EDIT_VIEW_COLUMNS = [
        'model', 'short_description', 'description',
        'price', 'compare_price', 'price_includes_tax', 'cost', 'stock', 'stock_unit', 'currency', 'availability',
        'category_id', 'category_sub', 'category_child', 'brand_id', 'is_active', 'publish_on_website', 'is_featured', 'is_new', 'is_recommended',
        'tags', 'specifications', 'faqs',
        'seo_title', 'seo_description', 'seo_keywords', 'og_title', 'og_description', 'og_image',
    ];
    private const BULK_EDIT_VIEWS_MAX_PER_USER = 10;

    /**
     * Etiqueta tipo "Padre > Hijo" para que el <select> de categoría del
     * editor en lote no sea ambiguo entre categorías con nombres parecidos
     * en distintas ramas. Requiere $category cargada con parent.parent
     * (ver bulkEditIndex()) para no disparar una consulta por categoría.
     */
    private function categoryBreadcrumb(Category $category): string
    {
        $parts = [$category->name];
        $node = $category;
        while ($node->parent) {
            $node = $node->parent;
            array_unshift($parts, $node->name);
        }

        return implode(' > ', $parts);
    }

    /**
     * Fase B — editor tipo hoja de cálculo. Mismos filtros que index(), pero
     * en una página aparte con (casi) todos los campos de un producto como
     * columnas editables — todo salvo la galería de imágenes, los
     * documentos PDF, el slug y las dimensiones físicas (sin UI en ningún
     * otro lugar del sistema todavía).
     */
    public function bulkEditIndex(Request $request)
    {
        $query = $this->filteredProductsQuery($request, [
            'id', 'name', 'sku', 'model', 'supplier_sku', 'short_description', 'description',
            'price', 'compare_price', 'price_includes_tax', 'cost', 'stock', 'stock_unit', 'currency', 'availability',
            'category_id', 'brand_id', 'is_active', 'publish_on_website', 'is_featured', 'is_new', 'is_recommended',
            'tags', 'specifications', 'faqs',
            'seo_title', 'seo_description', 'seo_keywords', 'og_title', 'og_description', 'og_image',
        ])->with(['category.parent.parent', 'suppliers']);

        $totalFiltered = (clone $query)->count();
        $perPageInput  = $request->input('per_page', 25);
        $showAll       = $perPageInput === 'all';
        $perPage       = $showAll ? $totalFiltered : (int) $perPageInput;

        $products = $showAll
            ? $query->get()
            : $query->paginate(max($perPage, 1))->withQueryString();

        // $categories: lista plana para el filtro de la barra de herramientas
        // (igual que index.blade.php). $categoryOptions: con etiqueta
        // jerárquica, usada como fallback si algún producto tuviera una
        // categoría fuera del árbol de 3 niveles. $categoryTree: mismo
        // formato que create()/edit() (raíz + 2 niveles de children
        // anidados) para alimentar el selector en cascada Principal >
        // Subcategoría > Categoría Hija de cada fila.
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $categoryOptions = Category::with('parent.parent')->get()
            ->map(fn ($c) => ['id' => $c->id, 'label' => $this->categoryBreadcrumb($c)])
            ->sortBy('label')
            ->values();
        $categoryTree = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)->select('id', 'name', 'parent_id');
                    }])
                    ->select('id', 'name', 'parent_id');
            }])
            ->orderBy('name')
            ->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        // Vistas de columnas guardadas por el usuario actual (hasta 10) —
        // ver storeBulkEditView()/updateBulkEditView()/destroyBulkEditView().
        $savedViews = ProductBulkEditView::where('user_id', auth()->id())
            ->orderBy('id')
            ->get(['id', 'name', 'columns']);

        $activeSuppliers = Supplier::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.products.bulk_edit', compact('products', 'categories', 'categoryOptions', 'categoryTree', 'brands', 'totalFiltered', 'savedViews', 'activeSuppliers'))
            ->with('perPageOptions', self::PER_PAGE_OPTIONS);
    }

    /**
     * Backfill masivo: asigna de golpe N productos seleccionados a 1
     * proveedor, llevándose el supplier_sku legacy de cada producto como el
     * SKU de ese proveedor. Usa updateOrCreate a propósito para que la
     * acción se pueda repetir sin error si el admin selecciona productos que
     * se traslapan entre corridas. No toca cost/lead_time_days (no hay dato
     * histórico que adivinar ahí) ni borra products.supplier_sku.
     */
    public function bulkAssignSupplier(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'product_ids'    => 'required|array|min:1',
            'product_ids.*'  => 'integer|exists:products,id',
        ]);

        $assigned = 0;

        DB::transaction(function () use ($validated, &$assigned) {
            $products = Products::whereIn('id', $validated['product_ids'])->get(['id', 'supplier_sku']);

            foreach ($products as $product) {
                $row = SupplierProduct::updateOrCreate(
                    ['supplier_id' => $validated['supplier_id'], 'product_id' => $product->id],
                    ['sku' => $product->supplier_sku, 'is_primary' => true]
                );

                SupplierProduct::demoteOtherPrimaries($product->id, $row->id);
                $assigned++;
            }
        });

        return response()->json([
            'success'  => true,
            'assigned' => $assigned,
            'message'  => "{$assigned} producto(s) asignado(s) correctamente.",
        ]);
    }

    /**
     * Campo => longitud máxima, para los campos de texto simple que solo
     * necesitan trim + límite (misma longitud que ya exige store()/update()
     * para el formulario completo).
     */
    private const BULK_EDIT_TEXT_MAX = [
        'name' => 255,
        'model' => 100,
        'short_description' => 500,
        'seo_title' => 60,
        'seo_description' => 160,
        'seo_keywords' => 255,
        'og_title' => 255,
        'og_image' => 255,
    ];

    /**
     * Valida y sanea un cambio individual del editor en lote. Devuelve
     * [ok, valorLimpio, mensajeDeError] — nunca lanza, para que un cambio
     * inválido no tumbe los demás cambios del mismo guardado. $productId se
     * usa para validaciones que necesitan excluir la propia fila (ninguna
     * hoy, pero se deja listo para no tener que volver a cambiar la firma).
     */
    private function validateBulkEditChange(string $field, $value, int $productId): array
    {
        if (array_key_exists($field, self::BULK_EDIT_TEXT_MAX)) {
            $v = trim((string) $value);
            if ($field === 'name' && $v === '') {
                return [false, null, 'El nombre no puede quedar vacío.'];
            }
            if (mb_strlen($v) > self::BULK_EDIT_TEXT_MAX[$field]) {
                return [false, null, 'Máximo ' . self::BULK_EDIT_TEXT_MAX[$field] . ' caracteres.'];
            }
            if ($field === 'og_image' && $v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) {
                return [false, null, 'No es una URL válida.'];
            }

            return [true, $v === '' ? null : $v, null];
        }

        switch ($field) {
            case 'price':
            case 'compare_price':
            case 'cost':
                $clean = $this->sanitizePrice($value);
                if ($clean === null || $clean < 0) {
                    return [false, null, 'No es un número válido.'];
                }

                return [true, $clean, null];

            case 'stock':
                if ($value === null || $value === '' || !is_numeric($value) || (int) $value < 0) {
                    return [false, null, 'El stock debe ser un número entero ≥ 0.'];
                }

                return [true, (int) $value, null];

            case 'description':
                // Se guarda tal cual, sin límite de longitud (igual que la
                // regla actual 'nullable|string' del formulario completo).
                // En realidad el formulario completo solo guarda texto plano
                // (el editor Quill descarta el formato al enviarse), así que
                // esta celda se trata igual que cualquier textarea largo.
                $v = trim((string) $value);

                return [true, $v === '' ? null : $v, null];

            case 'stock_unit':
                $v = trim((string) $value);
                if (!in_array($v, self::BULK_EDIT_STOCK_UNITS, true)) {
                    return [false, null, 'Unidad de stock no válida.'];
                }

                return [true, $v, null];

            case 'currency':
                $v = strtoupper(trim((string) $value));
                if (!in_array($v, self::BULK_EDIT_CURRENCIES, true)) {
                    return [false, null, 'Moneda no válida.'];
                }

                return [true, $v, null];

            case 'availability':
                $v = trim((string) $value);
                if (!in_array($v, self::BULK_EDIT_AVAILABILITY, true)) {
                    return [false, null, 'Disponibilidad no válida.'];
                }

                return [true, $v, null];

            case 'category_id':
                if (!Category::where('id', $value)->exists()) {
                    return [false, null, 'Categoría no válida.'];
                }

                return [true, (int) $value, null];

            case 'brand_id':
                if (!Brand::where('id', $value)->exists()) {
                    return [false, null, 'Marca no válida.'];
                }

                return [true, (int) $value, null];

            case 'is_active':
            case 'publish_on_website':
            case 'is_featured':
            case 'is_new':
            case 'is_recommended':
            case 'price_includes_tax':
                return [true, (bool) $value, null];

            case 'tags':
                // Llega como JSON string armado por el popover de tags
                // (mismo widget de chips que crear/editar producto). El
                // valor limpio es un array de PHP (no un JSON string),
                // porque el modelo ya tiene 'tags' => 'array' en $casts —
                // asignarle un string JSON aquí lo doble-codificaría.
                $decodedTags = json_decode((string) $value, true);
                if (!is_array($decodedTags)) {
                    return [false, null, 'Formato de tags inválido.'];
                }
                $tags = array_values(array_unique(array_filter(array_map(fn ($t) => trim((string) $t), $decodedTags), fn ($t) => $t !== '')));

                return [true, $tags, null];

            case 'specifications':
                // Llega como JSON string armado por el popover de
                // especificaciones. Esta columna no tiene cast — se asigna
                // el string JSON tal cual, igual que ya hace store()/update().
                $decoded = json_decode((string) $value, true);
                if (!is_array($decoded)) {
                    return [false, null, 'Formato de especificaciones inválido.'];
                }
                $clean = [];
                foreach ($decoded as $row) {
                    $key = trim((string) ($row['key'] ?? ''));
                    if ($key === '') {
                        continue;
                    }
                    $clean[] = ['key' => $key, 'value' => trim((string) ($row['value'] ?? ''))];
                }

                ProductSpecName::registerMany(array_column($clean, 'key'));

                return [true, json_encode($clean), null];

            case 'og_description':
                // Sin límite de longitud, igual que la regla actual
                // 'nullable|string' del formulario completo.
                $v = trim((string) $value);

                return [true, $v === '' ? null : $v, null];

            case 'faqs':
                // Llega como JSON string armado por el popover de FAQ. A
                // diferencia de specifications, products.faqs SÍ tiene cast
                // 'array' — hay que devolver el array de PHP tal cual (igual
                // que el case 'tags'), no una segunda vuelta de json_encode.
                $decodedFaqs = json_decode((string) $value, true);
                if (!is_array($decodedFaqs)) {
                    return [false, null, 'Formato de FAQ inválido.'];
                }
                $cleanFaqs = [];
                foreach ($decodedFaqs as $item) {
                    $question = trim((string) ($item['question'] ?? ''));
                    $answer = trim((string) ($item['answer'] ?? ''));
                    if ($question === '' || $answer === '') {
                        continue;
                    }
                    $cleanFaqs[] = ['question' => $question, 'answer' => $answer];
                }

                return [true, $cleanFaqs ?: null, null];
        }

        return [false, null, 'Campo no soportado.'];
    }

    public function bulkEditSave(Request $request)
    {
        $request->validate([
            'changes' => 'required|array|min:1',
            'changes.*.id' => 'required|integer|exists:products,id',
            'changes.*.field' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!in_array($value, self::BULK_EDIT_FIELDS, true)) {
                    $fail('Campo no soportado.');
                }
            }],
        ]);

        $results = [];
        // [productId => [field => valorLimpio, ...], ...] — solo los cambios
        // que pasaron la validación individual llegan aquí.
        $validByProductId = [];

        foreach ($request->input('changes') as $change) {
            $id = (int) $change['id'];
            $field = $change['field'];
            [$ok, $clean, $error] = $this->validateBulkEditChange($field, $change['value'] ?? null, $id);

            if ($ok) {
                $validByProductId[$id][$field] = $clean;
            }

            $results[] = ['id' => $id, 'field' => $field, 'ok' => $ok, 'error' => $error];
        }

        if (!empty($validByProductId)) {
            DB::transaction(function () use ($validByProductId) {
                foreach ($validByProductId as $id => $fields) {
                    $product = Products::find($id);
                    if (!$product) {
                        continue;
                    }
                    foreach ($fields as $field => $value) {
                        $product->{$field} = $value;
                    }
                    $product->save();
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
        return ProductBulkEditView::where('user_id', auth()->id())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->exists();
    }

    public function storeBulkEditView(Request $request)
    {
        $request->validate($this->bulkEditViewValidationRules());

        $existingCount = ProductBulkEditView::where('user_id', auth()->id())->count();
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

        $view = ProductBulkEditView::create([
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
        $view = ProductBulkEditView::findOrFail($id);
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
        $view = ProductBulkEditView::findOrFail($id);
        abort_if($view->user_id !== auth()->id(), 403);

        $view->delete();

        return response()->json(['success' => true]);
    }

    public function create()
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)->select('id', 'name', 'parent_id');
                    }])
                    ->select('id', 'name', 'parent_id');
            }])
            ->get(['id', 'name']);

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        $sku = \App\Services\ProductSkuGenerator::next();

        return view('admin.products.create_product.create', compact('categories', 'brands', 'sku'));
    }

    public function store(Request $request)
    {
        // Normalizar el slug ANTES de validar: unique debe evaluar lo que
        // realmente se guarda (Str::slug() más abajo), si no "Mi Producto"
        // pasa la validación y luego colisiona en BD con "mi-producto".
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->slug)]);
        }

        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku',
            // FIX BUG 1: category_id is NOT NULL in the DB with an FK
            // (onDelete restrict) to product_categories, but had no
            // validation rule — a request bypassing the JS guard passed
            // validation and crashed the INSERT with a silent 500
            // QueryException instead of a 422.
            'category_id'       => 'required|exists:product_categories,id',
            // FIX BUG 7: brand_id was marked required (* + required attr)
            // in the view and enforced client-side, but had no server rule
            // — nullable in DB, so a bypass silently saved a brand-less
            // product. Option A chosen: make it truly required.
            'brand_id'          => 'required|exists:brands,id',
            'model'             => 'nullable|string|max:100',
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'price_includes_tax' => 'nullable|boolean',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            // FIX BUG 3/5: added validation for the new columns.
            'tags'              => 'nullable|string',
            'seo_keywords'      => 'nullable|string|max:255',
            'og_title'          => 'nullable|string|max:255',
            'og_description'    => 'nullable|string',
            'og_image'          => 'nullable|url|max:255',
            // FIX BUG 9: added validation for the new currency/stock_unit
            // columns.
            'currency'          => 'nullable|in:MXN,USD',
            'stock_unit'        => 'nullable|in:pieza,juego,kit,metro,kg,litro',
            'slug'              => 'nullable|string|max:255|unique:products,slug',
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
            'is_new'            => 'nullable|boolean',
            'is_recommended'    => 'nullable|boolean',
            'publish_on_website' => 'nullable|boolean',
            'availability'      => 'nullable|in:available,on_order,out_of_stock',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,jpg,png|max:2048',
            'image_urls'        => 'nullable|array',
            'image_urls.*'      => 'url|max:2048',
            // FIX: images reused from the "Biblioteca" picker are sent as
            // existing_image_ids instead of image_urls, so saveProductImages()
            // can copy the row's own path directly instead of re-downloading
            // it as a new physical file (see saveProductImages()).
            'existing_image_ids'   => 'nullable|array',
            'existing_image_ids.*' => 'integer|exists:product_images,id',
            'image_source_order'   => 'nullable|array',
            'image_source_order.*' => 'in:file,url,existing',
            // FIX (Documentación tab): added validation for the 6 document
            // uploads — previously had no name= at all, so nothing reached
            // the server to validate.
            'doc_ficha'         => 'nullable|file|mimes:pdf|max:5120',
            'doc_manual'        => 'nullable|file|mimes:pdf|max:5120',
            'doc_catalogo'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_certificacion' => 'nullable|file|mimes:pdf|max:5120',
            'doc_garantia'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_otro'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $product = new Products();
        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug
            ? Str::slug($request->slug)
            : Str::slug($request->name);
        // FIX (reported bug): 'model' has a real column and a name="model"
        // input in the view, but was never assigned — always lost silently.
        $product->model              = $request->model         ?? null;
        $product->price             = $request->price;
        $product->cost              = $request->cost           ?? 0;
        $product->compare_price     = $request->compare_price  ?? null;
        $product->price_includes_tax = $request->boolean('price_includes_tax', false);
        $product->stock             = $request->stock          ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description    ?? null;
        // FIX BUG 10: weight/height/width/length removed from validate()
        // and assignment — postponed per decision, no UI inputs exist for
        // them yet. Columns stay null until a future ticket adds the UI.
        $product->seo_title         = $request->seo_title      ?? null;
        $product->seo_description   = $request->seo_description ?? null;
        // FIX BUG 3: JS serializes the tag chips into a JSON string in a
        // hidden input named 'tags'; decode it here so the 'array' cast on
        // the model re-encodes it consistently.
        $product->tags               = $request->filled('tags') ? json_decode($request->tags, true) : null;
        // FIX BUG 5: seo_keywords + Open Graph fields now have a real
        // column and a name= attribute in the view.
        $product->seo_keywords      = $request->seo_keywords   ?? null;
        $product->og_title          = $request->og_title       ?? null;
        $product->og_description    = $request->og_description ?? null;
        $product->og_image          = $request->og_image       ?? null;
        // FIX BUG 9: currency + stock_unit now have a real column and a
        // name= attribute in the view.
        $product->currency          = $request->currency       ?? 'MXN';
        $product->stock_unit        = $request->stock_unit     ?? 'pieza';
        $product->category_id       = $request->category_id    ?? null;
        $product->brand_id          = $request->brand_id       ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->is_new            = $request->boolean('is_new', false);
        $product->is_recommended    = $request->boolean('is_recommended', false);
        $product->publish_on_website = $request->boolean('publish_on_website', false);
        $product->availability      = $request->availability ?? 'available';
        // Save specifications
        if ($request->filled('spec_key')) {
            $specs = [];
            foreach ($request->spec_key as $i => $key) {
                if (!empty($key)) {
                    $specs[] = [
                        'key'   => $key,
                        'value' => $request->spec_value[$i] ?? '',
                    ];
                }
            }
            $product->specifications = json_encode($specs);
            ProductSpecName::registerMany(array_column($specs, 'key'));
        }

        // Preguntas frecuentes personalizadas (capturadas en el modal SEO).
        $faqs = collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();
        $product->faqs = $faqs ?: null;

        $product->save();

        $this->saveProductImages($product, $request, 0);

        // FIX (Documentación tab): save each uploaded document slot.
        foreach (self::DOCUMENT_FIELDS as $field => $type) {
            if ($request->hasFile($field)) {
                $this->saveProductDocument($product, $request->file($field), $type);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(string $id)
    {
        // FIX (Documentación tab): eager-load documents so the edit view
        // can show what's already uploaded per type.
        $product = Products::with(['category', 'brand', 'images', 'documents', 'suppliers'])->findOrFail($id);

        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)->select('id', 'name', 'parent_id');
                    }])
                    ->select('id', 'name', 'parent_id');
            }])
            ->get(['id', 'name']);

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        $allSuppliers = Supplier::where('status', 'active')->orderBy('company_name')->get();

        return view(
            'admin.products.edit_product.edit',
            compact('product', 'categories', 'brands', 'allSuppliers')
        );
    }

    public function update(Request $request, string $id)
    {
        $product = Products::findOrFail($id);

        // Normalizar el slug ANTES de validar (ver nota en store()).
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->slug)]);
        }

        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku,' . $id,
            // FIX BUG 1: category_id is NOT NULL in the DB with an FK
            // (onDelete restrict) to product_categories, but had no
            // validation rule here either — same 500 QueryException risk
            // as store(), now returns a 422 instead.
            'category_id'       => 'required|exists:product_categories,id',
            // FIX BUG 7: mirrors store() — brand_id is now truly required.
            'brand_id'          => 'required|exists:brands,id',
            'model'             => 'nullable|string|max:100',
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'price_includes_tax' => 'nullable|boolean',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            // FIX BUG 3/5: added validation for the new columns.
            'tags'              => 'nullable|string',
            'seo_keywords'      => 'nullable|string|max:255',
            'og_title'          => 'nullable|string|max:255',
            'og_description'    => 'nullable|string',
            'og_image'          => 'nullable|url|max:255',
            // FIX BUG 9: added validation for the new currency/stock_unit
            // columns.
            'currency'          => 'nullable|in:MXN,USD',
            'stock_unit'        => 'nullable|in:pieza,juego,kit,metro,kg,litro',
            'slug'              => 'nullable|string|max:255|unique:products,slug,' . $id,
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
            'is_new'            => 'nullable|boolean',
            'is_recommended'    => 'nullable|boolean',
            'publish_on_website' => 'nullable|boolean',
            'availability'      => 'nullable|in:available,on_order,out_of_stock',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,jpg,png|max:2048',
            'image_urls'        => 'nullable|array',
            'image_urls.*'      => 'url|max:2048',
            'existing_image_ids'   => 'nullable|array',
            'existing_image_ids.*' => 'integer|exists:product_images,id',
            'image_source_order'   => 'nullable|array',
            'image_source_order.*' => 'in:file,url,existing',
            'delete_images'     => 'nullable|array',
            'delete_images.*'   => 'integer|exists:product_images,id',
            // FIX (Documentación tab): mirrors store() — validation for the
            // 6 document uploads.
            'doc_ficha'         => 'nullable|file|mimes:pdf|max:5120',
            'doc_manual'        => 'nullable|file|mimes:pdf|max:5120',
            'doc_catalogo'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_certificacion' => 'nullable|file|mimes:pdf|max:5120',
            'doc_garantia'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_otro'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug
            ? Str::slug($request->slug)
            : Str::slug($request->name);
        // FIX (reported bug): 'model' has a real column and a name="model"
        // input in the view, but was never assigned — always lost silently.
        $product->model              = $request->model         ?? null;
        $product->price             = $request->price;
        $product->cost              = $request->cost           ?? 0;
        $product->compare_price     = $request->compare_price  ?? null;
        $product->price_includes_tax = $request->boolean('price_includes_tax', false);
        $product->stock             = $request->stock          ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description    ?? null;
        // FIX BUG 10: weight/height/width/length removed from validate()
        // and assignment — postponed per decision, no UI inputs exist for
        // them yet. Columns stay null until a future ticket adds the UI.
        $product->seo_title         = $request->seo_title      ?? null;
        $product->seo_description   = $request->seo_description ?? null;
        // FIX BUG 3: same as store() — decode the JSON string from the
        // hidden 'tags' input so the model's 'array' cast re-encodes it.
        $product->tags               = $request->filled('tags') ? json_decode($request->tags, true) : null;
        // FIX BUG 5: seo_keywords + Open Graph fields now have a real
        // column and a name= attribute in the view.
        $product->seo_keywords      = $request->seo_keywords   ?? null;
        $product->og_title          = $request->og_title       ?? null;
        $product->og_description    = $request->og_description ?? null;
        $product->og_image          = $request->og_image       ?? null;
        // FIX BUG 9: currency + stock_unit now have a real column and a
        // name= attribute in the view.
        $product->currency          = $request->currency       ?? 'MXN';
        $product->stock_unit        = $request->stock_unit     ?? 'pieza';
        $product->category_id       = $request->category_id    ?? null;
        $product->brand_id          = $request->brand_id       ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->is_new            = $request->boolean('is_new', false);
        $product->is_recommended    = $request->boolean('is_recommended', false);
        $product->publish_on_website = $request->boolean('publish_on_website', false);
        $product->availability      = $request->availability ?? 'available';

        // Save specifications
        if ($request->filled('spec_key')) {
            $specs = [];
            foreach ($request->spec_key as $i => $key) {
                if (!empty($key)) {
                    $specs[] = [
                        'key'   => $key,
                        'value' => $request->spec_value[$i] ?? '',
                    ];
                }
            }
            $product->specifications = json_encode($specs);
            ProductSpecName::registerMany(array_column($specs, 'key'));
        } else {
            // Clear specs if all were deleted
            $product->specifications = null;
        }

        // Preguntas frecuentes personalizadas (capturadas en el modal SEO).
        // Sin filas => null (todas fueron eliminadas).
        $faqs = collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();
        $product->faqs = $faqs ?: null;

        $product->save();

        if ($request->filled('delete_images')) {
            $toDelete = ProductImage::whereIn('id', $request->delete_images)
                ->where('product_id', $product->id)
                ->get();
            foreach ($toDelete as $img) {
                $this->deleteImageIfUnreferenced($img);
                $img->delete();
            }

            // Close any gaps left by the deletion (e.g. sort_order 0,2,3 ->
            // 0,1,2) so newly added images continue the sequence cleanly.
            foreach ($product->images()->orderBy('sort_order')->get() as $i => $img) {
                if ($img->sort_order !== $i) {
                    $img->update(['sort_order' => $i]);
                }
            }
        }

        $this->saveProductImages($product, $request, $product->images()->count());

        // FIX (Documentación tab): save/replace each uploaded document slot.
        foreach (self::DOCUMENT_FIELDS as $field => $type) {
            if ($request->hasFile($field)) {
                $this->saveProductDocument($product, $request->file($field), $type);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Lists gallery images across the whole catalog (not just the current
     * product) so the "Agregar Imagen" modal can offer reusing an image
     * already uploaded to another product instead of uploading it again.
     * Read-only; reusing one is handled client-side by feeding its URL
     * through the same "usar URL" pipeline that downloads/copies a fresh
     * local file — this keeps each product's images independent, so
     * deleting one never affects another product that reused the same
     * source image.
     *
     * NO unificar con MediaController::library(): aunque nacieron como
     * código gemelo, esa biblioteca ahora sirve el feed COMBINADO
     * (gallery_images + product_images) para el picker genérico, mientras
     * que aquí los ids devueltos viajan como ProductImage ids en
     * existing_image_ids[] para reutilizar el archivo físico — un feed
     * combinado colisionaría ids de tablas distintas y adjuntaría imágenes
     * equivocadas al producto.
     */
    public function mediaLibrary(Request $request)
    {
        $query = ProductImage::with('product:id,name,sku')
            ->whereHas('product')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('product', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $images = $query->paginate(24, ['*'], 'page', (int) $request->input('page', 1));

        return response()->json([
            'data' => $images->getCollection()->map(fn ($img) => [
                'id'           => $img->id,
                'url'          => $img->url,
                'product_name' => $img->product->name,
                'product_sku'  => $img->product->sku,
            ]),
            'has_more'  => $images->hasMorePages(),
            'next_page' => $images->currentPage() + 1,
        ]);
    }

    /**
     * Persists a new drag-and-drop order for a product's already-saved
     * gallery images. Called via AJAX the moment the user drops an image
     * in its new position — unlike delete_images (batched with the main
     * form save), reordering existing images takes effect immediately.
     */
    public function reorderImages(Request $request, string $id)
    {
        $product = Products::findOrFail($id);

        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:product_images,id',
        ]);

        $images = ProductImage::whereIn('id', $request->order)
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('id');

        if ($images->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con las imágenes de este producto.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $imageId) {
            $images[$imageId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Etiquetas ya usadas en algún producto (no hay tabla de tags — se
     * extraen de la columna JSON `products.tags`), para el autocompletado
     * del campo de etiquetas en crear/editar producto.
     */
    public function tagSuggestions(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $tags = Products::whereNotNull('tags')
            ->pluck('tags')
            ->flatten(1)
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
            ->values();

        if ($term !== '') {
            $tags = $tags->filter(fn ($tag) => mb_stripos($tag, $term) !== false)->values();
        }

        return response()->json($tags->take(10)->values());
    }

    /**
     * Nombres de especificación ya registrados en el catálogo permanente
     * (tabla product_spec_names), para el autocompletado del campo "Nombre
     * del campo" del repeater de especificaciones (crear/editar/lote).
     */
    public function specNameSuggestions(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $names = ProductSpecName::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json($names);
    }

    /**
     * Resultados para el picker de enlaces del widget de FAQ (producto,
     * colección, categoría) — el frontend ya arma [texto](url) con lo que
     * regresa aquí, sin tener que conocer las rutas de cada tipo.
     */
    public function faqLinkSearch(Request $request)
    {
        $type = $request->input('type');
        $term = trim((string) $request->input('q', ''));

        $results = match ($type) {
            'product' => Products::query()
                ->where('publish_on_website', true)
                ->when($term !== '', fn ($q) => $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
                }))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($p) => ['label' => $p->name, 'url' => route('product.show', $p->slug)]),

            'collection' => Collection::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['label' => $c->name, 'url' => route('collection.show', $c->slug)]),

            'category' => Category::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['label' => $c->name, 'url' => route('catalog.category', $c->slug)]),

            default => collect(),
        };

        return response()->json($results->values());
    }

    /**
     * FIX BUG 2: Guard against FK constraint violations before deleting.
     * 6 tables reference products.id with onDelete('restrict'); deleting a
     * product still referenced by any of them crashed with an uncaught
     * QueryException (500) instead of a clear 422. Two checks use real
     * Eloquent relations (models exist); the other four use DB::table()
     * directly since no Eloquent model exists yet for those tables
     * (order_items, inventory_movements, service_materials_used/returned) —
     * creating one is outside this module's isolated scope. Extracted so
     * both the single-delete and bulk-delete flows apply the exact same
     * protection.
     *
     * @return string[] motivos de bloqueo, vacío si se puede eliminar
     */
    private function productDeleteBlockers(Products $product): array
    {
        $blockers = [];
        if ($product->purchaseOrderItems()->count() > 0) {
            $blockers[] = 'órdenes de compra';
        }
        if ($product->serviceMaterialPlans()->count() > 0) {
            $blockers[] = 'planes de materiales de servicio';
        }
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'órdenes de venta';
        }
        if (DB::table('service_materials_used')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'materiales usados en servicios';
        }
        if (DB::table('service_materials_returned')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'materiales devueltos de servicios';
        }
        if (DB::table('inventory_movements')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'movimientos de inventario';
        }

        return $blockers;
    }

    private function deleteProductAndFiles(Products $product): void
    {
        foreach ($product->images as $img) {
            $this->deleteImageIfUnreferenced($img);
        }

        // FIX (Documentación tab): clean up physical document files too —
        // the DB rows cascade-delete via the FK, but the files on disk
        // wouldn't without this.
        foreach ($product->documents as $doc) {
            $this->deleteImage($doc->path);
        }

        $product->suppliers()->detach();
        $product->delete();
    }

    public function destroy(string $id)
    {
        // FIX (Documentación tab): eager-load documents too so their
        // physical files can be cleaned up below.
        $product = Products::with(['images', 'documents'])->findOrFail($id);

        $blockers = $this->productDeleteBlockers($product);
        if (!empty($blockers)) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar \"{$product->name}\" porque tiene " . implode(', ', $blockers) . ' asociados.',
            ], 422);
        }

        $this->deleteProductAndFiles($product);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    /**
     * Acciones masivas estilo Shopify: activar/desactivar, publicar/
     * despublicar en el sitio web, o eliminar varios productos a la vez.
     * "Seleccionar todos" en la UI solo opera sobre la página actualmente
     * visible, así que $ids siempre es una lista acotada, no todo el catálogo.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
            'action' => 'required|in:activate,deactivate,publish,unpublish,delete',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        if ($action === 'activate' || $action === 'deactivate') {
            $updated = Products::whereIn('id', $ids)->update(['is_active' => $action === 'activate']);

            return response()->json(['success' => true, 'action' => $action, 'updated' => $updated]);
        }

        if ($action === 'publish' || $action === 'unpublish') {
            $updated = Products::whereIn('id', $ids)->update(['publish_on_website' => $action === 'publish']);

            return response()->json(['success' => true, 'action' => $action, 'updated' => $updated]);
        }

        // delete: cada producto se procesa por separado (no una sola
        // transacción global) para que el bloqueo de uno solo no impida
        // borrar el resto del lote que sí es seguro eliminar.
        $products = Products::with(['images', 'documents'])->whereIn('id', $ids)->get();
        $deleted = [];
        $blocked = [];

        foreach ($products as $product) {
            $blockers = $this->productDeleteBlockers($product);
            if (!empty($blockers)) {
                $blocked[] = ['id' => $product->id, 'name' => $product->name, 'reasons' => $blockers];
                continue;
            }

            try {
                DB::transaction(function () use ($product) {
                    $this->deleteProductAndFiles($product);
                });
                $deleted[] = $product->id;
            } catch (\Throwable $e) {
                $blocked[] = ['id' => $product->id, 'name' => $product->name, 'reasons' => ['error inesperado al eliminar']];
            }
        }

        return response()->json([
            'success' => true,
            'action' => 'delete',
            'deleted' => $deleted,
            'blocked' => $blocked,
        ]);
    }
}
