<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\ProductSectionAssignment;
use App\Models\Products;
use App\Services\ProductBlocks;
use App\Support\LinkTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HomeSectionController extends Controller
{
    protected array $types = [
        'hero_slider', 'banner', 'dual_banner', 'product_carousel',
        'product_carousel_banner', 'category_grid', 'brand_carousel', 'html_block',
        'pool_calculator',
    ];

    // La página de producto (legada, secciones globales) admite todos los
    // tipos menos el slider principal.
    protected array $productPageTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq',
    ];

    // Bloques por producto (plantillas 'product_template' y secciones propias
    // 'product_custom'): los de la página de producto + card_carousel (fichas
    // con imagen/texto/enlace, solo en la zona lateral).
    protected array $productBlockTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq', 'card_carousel',
    ];

    // Las páginas de colección: igual que producto (sin hero_slider, con faq)
    // + pool_calculator (calculadora de dimensionamiento — su fuente de
    // productos es la propia Colección o una lista manual, no "el producto
    // que se está viendo", por eso solo vive aquí y no en $productPageTypes).
    protected array $collectionPageTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq', 'pool_calculator',
    ];

    protected array $sources = [
        'featured', 'new', 'recommended', 'category', 'brand', 'collection', 'manual', 'tag',
    ];

    // Fuentes relativas al producto que se está viendo: solo tienen sentido
    // en la página de producto (y en sus bloques por producto).
    protected array $productPageSources = [
        'featured', 'new', 'recommended', 'category', 'brand', 'collection', 'manual', 'tag',
        'related_category', 'related_brand',
    ];

    protected const MAX_CARDS = 12;

    /** Páginas que son bloques por producto (plantilla o propia). */
    protected static function isProductBlockPage(?string $page): bool
    {
        return in_array($page, [HomeSection::PAGE_PRODUCT_TEMPLATE, HomeSection::PAGE_PRODUCT_CUSTOM], true);
    }

    /** Tipos permitidos por `page` (los consume también el modal en JS). */
    protected function allowedTypesFor(?string $page): array
    {
        return match ($page) {
            'product'                          => $this->productPageTypes,
            'collection'                       => $this->collectionPageTypes,
            HomeSection::PAGE_PRODUCT_TEMPLATE,
            HomeSection::PAGE_PRODUCT_CUSTOM   => $this->productBlockTypes,
            default                            => $this->types,
        };
    }

    /**
     * Categorías activas de TODOS los niveles (categoría, subcategoría,
     * categoría hija) como lista plana en orden de árbol, con `depth` y
     * `label` indentado — para los selectores de "Por Categoría".
     *
     * @return array<int, array{id:int, label:string, depth:int}>
     */
    public static function categoryOptions(): array
    {
        $byParent = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->groupBy(fn ($c) => $c->parent_id ?? 0);

        $out = [];
        $walk = function ($parentId, int $depth) use (&$walk, &$out, $byParent) {
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $out[] = [
                    'id'    => $cat->id,
                    'label' => str_repeat('— ', $depth) . $cat->name,
                    'depth' => $depth,
                ];
                if ($depth < 5) {
                    $walk($cat->id, $depth + 1);
                }
            }
        };
        $walk(0, 0);

        return $out;
    }

    /**
     * Datos que necesita el editor (partial admin.home-sections.partials.
     * editor) para pintar selectores: categorías (raíz y árbol completo),
     * marcas, colecciones y el mapa de tipos permitidos por página.
     */
    public static function editorData(): array
    {
        $self = new static();

        return [
            'categories'      => Category::where('is_active', true)->whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('name')->get(),
            'categoryOptions' => static::categoryOptions(),
            'brands'          => Brand::orderBy('name')->get(),
            'collections'     => Collection::where('is_active', true)->orderBy('name')->get(),
            'pageTypes'       => [
                'home'                             => $self->types,
                'product'                          => $self->productPageTypes,
                'collection'                       => $self->collectionPageTypes,
                HomeSection::PAGE_PRODUCT_TEMPLATE => $self->productBlockTypes,
                HomeSection::PAGE_PRODUCT_CUSTOM   => $self->productBlockTypes,
            ],
        ];
    }

    public function index()
    {
        $page = match (request('pagina')) {
            'producto'    => 'product',
            'colecciones' => 'collection',
            'plantillas'  => HomeSection::PAGE_PRODUCT_TEMPLATE,
            default       => 'home',
        };

        $sections = HomeSection::where('page', $page)->orderBy('sort_order')->orderBy('id')->get();

        $usageCounts = $page === HomeSection::PAGE_PRODUCT_TEMPLATE
            ? ProductBlocks::usageCounts()
            : collect();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'home-sections.index')
            ->value('columns');

        $globalProductSectionsOn = (bool) config('shop.product_global_sections', false);

        return view('admin.home-sections.index', [
            'editorData' => static::editorData(),
        ] + compact('sections', 'page', 'visibleColumns', 'usageCounts', 'globalProductSectionsOn'));
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

    /**
     * Sugerencias de etiquetas para la fuente "Por Etiqueta" (mismo origen
     * que el autocompletado de Productos/Colecciones, aquí bajo el permiso
     * de home-sections).
     */
    public function tagSuggestions(Request $request)
    {
        return app(ProductController::class)->tagSuggestions($request);
    }

    /**
     * Destino de enlace del request: `{$linkKey}[type|id|url|new_tab]`
     * (LinkTarget::normalize). Si el request no trae el campo nuevo se cae al
     * campo legado de URL suelta, para clientes que aún no usan el picker.
     * `link_url` solo se mantiene (compatibilidad) cuando el destino es una
     * URL personalizada; los destinos por entidad se resuelven al renderizar.
     *
     * @return array{0: ?array, 1: ?string}  [link, link_url]
     */
    protected function linkInput(Request $request, string $linkKey, string $legacyKey): array
    {
        $raw = $request->input($linkKey);

        if (is_array($raw)) {
            $link = LinkTarget::normalize($raw);

            return [$link, $link && $link['type'] === 'custom' ? $link['url'] : null];
        }

        $legacy = $request->input($legacyKey);

        return [null, $legacy !== null && $legacy !== '' ? $legacy : null];
    }

    protected function buildConfig(Request $request, string $type): ?array
    {
        switch ($type) {
            case 'product_carousel':
                return [
                    'source'          => $request->input('source', 'featured'),
                    'category_id'     => $request->input('category_id') ?: null,
                    'brand_id'        => $request->input('brand_id') ?: null,
                    'collection_id'   => $request->input('collection_id') ?: null,
                    'tag'             => trim((string) $request->input('tag', '')) ?: null,
                    'eyebrow'         => mb_substr(trim((string) $request->input('eyebrow', '')), 0, 80) ?: null,
                    'exclude_current' => $request->boolean('exclude_current', true),
                    'product_ids'     => array_values(array_filter((array) $request->input('product_ids', []))),
                    'limit'           => $request->input('limit') !== null ? (int) $request->input('limit') : null,
                ];

            case 'banner':
                [$link, $linkUrl] = $this->linkInput($request, 'banner_link', 'banner_link_url');

                return [
                    'image_url' => $request->input('banner_image_url'),
                    'link_url'  => $linkUrl,
                    'link'      => $link,
                    'alt'       => $request->input('banner_alt'),
                ];

            case 'product_carousel_banner':
                [$link, $linkUrl] = $this->linkInput($request, 'pcb_banner_link', 'pcb_banner_link_url');

                return [
                    'banner_image_url' => $request->input('pcb_banner_image_url'),
                    'banner_link_url'  => $linkUrl,
                    'banner_link'      => $link,
                    'banner_alt'       => $request->input('pcb_banner_alt'),
                    'source'           => $request->input('pcb_source', 'featured'),
                    'category_id'      => $request->input('pcb_category_id') ?: null,
                    'brand_id'         => $request->input('pcb_brand_id') ?: null,
                    'collection_id'    => $request->input('pcb_collection_id') ?: null,
                    'tag'              => trim((string) $request->input('pcb_tag', '')) ?: null,
                    'exclude_current'  => $request->boolean('pcb_exclude_current', true),
                    'product_ids'      => array_values(array_filter((array) $request->input('pcb_product_ids', []))),
                    'limit'            => $request->input('pcb_limit') !== null ? (int) $request->input('pcb_limit') : null,
                ];

            case 'dual_banner':
                [$leftLink, $leftUrl]   = $this->linkInput($request, 'left_link', 'left_link_url');
                [$rightLink, $rightUrl] = $this->linkInput($request, 'right_link', 'right_link_url');

                return [
                    'left' => [
                        'image_url' => $request->input('left_image_url'),
                        'link_url'  => $leftUrl,
                        'link'      => $leftLink,
                        'alt'       => $request->input('left_alt'),
                    ],
                    'right' => [
                        'image_url' => $request->input('right_image_url'),
                        'link_url'  => $rightUrl,
                        'link'      => $rightLink,
                        'alt'       => $request->input('right_alt'),
                    ],
                ];

            case 'card_carousel':
                // Fichas {image_url, text, link}; una ficha sin imagen ni
                // texto se descarta (fila vacía del repeater).
                $items = collect((array) $request->input('card_items', []))
                    ->filter(fn ($it) => is_array($it))
                    ->map(function (array $it) {
                        return [
                            'image_url' => trim((string) ($it['image_url'] ?? '')) ?: null,
                            'text'      => trim((string) ($it['text'] ?? '')) ?: null,
                            'link'      => LinkTarget::normalize(is_array($it['link'] ?? null) ? $it['link'] : null),
                        ];
                    })
                    ->filter(fn ($it) => $it['image_url'] || $it['text'])
                    ->take(self::MAX_CARDS)
                    ->values()
                    ->all();

                return ['items' => $items];

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

            case 'pool_calculator':
                // Mismo shape que product_carousel: 'collection' lee los
                // productos manuales de una Colección (Collection::resolveProducts()),
                // 'manual' toma una lista explícita de product_ids. Sin
                // 'featured'/'new'/etc. — esta calculadora siempre recomienda
                // de un conjunto curado corto (2-3 modelos), nunca de todo
                // el catálogo por categoría/marca.
                return [
                    'source'        => $request->input('pc_source', 'collection'),
                    'collection_id' => $request->input('pc_collection_id') ?: null,
                    'product_ids'   => array_values(array_filter((array) $request->input('pc_product_ids', []))),
                ];

            case 'hero_slider':
            default:
                return null;
        }
    }

    /**
     * Reglas comunes de store/update. Los tipos y fuentes permitidos
     * dependen de la página: hero_slider solo existe en el home, las
     * fuentes related_* solo en la página de producto / sus bloques, y
     * card_carousel solo en bloques por producto con zone='sidebar'.
     */
    protected function validateSection(Request $request): void
    {
        $page = $request->input('page');
        $isBlock = static::isProductBlockPage($page);

        $allowedTypes = $this->allowedTypesFor($page);

        // Las fuentes relativas (related_*) solo tienen sentido con un
        // producto en contexto; colecciones/home usan las fuentes fijas.
        $allowedSources = ($page === 'product' || $isBlock) ? $this->productPageSources : $this->sources;

        $validator = Validator::make($request->all(), [
            'page'         => 'required|string|in:home,product,collection,' . HomeSection::PAGE_PRODUCT_TEMPLATE . ',' . HomeSection::PAGE_PRODUCT_CUSTOM,
            'type'         => 'required|string|in:' . implode(',', $allowedTypes),
            'source'       => 'nullable|string|in:' . implode(',', $allowedSources),
            'pcb_source'   => 'nullable|string|in:' . implode(',', $allowedSources),
            'title'        => 'nullable|string|max:255',
            'name'         => ($page === HomeSection::PAGE_PRODUCT_TEMPLATE ? 'required' : 'nullable') . '|string|max:150',
            'zone'         => 'nullable|string|in:' . HomeSection::ZONE_STACK . ',' . HomeSection::ZONE_SIDEBAR,
            'heading_link' => 'nullable|array',
            'product_id'   => 'nullable|integer|exists:products,id',
            'card_items'   => 'nullable|array|max:' . self::MAX_CARDS,
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ], [
            'name.required'  => 'La plantilla necesita un nombre interno.',
            'card_items.max' => 'Máximo ' . self::MAX_CARDS . ' fichas por carrusel.',
            'type.in'        => 'Ese tipo de sección no está disponible en esta página.',
        ]);

        $validator->after(function ($v) use ($request) {
            // Las fichas viven solo en la columna lateral.
            if ($request->input('type') === 'card_carousel'
                && $request->filled('zone')
                && $request->input('zone') !== HomeSection::ZONE_SIDEBAR) {
                $v->errors()->add('zone', 'El carrusel de fichas solo puede ir en la barra lateral.');
            }
        });

        $validator->validate();
    }

    /** Zona final: card_carousel siempre lateral; solo los bloques por producto eligen zona. */
    protected function resolveZone(Request $request): string
    {
        if ($request->input('type') === 'card_carousel') {
            return HomeSection::ZONE_SIDEBAR;
        }

        if (static::isProductBlockPage($request->input('page'))) {
            return $request->input('zone') === HomeSection::ZONE_SIDEBAR
                ? HomeSection::ZONE_SIDEBAR
                : HomeSection::ZONE_STACK;
        }

        return HomeSection::ZONE_STACK;
    }

    /** Copia los campos comunes del request al modelo (sin guardar). */
    protected function fillSection(HomeSection $section, Request $request): void
    {
        $isBlock = static::isProductBlockPage($request->page);

        $section->type         = $request->type;
        $section->page         = $request->page;
        $section->zone         = $this->resolveZone($request);
        $section->name         = $isBlock ? (trim((string) $request->input('name', '')) ?: null) : null;
        $section->title        = $request->title ?? null;
        $section->heading_link = LinkTarget::normalize(is_array($request->input('heading_link')) ? $request->input('heading_link') : null);
        $section->config       = $this->buildConfig($request, $request->type);
        $section->sort_order   = $request->sort_order ?? 0;
        $section->is_active    = $request->boolean('is_active', true);
    }

    public function store(Request $request)
    {
        $this->validateSection($request);

        $assignment = null;

        $section = DB::transaction(function () use ($request, &$assignment) {
            $section = new HomeSection();
            $this->fillSection($section, $request);
            $section->save();

            // Sección propia creada desde la ficha de un producto: queda
            // asignada al final de sus bloques.
            if ($section->page === HomeSection::PAGE_PRODUCT_CUSTOM && $request->filled('product_id')) {
                $productId = (int) $request->input('product_id');
                $assignment = ProductSectionAssignment::create([
                    'product_id'      => $productId,
                    'home_section_id' => $section->id,
                    'sort_order'      => (int) (ProductSectionAssignment::where('product_id', $productId)->max('sort_order') ?? -1) + 1,
                    'is_visible'      => true,
                ]);
            }

            return $section;
        });

        return response()->json([
            'success'    => true,
            'section'    => $section,
            'assignment' => $assignment,
        ]);
    }

    public function edit(string $id)
    {
        $section = HomeSection::findOrFail($id);

        $data = $section->toArray();
        $data['usage_count'] = $section->page === HomeSection::PAGE_PRODUCT_TEMPLATE
            ? $section->productAssignments()->count()
            : null;

        return response()->json($data);
    }

    public function update(Request $request, string $id)
    {
        $section = HomeSection::findOrFail($id);

        $this->validateSection($request);

        $this->fillSection($section, $request);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    /**
     * Elimina una sección. Una plantilla en uso responde 409 {in_use: N}
     * salvo que llegue confirm=1; al confirmar, las asignaciones caen por
     * cascade (FK product_section_assignments.home_section_id).
     */
    public function destroy(Request $request, string $id)
    {
        $section = HomeSection::findOrFail($id);

        if ($section->page === HomeSection::PAGE_PRODUCT_TEMPLATE) {
            $inUse = $section->productAssignments()->count();

            if ($inUse > 0 && !$request->boolean('confirm')) {
                return response()->json([
                    'success' => false,
                    'in_use'  => $inUse,
                    'message' => "Esta plantilla se usa en {$inUse} producto(s). Confirma para eliminarla de todos.",
                ], 409);
            }
        }

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
