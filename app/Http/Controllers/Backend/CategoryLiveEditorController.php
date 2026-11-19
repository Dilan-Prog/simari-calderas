<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Shop\CatalogController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategorySection;
use App\Models\Collection;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Editor en vivo de Categorías (principal, subcategoría y categoría hija).
 * Misma estructura que CollectionController::liveEditor* — la app JS es la
 * misma (resources/js/admin/service-page-live-editor.js, kind 'category').
 *
 * Herencia: una categoría sin bloques propios usa los del ancestro más
 * cercano que tenga (Category::effectiveSections()).
 */
class CategoryLiveEditorController extends Controller
{
    /**
     * Bloques permitidos en categorías. Fuera quedan los que dependen de un
     * servicio (rich_header usa $servicePage), faq (las FAQ de la categoría
     * ya se pintan aparte) y pool_calculator.
     */
    public const SECTION_TYPES = [
        'product_grid', 'product_carousel', 'product_carousel_banner', 'banner', 'dual_banner',
        'category_grid', 'brand_carousel', 'brand_logos', 'html_block',
        'benefits_grid', 'cta_final', 'button', 'gallery_carousel',
        'content_tabs', 'process_steps', 'table_block', 'text_block',
    ];

    private const LEVEL_LABELS = [
        1 => 'Categoría principal',
        2 => 'Subcategoría',
        3 => 'Categoría hija',
    ];

    public function show(Category $category)
    {
        $category->load(['parent.parent', 'sections']);

        $level = max(1, min(3, (int) $category->level));
        $levelLabel = self::LEVEL_LABELS[$level];

        $ownSections = $category->sections->values();
        $inheritedFrom = null;
        $inheritedSections = collect();
        if ($ownSections->isEmpty()) {
            $effective = $category->effectiveSections(false);
            $inheritedFrom = $effective['inherited_from'];
            $inheritedSections = $effective['sections'];
        }

        $categories   = Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'parent_id', 'image_url']);
        $brands       = Brand::orderBy('name')->get(['id', 'name']);
        $collections  = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $sectionTypes = self::SECTION_TYPES;
        $publicUrl    = url('/catalogo/' . $category->slug);

        return view('admin.categories.live-editor', compact(
            'category', 'level', 'levelLabel', 'ownSections', 'inheritedFrom', 'inheritedSections',
            'categories', 'brands', 'collections', 'sectionTypes', 'publicUrl'
        ));
    }

    /**
     * Renderiza la página pública REAL (/catalogo/{slug}, página 1 sin
     * filtros) con un borrador de bloques en memoria (no persistido).
     *
     * - `sections` con elementos: se pintan esos (solo los activos).
     * - `sections` ausente o vacío: sin bloques, salvo `use_inherited=true`,
     *   que pinta los bloques activos del ancestro del que se heredaría.
     */
    public function preview(Request $request, Category $category)
    {
        $request->validate([
            'sections'             => 'nullable|array',
            'sections.*.type'      => 'required_with:sections|string',
            'sections.*.title'     => 'nullable|string',
            'sections.*.config'    => 'nullable|array',
            'sections.*.is_active' => 'nullable|boolean',
            'use_inherited'        => 'nullable|boolean',
        ]);

        $draftSections = collect($request->input('sections', []))->values()->map(function ($s, $i) use ($category) {
            $section = new CategorySection([
                'type'       => $s['type'],
                'title'      => $s['title'] ?? null,
                'config'     => $s['config'] ?? [],
                'sort_order' => $i,
                'is_active'  => (bool) ($s['is_active'] ?? true),
            ]);
            $section->id = $s['id'] ?? null;
            $section->category_id = $category->id;

            return $section;
        })->filter(fn ($s) => $s->is_active)->values();

        if ($draftSections->isEmpty() && empty($request->input('sections')) && $request->boolean('use_inherited')) {
            $category->setRelation('sections', collect());
            $draftSections = $category->effectiveSections(true)['sections'];
        }

        // Petición GET limpia de la página pública: mismas variables que
        // CatalogController::renderCatalog (productos de la página 1, sidebar,
        // facetas, chips...).
        $catalog = app(CatalogController::class);
        $publicRequest = Request::create(route('catalog.category', $category->slug), 'GET');
        $result = $catalog->catalogResult($publicRequest, $category);

        $html = view('frontend.shop.catalog.index', [
            'result'      => $result,
            'category'    => $category,
            'previewMode' => true,
        ] + CatalogController::splitSections($draftSections))->render();

        return response()->json(['html' => $html]);
    }

    public function save(Request $request, Category $category)
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

        DB::transaction(function () use ($request, $category) {
            foreach (array_values($request->input('sections')) as $i => $s) {
                $attrs = [
                    'type'       => $s['type'],
                    'title'      => $s['title'] ?? null,
                    'config'     => $s['config'] ?? [],
                    'sort_order' => $i,
                    'is_active'  => (bool) ($s['is_active'] ?? true),
                ];

                if (!empty($s['id'])) {
                    $section = CategorySection::where('id', $s['id'])
                        ->where('category_id', $category->id)
                        ->first();
                    if ($section) {
                        $section->update($attrs);
                        continue;
                    }
                }

                $category->sections()->create($attrs);
            }

            // Solo borra los bloques que el editor pide borrar de forma
            // explícita (nunca por omisión) y solo de esta categoría.
            if ($ids = $request->input('deleted_ids')) {
                CategorySection::where('category_id', $category->id)->whereIn('id', $ids)->delete();
            }
        });

        return response()->json([
            'success'  => true,
            'sections' => $category->sections()->get()->map(fn (CategorySection $s) => [
                'id'         => $s->id,
                'type'       => $s->type,
                'title'      => $s->title,
                'config'     => $s->config ?? [],
                'is_active'  => $s->is_active,
                'sort_order' => $s->sort_order,
            ])->values(),
        ]);
    }

    /**
     * Información general: nombre, slug (opcional; solo cuenta su último
     * segmento y se reconstruye con la ruta del padre), descripción, imagen,
     * estado, orden, SEO y FAQs. El padre NO se cambia aquí. Mismas reglas
     * que CategoryController::update (301 si cambia la URL).
     */
    public function general(Request $request, Category $category)
    {
        $request->validate([
            'name'            => 'required|string|max:120',
            'slug'            => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'image_url'       => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0',
            'seo_title'       => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'faq_items'       => 'nullable|array',
            'faq_items.*.question' => 'nullable|string',
            'faq_items.*.answer'   => 'nullable|string',
        ], [
            'seo_title.max' => 'El título SEO no puede tener más de 60 caracteres.',
            'seo_description.max' => 'La meta description no puede tener más de 160 caracteres.',
        ]);

        $rawSlug = $request->filled('slug') ? (string) $request->slug : Str::afterLast((string) $category->slug, '/');
        $parent = $category->parent_id ? Category::find($category->parent_id) : null;
        $oldSlug = $category->slug;
        $slug = CategoryController::uniqueSlug($parent, $rawSlug, (int) $category->id);

        $category->name            = $request->name;
        $category->slug            = $slug;
        $category->description     = $request->description ?? null;
        $category->image_url       = $request->image_url ?? null;
        $category->is_active       = $request->boolean('is_active', true);
        $category->sort_order      = $request->sort_order ?? 0;
        $category->seo_title       = $request->seo_title ?? null;
        $category->seo_description = $request->seo_description ?? null;
        CategoryController::applyFaqs($category, $request);

        // Si la URL cambió, la vieja redirige (301) a la nueva; los
        // descendientes los registra Category::saved().
        DB::transaction(function () use ($category, $oldSlug) {
            $category->save();
            if ($category->wasChanged('slug')) {
                Redirect::record('/catalogo/' . $oldSlug, '/catalogo/' . $category->slug);
            }
        });

        return response()->json([
            'success'  => true,
            'category' => [
                'id'              => $category->id,
                'name'            => $category->name,
                'slug'            => $category->slug,
                'description'     => $category->description,
                'image_url'       => $category->image_url,
                'is_active'       => $category->is_active,
                'sort_order'      => $category->sort_order,
                'seo_title'       => $category->seo_title,
                'seo_description' => $category->seo_description,
                'faqs'            => $category->faqs ?? [],
                'public_path'     => '/catalogo/' . $category->slug,
            ],
        ]);
    }

    /** Selector manual de productos: misma respuesta que admin.collections.products.search. */
    public function productsSearch(Request $request)
    {
        return response()->json(CollectionController::productSearchResults(
            (string) $request->get('q', ''),
            $request->filled('ids') ? (string) $request->input('ids') : null
        ));
    }
}
