<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use App\Models\ServicePage;
use App\Models\ServiceSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServicePageController extends Controller
{
    // Mismo set de tipos que HomeSectionController::$productPageTypes /
    // $collectionPageTypes (sin hero_slider, que es exclusivo del Home).
    protected array $sectionTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq',
    ];

    protected array $sources = [
        'featured', 'new', 'recommended', 'category', 'brand', 'collection', 'manual',
    ];

    public function index(Request $request)
    {
        $servicePages = ServicePage::orderBy('sort_order')
            ->orderBy('name')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->paginate(15)
            ->withQueryString();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'service-pages.index')
            ->value('columns');

        return view('admin.service-pages.index', compact('servicePages', 'visibleColumns'));
    }

    public function create()
    {
        return view('admin.service-pages.create');
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $request->validate([
            'name'              => 'required|string|max:180',
            'slug'              => 'required|string|max:255|unique:service_pages,slug',
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'price'             => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
            'cover_image_url'   => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
            'sort_order'        => 'nullable|integer|min:0',
            'seo_title'         => 'nullable|string|max:160',
            'seo_description'   => 'nullable|string|max:500',
            'og_image_url'      => 'nullable|string|max:255',
            'faq_items'         => 'nullable|array',
        ]);

        $servicePage = new ServicePage();
        $servicePage->name = $request->name;
        $servicePage->slug = $request->slug;
        $servicePage->short_description = $request->short_description ?: null;
        $servicePage->description = $request->description ?: null;
        $servicePage->price = $request->price !== null && $request->price !== '' ? $request->price : null;
        $servicePage->currency = $request->currency ?: 'MXN';
        $servicePage->cover_image_url = $request->cover_image_url ?: null;
        $servicePage->sort_order = $request->sort_order ?? 0;
        $servicePage->is_active = $request->boolean('is_active', true);
        $this->fillSeoAndFaqs($servicePage, $request);
        $servicePage->save();

        return redirect()->route('admin.service-pages.edit', $servicePage)
            ->with('success', 'Servicio creado. Ahora puedes agregar sus secciones.');
    }

    public function edit(ServicePage $servicePage)
    {
        $servicePage->load('sections');
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $collections = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.service-pages.edit', compact('servicePage', 'categories', 'brands', 'collections'));
    }

    public function update(Request $request, ServicePage $servicePage)
    {
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $request->validate([
            'name'              => 'required|string|max:180',
            'slug'              => 'required|string|max:255|unique:service_pages,slug,' . $servicePage->id,
            'short_description' => 'nullable|string',
            'description'       => 'nullable|string',
            'price'             => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
            'cover_image_url'   => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
            'sort_order'        => 'nullable|integer|min:0',
            'seo_title'         => 'nullable|string|max:160',
            'seo_description'   => 'nullable|string|max:500',
            'og_image_url'      => 'nullable|string|max:255',
            'faq_items'         => 'nullable|array',
        ]);

        $servicePage->name = $request->name;
        $servicePage->slug = $request->slug;
        $servicePage->short_description = $request->short_description ?: null;
        $servicePage->description = $request->description ?: null;
        $servicePage->price = $request->price !== null && $request->price !== '' ? $request->price : null;
        $servicePage->currency = $request->currency ?: 'MXN';
        $servicePage->cover_image_url = $request->cover_image_url ?: null;
        $servicePage->sort_order = $request->sort_order ?? 0;
        $servicePage->is_active = $request->boolean('is_active', true);
        $this->fillSeoAndFaqs($servicePage, $request);
        $servicePage->save();

        return redirect()->route('admin.service-pages.edit', $servicePage)
            ->with('success', 'Servicio actualizado.');
    }

    public function destroy(ServicePage $servicePage)
    {
        $servicePage->delete();

        return redirect()->route('admin.service-pages.index')->with('success', 'Servicio eliminado.');
    }

    /**
     * Mismo patrón de FAQs que Collection/Category: se guardan como
     * array de {question, answer}, descartando pares incompletos.
     */
    protected function fillSeoAndFaqs(ServicePage $servicePage, Request $request): void
    {
        $servicePage->seo_title = $request->input('seo_title') ?: null;
        $servicePage->seo_description = $request->input('seo_description') ?: null;
        $servicePage->og_image_url = $request->input('og_image_url') ?: null;

        $faqs = collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();

        $servicePage->faqs = $faqs ?: null;
    }

    /**
     * Alimenta el picker "Selección Manual" del carrusel de productos,
     * calcado de HomeSectionController::searchProducts().
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

    // ── Secciones (sub-CRUD scopeado a la instancia) ────────────────────────

    protected function buildSectionConfig(Request $request, string $type): ?array
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
                return ['html' => $request->input('html')];

            case 'faq':
                // Las preguntas viven en service_pages.faqs; la sección solo
                // aporta título y texto descriptivo.
                return ['description' => $request->input('faq_description') ?: null];

            default:
                return null;
        }
    }

    protected function validateSection(Request $request): void
    {
        $request->validate([
            'type'       => 'required|string|in:' . implode(',', $this->sectionTypes),
            'source'     => 'nullable|string|in:' . implode(',', $this->sources),
            'pcb_source' => 'nullable|string|in:' . implode(',', $this->sources),
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);
    }

    public function storeSection(Request $request, ServicePage $servicePage)
    {
        $this->validateSection($request);

        $section = new ServiceSection();
        $section->service_page_id = $servicePage->id;
        $section->type = $request->type;
        $section->title = $request->title ?: null;
        $section->config = $this->buildSectionConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? $servicePage->sections()->count();
        $section->is_active = $request->boolean('is_active', true);
        $section->save();

        return response()->json(['success' => true, 'section' => $section]);
    }

    public function editSection(ServicePage $servicePage, ServiceSection $section)
    {
        abort_unless($section->service_page_id === $servicePage->id, 404);

        return response()->json($section);
    }

    public function updateSection(Request $request, ServicePage $servicePage, ServiceSection $section)
    {
        abort_unless($section->service_page_id === $servicePage->id, 404);

        $this->validateSection($request);

        $section->type = $request->type;
        $section->title = $request->title ?: null;
        $section->config = $this->buildSectionConfig($request, $request->type);
        $section->sort_order = $request->sort_order ?? $section->sort_order;
        $section->is_active = $request->boolean('is_active', true);
        $section->save();

        return response()->json(['success' => true, 'section' => $section]);
    }

    public function destroySection(ServicePage $servicePage, ServiceSection $section)
    {
        abort_unless($section->service_page_id === $servicePage->id, 404);

        $section->delete();

        return response()->json(['success' => true]);
    }

    public function reorderSections(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:service_sections,id',
        ]);

        $sections = ServiceSection::whereIn('id', $request->order)
            ->where('service_page_id', $servicePage->id)
            ->get()
            ->keyBy('id');

        if ($sections->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con las secciones de este servicio.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $sectionId) {
            $sections[$sectionId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }
}
