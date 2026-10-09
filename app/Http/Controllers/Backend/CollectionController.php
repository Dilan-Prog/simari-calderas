<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\CollectionRule;
use App\Models\CollectionSection;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    /**
     * Tipos de bloque permitidos en el editor en vivo de colecciones (los de
     * Servicios, salvo rich_header y rating_reviews que dependen de un servicio).
     */
    public const SECTION_TYPES = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'brand_logos', 'html_block', 'faq',
        'content_tabs', 'benefits_grid', 'process_steps', 'gallery_carousel',
        'cta_final', 'button', 'table_block', 'pool_calculator',
    ];

    public function index()
    {
        $collections = Collection::orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $categories = Category::where('is_active', true)->get(['id', 'name']);
        $brands     = Brand::where('is_active', true)->get(['id', 'name']);

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'collections.index')
            ->value('columns');

        return view('admin.collections.index', compact('collections', 'categories', 'brands', 'visibleColumns'));
    }

    public function store(Request $request)
    {
        // Normalizar ANTES de validar: unique debe evaluar el slug que
        // realmente se guarda ("Mi Coleccion" colisionaría con "mi-coleccion").
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $request->validate([
            'name'            => 'required|string|max:150',
            'slug'            => 'required|string|max:180|unique:collections,slug',
            'description'     => 'nullable|string',
            'type'            => 'required|in:manual,automatic',
            'match_type'      => 'required_if:type,automatic|nullable|in:all,any',
            'image_url'       => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0',
            'seo_title'       => 'nullable|string|max:160',
            'seo_description' => 'nullable|string|max:500',
            'og_image_url'    => 'nullable|string|max:255',
            'faq_items'       => 'nullable|array',
        ]);

        $collection = new Collection();
        $collection->name        = $request->name;
        $collection->slug        = $request->slug;
        $collection->description = $request->description ?? null;
        $collection->type        = $request->type;
        $collection->match_type  = $request->type === 'automatic' ? $request->match_type : null;
        $collection->image_url   = $request->image_url ?? null;
        $collection->sort_order  = $request->sort_order ?? 0;
        $collection->is_active   = $request->boolean('is_active', true);
        $this->fillSeoAndFaqs($collection, $request);
        $collection->save();

        $this->syncRules($collection, $request);

        return response()->json([
            'success'    => true,
            'collection' => $collection->load('rules'),
        ]);
    }

    public function edit(string $id)
    {
        $collection = Collection::with('rules')->findOrFail($id);
        return response()->json($collection);
    }

    /**
     * SEO + FAQs de la página pública /coleccion/{slug}. Mismo patrón de
     * FAQs que products.faqs (trim, descartar pares incompletos, null si
     * queda vacío).
     */
    protected function fillSeoAndFaqs(Collection $collection, Request $request): void
    {
        $collection->seo_title       = $request->input('seo_title') ?: null;
        $collection->seo_description = $request->input('seo_description') ?: null;
        $collection->og_image_url    = $request->input('og_image_url') ?: null;

        $faqs = collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();

        $collection->faqs = $faqs ?: null;
    }

    public function update(Request $request, string $id)
    {
        $collection = Collection::findOrFail($id);

        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $request->validate([
            'name'            => 'required|string|max:150',
            'slug'            => 'required|string|max:180|unique:collections,slug,' . $id,
            'description'     => 'nullable|string',
            'type'            => 'required|in:manual,automatic',
            'match_type'      => 'required_if:type,automatic|nullable|in:all,any',
            'image_url'       => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0',
            'seo_title'       => 'nullable|string|max:160',
            'seo_description' => 'nullable|string|max:500',
            'og_image_url'    => 'nullable|string|max:255',
            'faq_items'       => 'nullable|array',
        ]);

        $collection->name        = $request->name;
        $collection->slug        = $request->slug;
        $collection->description = $request->description ?? null;
        $collection->type        = $request->type;
        $collection->match_type  = $request->type === 'automatic' ? $request->match_type : null;
        $collection->image_url   = $request->image_url ?? null;
        $collection->sort_order  = $request->sort_order ?? 0;
        $collection->is_active   = $request->boolean('is_active', true);
        $this->fillSeoAndFaqs($collection, $request);
        $collection->save();

        $this->syncRules($collection, $request);

        return response()->json([
            'success'    => true,
            'collection' => $collection->load('rules'),
        ]);
    }

    public function destroy(string $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();

        return response()->json(['success' => true]);
    }

    public function show(string $id)
    {
        $collection = Collection::with(['manualProducts' => function ($q) {
            $q->with(['category:id,name', 'brand:id,name']);
        }])->findOrFail($id);

        return view('admin.collections.show', compact('collection'));
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');

        $products = Products::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'name', 'sku', 'cover_image_url', 'price']);

        return response()->json($products);
    }

    public function addProduct(Request $request, string $collection)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $collectionModel = Collection::findOrFail($collection);

        $alreadyExists = $collectionModel->manualProducts()
            ->where('products.id', $request->product_id)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'El producto ya está en esta colección.',
            ], 422);
        }

        $nextOrder = ($collectionModel->manualProducts()->max('collection_products.sort_order') ?? -1) + 1;

        $collectionModel->manualProducts()->attach($request->product_id, [
            'sort_order' => $nextOrder,
        ]);

        $product = Products::select('id', 'name', 'sku', 'cover_image_url', 'price')
            ->findOrFail($request->product_id);

        return response()->json([
            'success' => true,
            'product' => array_merge($product->toArray(), ['sort_order' => $nextOrder]),
        ]);
    }

    public function removeProduct(string $collection, string $product)
    {
        $collectionModel = Collection::findOrFail($collection);
        $collectionModel->manualProducts()->detach($product);

        return response()->json(['success' => true]);
    }

    public function reorderProducts(Request $request, string $collection)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:products,id',
        ]);

        $collectionModel = Collection::findOrFail($collection);

        $currentIds = $collectionModel->manualProducts()->pluck('products.id')->all();

        if (count($request->order) !== count($currentIds) || array_diff($request->order, $currentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'La lista de productos no coincide con la colección.',
            ], 422);
        }

        foreach ($request->order as $index => $productId) {
            $collectionModel->manualProducts()->updateExistingPivot($productId, [
                'sort_order' => $index,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ── Editor en vivo ──────────────────────────────────────────────────────

    public function liveEditor(Collection $collection)
    {
        $collection->load(['sections', 'rules', 'manualProducts' => function ($q) {
            $q->with(['category:id,name', 'brand:id,name']);
        }]);
        $categories   = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands       = Brand::orderBy('name')->get(['id', 'name']);
        $collections  = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $sectionTypes = self::SECTION_TYPES;

        return view('admin.collections.live-editor', compact(
            'collection', 'categories', 'brands', 'collections', 'sectionTypes'
        ));
    }

    /**
     * Renderiza la página pública REAL con un borrador de bloques en memoria
     * (no persistido) y las mismas variables que Frontend\Shop\CollectionController.
     */
    public function liveEditorPreview(Request $request, Collection $collection)
    {
        $request->validate([
            'sections'             => 'nullable|array',
            'sections.*.type'      => 'required_with:sections|string',
            'sections.*.title'     => 'nullable|string',
            'sections.*.config'    => 'nullable|array',
            'sections.*.is_active' => 'nullable|boolean',
        ]);

        $draftSections = collect($request->input('sections', []))->map(function ($s, $i) use ($collection) {
            $section = new CollectionSection([
                'type'       => $s['type'],
                'title'      => $s['title'] ?? null,
                'config'     => $s['config'] ?? [],
                'sort_order' => $i,
                'is_active'  => (bool) ($s['is_active'] ?? true),
            ]);
            $section->id = $s['id'] ?? null;
            $section->collection_id = $collection->id;

            return $section;
        })->filter(fn ($s) => $s->is_active)->values();

        $collection->load('rules');

        $products = $collection->productsQuery()
            ->paginate(24)
            ->withQueryString();

        $html = view('frontend.shop.collection.show', [
            'collection'  => $collection,
            'products'    => $products,
            'sections'    => $draftSections,
            'previewMode' => true,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function liveEditorSave(Request $request, Collection $collection)
    {
        $request->validate([
            // 'present' (no 'required'): un array vacío es un estado válido.
            'sections'             => 'present|array',
            'sections.*.type'      => 'required|string|in:' . implode(',', self::SECTION_TYPES),
            'sections.*.title'     => 'nullable|string|max:255',
            'sections.*.config'    => 'nullable|array',
            'sections.*.is_active' => 'nullable|boolean',
            'deleted_ids'          => 'nullable|array',
            'deleted_ids.*'        => 'integer',
        ]);

        DB::transaction(function () use ($request, $collection) {
            foreach (array_values($request->input('sections')) as $i => $s) {
                $attrs = [
                    'type'       => $s['type'],
                    'title'      => $s['title'] ?? null,
                    'config'     => $s['config'] ?? [],
                    'sort_order' => $i,
                    'is_active'  => (bool) ($s['is_active'] ?? true),
                ];

                if (!empty($s['id'])) {
                    $section = CollectionSection::where('id', $s['id'])
                        ->where('collection_id', $collection->id)
                        ->first();
                    if ($section) {
                        $section->update($attrs);
                        continue;
                    }
                }

                $collection->sections()->create($attrs);
            }

            // Solo borra los bloques que el editor pide borrar de forma explícita
            // (nunca por omisión).
            if ($ids = $request->input('deleted_ids')) {
                $collection->sections()->whereIn('id', $ids)->delete();
            }
        });

        return response()->json(['success' => true]);
    }

    /** Información general (nombre, slug, descripción, imagen, SEO, FAQs). */
    public function liveEditorGeneral(Request $request, Collection $collection)
    {
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $request->validate([
            'name'            => 'required|string|max:150',
            'slug'            => 'required|string|max:180|unique:collections,slug,' . $collection->id,
            'description'     => 'nullable|string',
            'image_url'       => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0',
            'seo_title'       => 'nullable|string|max:160',
            'seo_description' => 'nullable|string|max:500',
            'og_image_url'    => 'nullable|string|max:255',
            'faq_items'       => 'nullable|array',
        ]);

        $collection->name        = $request->name;
        $collection->slug        = $request->slug;
        $collection->description = $request->description ?? null;
        $collection->image_url   = $request->image_url ?? null;
        $collection->sort_order  = $request->sort_order ?? 0;
        $collection->is_active   = $request->boolean('is_active', true);
        $this->fillSeoAndFaqs($collection, $request);
        $collection->save();

        return response()->json([
            'success'    => true,
            'collection' => [
                'id'              => $collection->id,
                'name'            => $collection->name,
                'slug'            => $collection->slug,
                'description'     => $collection->description,
                'image_url'       => $collection->image_url,
                'sort_order'      => $collection->sort_order,
                'is_active'       => $collection->is_active,
                'seo_title'       => $collection->seo_title,
                'seo_description' => $collection->seo_description,
                'og_image_url'    => $collection->og_image_url,
                'faqs'            => $collection->faqs ?? [],
                'public_path'     => '/coleccion/' . $collection->slug,
            ],
        ]);
    }

    /** Tipo (manual/automática), coincidencia y reglas. */
    public function liveEditorSettings(Request $request, Collection $collection)
    {
        // Acepta `rules: [{field,operator,value}]` y lo convierte a los arrays
        // paralelos que consume syncRules().
        if (is_array($request->input('rules'))) {
            $rules = array_values($request->input('rules'));
            $request->merge([
                'rule_field'    => array_column($rules, 'field'),
                'rule_operator' => array_column($rules, 'operator'),
                'rule_value'    => array_column($rules, 'value'),
            ]);
        }

        $request->validate([
            'type'       => 'required|in:manual,automatic',
            'match_type' => 'required_if:type,automatic|nullable|in:all,any',
            'rules'      => 'nullable|array',
            'rules.*.field'    => 'nullable|in:tag,category_id,brand_id,price',
            'rules.*.operator' => 'nullable|in:equals,greater_than,less_than',
            'rules.*.value'    => 'nullable|string|max:255',
            'rule_field.*'    => 'nullable|in:tag,category_id,brand_id,price',
            'rule_operator.*' => 'nullable|in:equals,greater_than,less_than',
            'rule_value.*'    => 'nullable|string|max:255',
        ]);

        $collection->type       = $request->type;
        $collection->match_type = $request->type === 'automatic' ? $request->match_type : null;
        $collection->save();

        $this->syncRules($collection, $request);

        return response()->json([
            'success'    => true,
            'type'       => $collection->type,
            'match_type' => $collection->match_type,
            'rules'      => $collection->rules()->get(['id', 'field', 'operator', 'value']),
        ]);
    }

    protected function syncRules(Collection $collection, Request $request): void
    {
        if ($collection->type !== 'automatic') {
            return;
        }

        $collection->rules()->delete();

        if ($request->filled('rule_field')) {
            foreach ($request->rule_field as $i => $field) {
                $value = $request->rule_value[$i] ?? '';
                if (empty($field) || $value === '') {
                    continue;
                }

                CollectionRule::create([
                    'collection_id' => $collection->id,
                    'field'         => $field,
                    'operator'      => $request->rule_operator[$i] ?? 'equals',
                    'value'         => $value,
                ]);
            }
        }
    }
}
