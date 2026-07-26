<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\Products;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    protected array $types = [
        'hero_slider', 'banner', 'dual_banner', 'product_carousel',
        'product_carousel_banner', 'category_grid', 'brand_carousel', 'html_block',
    ];

    // La página de producto admite todos los tipos menos el slider principal.
    protected array $productPageTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq',
    ];

    // Las páginas de colección: igual que producto (sin hero_slider, con faq).
    protected array $collectionPageTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq',
    ];

    protected array $sources = [
        'featured', 'new', 'recommended', 'category', 'brand', 'collection', 'manual',
    ];

    // Fuentes relativas al producto que se está viendo: solo tienen sentido
    // en la página de producto.
    protected array $productPageSources = [
        'featured', 'new', 'recommended', 'category', 'brand', 'collection', 'manual',
        'related_category', 'related_brand',
    ];

    public function index()
    {
        $page = match (request('pagina')) {
            'producto'    => 'product',
            'colecciones' => 'collection',
            default       => 'home',
        };

        $sections = HomeSection::where('page', $page)->orderBy('sort_order')->orderBy('id')->get();
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $brands = Brand::orderBy('name')->get();
        $collections = Collection::where('is_active', true)->orderBy('name')->get();

        return view('admin.home-sections.index', compact('sections', 'categories', 'brands', 'collections', 'page'));
    }

    /**
     * Backs the "Selección Manual" product picker: searches by name/SKU
     * (q=) for the live dropdown, or looks products up by id (ids=1,2,3)
     * to render existing chips with real names when editing a section.
     */
    public function searchProducts(Request $request)
    {
        $query = Products::query();

        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->input('ids'))));
            $query->whereIn('id', $ids);
        } else {
            $term = $request->input('q', '');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $products = $query->limit(20)->get(['id', 'name', 'sku', 'cover_image_url', 'price']);

        return response()->json($products);
    }

    protected function buildConfig(Request $request, string $type): ?array
    {
        switch ($type) {
            case 'product_carousel':
                return [
                    'source'        => $request->input('source', 'featured'),
                    'category_id'   => $request->input('category_id') ?: null,
                    'brand_id'      => $request->input('brand_id') ?: null,
                    'collection_id' => $request->input('collection_id') ?: null,
                    'product_ids'   => array_values(array_filter((array) $request->input('product_ids', []))),
                    'limit'         => $request->input('limit') !== null ? (int) $request->input('limit') : null,
                ];

            case 'banner':
                return [
                    'image_url' => $request->input('banner_image_url'),
                    'link_url'  => $request->input('banner_link_url'),
                    'alt'       => $request->input('banner_alt'),
                ];

            case 'product_carousel_banner':
                return [
                    'banner_image_url' => $request->input('pcb_banner_image_url'),
                    'banner_link_url'  => $request->input('pcb_banner_link_url'),
                    'banner_alt'       => $request->input('pcb_banner_alt'),
                    'source'           => $request->input('pcb_source', 'featured'),
                    'category_id'      => $request->input('pcb_category_id') ?: null,
                    'brand_id'         => $request->input('pcb_brand_id') ?: null,
                    'collection_id'    => $request->input('pcb_collection_id') ?: null,
                    'product_ids'      => array_values(array_filter((array) $request->input('pcb_product_ids', []))),
                    'limit'            => $request->input('pcb_limit') !== null ? (int) $request->input('pcb_limit') : null,
                ];

            case 'dual_banner':
                return [
                    'left' => [
                        'image_url' => $request->input('left_image_url'),
                        'link_url'  => $request->input('left_link_url'),
                        'alt'       => $request->input('left_alt'),
                    ],
                    'right' => [
                        'image_url' => $request->input('right_image_url'),
                        'link_url'  => $request->input('right_link_url'),
                        'alt'       => $request->input('right_alt'),
                    ],
                ];

            case 'category_grid':
                return [
                    'category_ids' => array_values(array_filter((array) $request->input('category_ids', []))),
                ];

            case 'brand_carousel':
                return [];

            case 'html_block':
                return [
                    'html' => $request->input('html'),
                ];

            case 'faq':
                // Las preguntas viven en cada producto (products.faqs); la
                // sección solo aporta título y texto descriptivo.
                return [
                    'description' => $request->input('faq_description') ?: null,
                ];

            case 'hero_slider':
            default:
                return null;
        }
    }

    /**
     * Reglas comunes de store/update. Los tipos y fuentes permitidos
     * dependen de la página: hero_slider solo existe en el home y las
     * fuentes related_* solo en la página de producto.
     */
    protected function validateSection(Request $request): void
    {
        $page = $request->input('page');

        $allowedTypes = match ($page) {
            'product'    => $this->productPageTypes,
            'collection' => $this->collectionPageTypes,
            default      => $this->types,
        };

        // Las fuentes relativas (related_*) solo tienen sentido con un
        // producto en contexto; colecciones usa las fuentes fijas.
        $allowedSources = $page === 'product' ? $this->productPageSources : $this->sources;

        $request->validate([
            'page'       => 'required|string|in:home,product,collection',
            'type'       => 'required|string|in:' . implode(',', $allowedTypes),
            'source'     => 'nullable|string|in:' . implode(',', $allowedSources),
            'pcb_source' => 'nullable|string|in:' . implode(',', $allowedSources),
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);
    }

    public function store(Request $request)
    {
        $this->validateSection($request);

        $section = new HomeSection();
        $section->type       = $request->type;
        $section->page       = $request->page;
        $section->title      = $request->title ?? null;
        $section->config     = $this->buildConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? 0;
        $section->is_active  = $request->boolean('is_active', true);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function edit(string $id)
    {
        $section = HomeSection::findOrFail($id);
        return response()->json($section);
    }

    public function update(Request $request, string $id)
    {
        $section = HomeSection::findOrFail($id);

        $this->validateSection($request);

        $section->type       = $request->type;
        $section->page       = $request->page;
        $section->title      = $request->title ?? null;
        $section->config     = $this->buildConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? 0;
        $section->is_active  = $request->boolean('is_active', true);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function destroy(string $id)
    {
        $section = HomeSection::findOrFail($id);
        $section->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:home_sections,id',
        ]);

        $sections = HomeSection::whereIn('id', $request->order)
            ->get()
            ->keyBy('id');

        if ($sections->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con las secciones existentes.',
            ], 422);
        }

        // Cada pestaña (home/product) reordena solo sus propias secciones;
        // un payload que mezcle páginas pisaría el orden de la otra pestaña.
        if ($sections->pluck('page')->unique()->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido mezcla secciones de páginas distintas.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $sectionId) {
            $sections[$sectionId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    public function slidesPage(string $homeSection)
    {
        $section = HomeSection::with('slides')->findOrFail($homeSection);

        return view('admin.home-sections.slides', compact('section'));
    }

    public function slides(string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slides = $section->slides()->get();

        return response()->json($slides);
    }

    public function storeSlide(Request $request, string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);

        $request->validate([
            'image_url'       => 'required|string|max:255',
            'badge_text'      => 'nullable|string|max:120',
            'title'           => 'required|string|max:255',
            'title_highlight' => 'nullable|string|max:120',
            'description'     => 'nullable|string',
            'link_url'        => 'nullable|string|max:255',
            'sort_order'      => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $slide = new HomeSectionSlide();
        $slide->home_section_id = $section->id;
        $slide->image_url       = $request->image_url;
        $slide->badge_text      = $request->badge_text ?? null;
        $slide->title           = $request->title;
        $slide->title_highlight = $request->title_highlight ?? null;
        $slide->description     = $request->description ?? null;
        $slide->link_url        = $request->link_url ?? null;
        $slide->sort_order      = $request->sort_order ?? ($section->slides()->count());
        $slide->is_active       = $request->boolean('is_active', true);
        $slide->save();

        return response()->json([
            'success' => true,
            'slide'   => $slide,
        ]);
    }

    public function updateSlide(Request $request, string $homeSection, string $slide)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slideModel = HomeSectionSlide::where('home_section_id', $section->id)->findOrFail($slide);

        $request->validate([
            'image_url'       => 'required|string|max:255',
            'badge_text'      => 'nullable|string|max:120',
            'title'           => 'required|string|max:255',
            'title_highlight' => 'nullable|string|max:120',
            'description'     => 'nullable|string',
            'link_url'        => 'nullable|string|max:255',
            'sort_order'      => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $slideModel->image_url       = $request->image_url;
        $slideModel->badge_text      = $request->badge_text ?? null;
        $slideModel->title           = $request->title;
        $slideModel->title_highlight = $request->title_highlight ?? null;
        $slideModel->description     = $request->description ?? null;
        $slideModel->link_url        = $request->link_url ?? null;
        $slideModel->sort_order      = $request->sort_order ?? $slideModel->sort_order;
        $slideModel->is_active       = $request->boolean('is_active', true);
        $slideModel->save();

        return response()->json([
            'success' => true,
            'slide'   => $slideModel,
        ]);
    }

    public function destroySlide(string $homeSection, string $slide)
    {
        $section = HomeSection::findOrFail($homeSection);
        $slideModel = HomeSectionSlide::where('home_section_id', $section->id)->findOrFail($slide);
        $slideModel->delete();

        return response()->json(['success' => true]);
    }

    public function reorderSlides(Request $request, string $homeSection)
    {
        $section = HomeSection::findOrFail($homeSection);

        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:home_section_slides,id',
        ]);

        $slides = HomeSectionSlide::whereIn('id', $request->order)
            ->where('home_section_id', $section->id)
            ->get()
            ->keyBy('id');

        if ($slides->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con los slides de esta sección.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $slideId) {
            $slides[$slideId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }
}
