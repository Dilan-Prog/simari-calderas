<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use App\Models\ServicePage;
use App\Models\ServicePageImage;
use App\Models\ServicePageReview;
use App\Models\ServiceSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServicePageController extends Controller
{
    // Mismo set de tipos que HomeSectionController::$productPageTypes /
    // $collectionPageTypes (sin hero_slider, que es exclusivo del Home), más
    // 7 tipos nuevos exclusivos de Servicios (rediseño 2026-09).
    protected array $sectionTypes = [
        'banner', 'dual_banner', 'product_carousel', 'product_carousel_banner',
        'category_grid', 'brand_carousel', 'html_block', 'faq',
        'rich_header', 'content_tabs', 'benefits_grid', 'process_steps',
        'gallery_carousel', 'rating_reviews', 'cta_final',
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

    /**
     * "+ Nuevo Servicio" ahora crea un borrador mínimo (nombre/slug
     * provisionales, inactivo) y manda directo al editor en vivo — ahí el
     * admin captura nombre/slug reales en el panel "Información general"
     * más los bloques, todo en una sola pantalla. El formulario clásico
     * (create()/store() arriba) se conserva sin uso directo por si hace
     * falta un alta rápida sin editor en vivo en el futuro.
     */
    public function quickCreate()
    {
        $n = ServicePage::count() + 1;
        $slugBase = 'nuevo-servicio-' . $n;
        $slug = $slugBase;
        $i = 1;
        while (ServicePage::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . (++$i);
        }

        $servicePage = ServicePage::create([
            'name'       => 'Nuevo servicio ' . $n,
            'slug'       => $slug,
            'currency'   => 'MXN',
            'sort_order' => 0,
            'is_active'  => false,
        ]);

        return redirect()->route('admin.service-pages.live-editor', $servicePage);
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
        $servicePage->load(['sections', 'images', 'reviews']);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $collections = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $headingOutline = $this->buildHeadingOutline($servicePage);

        return view('admin.service-pages.edit', compact(
            'servicePage', 'categories', 'brands', 'collections', 'headingOutline'
        ));
    }

    /**
     * Árbol H1/H2/H3 derivado de las secciones REALES de la página, para el
     * panel "Estructura semántica detectada" de la pestaña SEO — puramente
     * informativo/validador, no bloquea el guardado.
     */
    protected function buildHeadingOutline(ServicePage $servicePage): array
    {
        $outline = [];
        $hasRichHeader = false;

        foreach ($servicePage->sections->where('is_active', true) as $section) {
            switch ($section->type) {
                case 'rich_header':
                    $outline[] = ['level' => 'H1', 'text' => $servicePage->name, 'note' => 'único ✓'];
                    $hasRichHeader = true;
                    break;
                case 'content_tabs':
                    foreach ($section->config['tabs'] ?? [] as $tab) {
                        if (!empty($tab['label'])) {
                            $outline[] = ['level' => 'H2', 'text' => $tab['label']];
                        }
                    }
                    break;
                case 'benefits_grid':
                    $count = count($section->config['items'] ?? []);
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'Qué ganas con el servicio'];
                    if ($count) {
                        $outline[] = ['level' => 'H3', 'text' => $count . ' beneficios'];
                    }
                    break;
                case 'process_steps':
                    $count = count($section->config['steps'] ?? []);
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'Cómo trabajamos'];
                    if ($count) {
                        $outline[] = ['level' => 'H3', 'text' => $count . ' pasos'];
                    }
                    break;
                case 'gallery_carousel':
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'Galería'];
                    break;
                case 'rating_reviews':
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'Lo que dicen nuestros clientes'];
                    break;
                case 'faq':
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'Preguntas frecuentes', 'note' => 'FAQPage ✓'];
                    break;
                case 'cta_final':
                    $outline[] = ['level' => 'H2', 'text' => $section->title ?: 'CTA de cierre'];
                    break;
            }
        }

        if (!$hasRichHeader) {
            array_unshift($outline, [
                'level' => 'H1', 'text' => $servicePage->name, 'note' => '⚠ sin bloque Encabezado enriquecido',
            ]);
        }

        return $outline;
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
            // Estadísticas de marketing (pestaña Rating y reseñas) — nunca
            // alimentan el JSON-LD, solo el copy visual. Ver migración
            // add_rating_stats_to_service_pages_table.
            'rating_average_displayed'   => 'nullable|numeric|min:0|max:5',
            'rating_total_rated'         => 'nullable|integer|min:0',
            'rating_recommend_percent'   => 'nullable|numeric|min:0|max:100',
            'rating_punctuality_average' => 'nullable|numeric|min:0|max:5',
            'rating_recurring_clients'   => 'nullable|integer|min:0',
            'rating_since_year'          => 'nullable|integer|min:2000|max:2100',
            'rating_distribution'        => 'nullable|array',
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
        $this->fillRatingStats($servicePage, $request);
        $servicePage->save();

        return redirect()->route('admin.service-pages.edit', $servicePage)
            ->with('success', 'Servicio actualizado.');
    }

    /**
     * Estadísticas de marketing editables a mano (caja "Promedio mostrado"
     * de la pestaña Rating y reseñas) — ver nota en la migración sobre por
     * qué nunca deben alimentar el JSON-LD.
     */
    protected function fillRatingStats(ServicePage $servicePage, Request $request): void
    {
        $servicePage->rating_average_displayed = $request->input('rating_average_displayed') ?: null;
        $servicePage->rating_total_rated = $request->input('rating_total_rated') ?: null;
        $servicePage->rating_recommend_percent = $request->input('rating_recommend_percent') ?: null;
        $servicePage->rating_punctuality_average = $request->input('rating_punctuality_average') ?: null;
        $servicePage->rating_recurring_clients = $request->input('rating_recurring_clients') ?: null;
        $servicePage->rating_since_year = $request->input('rating_since_year') ?: null;

        $distribution = collect((array) $request->input('rating_distribution', []))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->all();
        $servicePage->rating_distribution = $distribution ?: null;
    }

    /**
     * Guardado AJAX de los campos "Información general" (Nombre/Slug/
     * Descripción corta/Precio/SEO) desde el editor en vivo — deliberadamente
     * separado de update() para no arriesgar pisar las estadísticas de
     * rating, las FAQs o las imágenes si este panel se guarda solo, sin el
     * resto del formulario clásico.
     */
    public function updateGeneral(Request $request, ServicePage $servicePage)
    {
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $validated = $request->validate([
            'name'              => 'required|string|max:180',
            'slug'              => 'required|string|max:255|unique:service_pages,slug,' . $servicePage->id,
            'short_description' => 'nullable|string',
            'price'             => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
            'seo_title'         => 'nullable|string|max:160',
            'seo_description'   => 'nullable|string|max:500',
            'is_active'         => 'nullable|boolean',
        ]);

        $servicePage->name = $validated['name'];
        $servicePage->slug = $validated['slug'];
        $servicePage->short_description = $validated['short_description'] ?: null;
        $servicePage->price = $validated['price'] !== null && $validated['price'] !== '' ? $validated['price'] : null;
        $servicePage->currency = $validated['currency'] ?: 'MXN';
        $servicePage->seo_title = $validated['seo_title'] ?: null;
        $servicePage->seo_description = $validated['seo_description'] ?: null;
        $servicePage->is_active = $request->boolean('is_active');
        $servicePage->save();

        return response()->json(['success' => true, 'servicePage' => [
            'name' => $servicePage->name,
            'slug' => $servicePage->slug,
            'short_description' => $servicePage->short_description,
            'price' => $servicePage->price,
            'currency' => $servicePage->currency,
            'seo_title' => $servicePage->seo_title,
            'seo_description' => $servicePage->seo_description,
            'is_active' => $servicePage->is_active,
        ]]);
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

            // ── Tipos nuevos (rediseño 2026-09) ─────────────────────────────

            case 'rich_header':
                // Sin ningún campo de teléfono a propósito — el CTA siempre
                // arma un link wa.me, nunca tel:. Ver rich-header.blade.php.
                $badges = array_slice(array_values(array_filter(array_map('trim',
                    explode('·', (string) $request->input('rh_badges', ''))
                ))), 0, 3);

                return [
                    'badges'                => $badges,
                    'whatsapp_text'         => $request->input('rh_whatsapp_text') ?: 'Cotizar por WhatsApp',
                    'meta_lines'            => array_values(array_filter((array) $request->input('rh_meta_lines', []))),
                    'background_image_ids'  => array_values(array_filter(array_map('intval', (array) $request->input('rh_background_image_ids', [])))),
                    // Texto libre (no numérico) para permitir formatos tipo
                    // "$8,500 MXN + IVA" — independiente de service_pages.price.
                    'price_label'           => $request->input('rh_price_label') ?: null,
                ];

            case 'content_tabs':
                return [
                    'tabs' => collect((array) $request->input('ct_tabs', []))
                        ->map(fn ($t) => [
                            'label'    => trim($t['label'] ?? ''),
                            'subtitle' => trim($t['subtitle'] ?? ''),
                            'body'     => trim($t['body'] ?? ''),
                            'bullets'  => array_values(array_filter((array) ($t['bullets'] ?? []))),
                            'image_id' => $t['image_id'] ?: null,
                        ])
                        ->filter(fn ($t) => $t['label'] !== '')
                        ->values()
                        ->all(),
                ];

            case 'benefits_grid':
                return [
                    'items' => collect((array) $request->input('bg_items', []))
                        ->map(fn ($i) => [
                            'figure'      => trim($i['figure'] ?? ''),
                            'title'       => trim($i['title'] ?? ''),
                            'description' => trim($i['description'] ?? ''),
                        ])
                        ->filter(fn ($i) => $i['title'] !== '')
                        ->values()
                        ->all(),
                ];

            case 'process_steps':
                return [
                    'steps' => collect((array) $request->input('ps_steps', []))
                        ->map(fn ($s) => [
                            'title'       => trim($s['title'] ?? ''),
                            'description' => trim($s['description'] ?? ''),
                            'duration'    => trim($s['duration'] ?? ''),
                        ])
                        ->filter(fn ($s) => $s['title'] !== '')
                        ->values()
                        ->all(),
                ];

            case 'gallery_carousel':
                return [
                    'image_ids' => array_values(array_filter(array_map('intval', (array) $request->input('gc_image_ids', [])))),
                ];

            case 'rating_reviews':
                return [
                    'description'      => $request->input('rr_description') ?: null,
                    'reviews_per_page' => (int) ($request->input('rr_reviews_per_page') ?: 3),
                ];

            case 'cta_final':
                return [
                    'headline'            => $request->input('cta_headline') ?: null,
                    'subtext'             => $request->input('cta_subtext') ?: null,
                    'whatsapp_text'       => $request->input('cta_whatsapp_text') ?: 'Cotizar por WhatsApp',
                    'background_image_id' => $request->input('cta_background_image_id') ?: null,
                ];

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
            'rh_badges'  => 'nullable|string|max:255',
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

    // ── Galería de imágenes (pestaña Multimedia) ────────────────────────────
    // Se agregan vía el picker compartido de media (window.openImagePicker),
    // no un uploader propio nuevo — ver resources/js/admin/image-picker.js.
    // Al terminar de guardar, sincroniza cover_image_url con la portada
    // (sort_order = 0) para no romper el accessor/OG fallback ya existentes.

    public function storeImage(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'image_url' => 'required|string|max:255',
            'alt_text'  => 'nullable|string|max:255',
        ]);

        $image = $servicePage->images()->create([
            'image_url'  => $request->image_url,
            'alt_text'   => $request->alt_text ?: null,
            'sort_order' => $servicePage->images()->count(),
        ]);

        $this->syncCoverImage($servicePage);

        return response()->json(['success' => true, 'image' => $image]);
    }

    public function updateImage(Request $request, ServicePage $servicePage, ServicePageImage $image)
    {
        abort_unless($image->service_page_id === $servicePage->id, 404);

        $request->validate(['alt_text' => 'nullable|string|max:255']);

        $image->update(['alt_text' => $request->alt_text ?: null]);

        return response()->json(['success' => true, 'image' => $image]);
    }

    public function destroyImage(ServicePage $servicePage, ServicePageImage $image)
    {
        abort_unless($image->service_page_id === $servicePage->id, 404);

        $image->delete();

        // Reindexa sort_order para que la portada siga siendo la 0.
        $servicePage->images()->orderBy('sort_order')->get()->values()
            ->each(fn ($img, $i) => $img->sort_order === $i ? null : $img->update(['sort_order' => $i]));

        $this->syncCoverImage($servicePage);

        return response()->json(['success' => true]);
    }

    public function reorderImages(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:service_page_images,id',
        ]);

        $images = ServicePageImage::whereIn('id', $request->order)
            ->where('service_page_id', $servicePage->id)
            ->get()
            ->keyBy('id');

        if ($images->count() !== count($request->order)) {
            return response()->json(['success' => false, 'message' => 'El orden recibido no coincide con las imágenes de este servicio.'], 422);
        }

        foreach (array_values($request->order) as $i => $imageId) {
            $images[$imageId]->update(['sort_order' => $i]);
        }

        $this->syncCoverImage($servicePage);

        return response()->json(['success' => true]);
    }

    protected function syncCoverImage(ServicePage $servicePage): void
    {
        $first = $servicePage->images()->orderBy('sort_order')->first();
        $servicePage->cover_image_url = $first?->image_url;
        $servicePage->save();
    }

    // ── Reseñas (pestaña Rating y reseñas) ──────────────────────────────────
    // Contenido curado por el staff (igual criterio que las FAQs) — el sitio
    // público no recibe reseñas de clientes directamente.

    protected function validateReview(Request $request): array
    {
        return $request->validate([
            'customer_name'           => 'required|string|max:255',
            'customer_role'           => 'nullable|string|max:255',
            'customer_company'        => 'nullable|string|max:255',
            'customer_city'           => 'nullable|string|max:255',
            'customer_state'          => 'nullable|string|max:255',
            'review_date'             => 'nullable|date',
            'rating'                  => 'required|integer|min:1|max:5',
            'comment'                 => 'required|string|max:240',
            'categories'              => 'nullable|array',
            'categories.*'            => Rule::in(array_keys(ServicePageReview::CATEGORIES)),
            'is_verified'             => 'nullable|boolean',
            'photo_urls'              => 'nullable|array',
            'photo_urls.*'            => 'string|max:255',
            'business_response'       => 'nullable|string',
            'business_response_date'  => 'nullable|date',
            'is_visible'              => 'nullable|boolean',
        ]);
    }

    public function storeReview(Request $request, ServicePage $servicePage)
    {
        $data = $this->validateReview($request);
        $data['is_verified'] = $request->boolean('is_verified');
        $data['is_visible'] = $request->boolean('is_visible', true);
        $data['sort_order'] = $servicePage->reviews()->count();

        $review = $servicePage->reviews()->create($data);

        return response()->json(['success' => true, 'review' => $review]);
    }

    public function editReview(ServicePage $servicePage, ServicePageReview $review)
    {
        abort_unless($review->service_page_id === $servicePage->id, 404);

        return response()->json($review);
    }

    public function updateReview(Request $request, ServicePage $servicePage, ServicePageReview $review)
    {
        abort_unless($review->service_page_id === $servicePage->id, 404);

        $data = $this->validateReview($request);
        $data['is_verified'] = $request->boolean('is_verified');
        $data['is_visible'] = $request->boolean('is_visible', true);

        $review->update($data);

        return response()->json(['success' => true, 'review' => $review]);
    }

    public function destroyReview(ServicePage $servicePage, ServicePageReview $review)
    {
        abort_unless($review->service_page_id === $servicePage->id, 404);

        $review->delete();

        return response()->json(['success' => true]);
    }

    public function reorderReviews(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:service_page_reviews,id',
        ]);

        $reviews = ServicePageReview::whereIn('id', $request->order)
            ->where('service_page_id', $servicePage->id)
            ->get()
            ->keyBy('id');

        if ($reviews->count() !== count($request->order)) {
            return response()->json(['success' => false, 'message' => 'El orden recibido no coincide con las reseñas de este servicio.'], 422);
        }

        foreach (array_values($request->order) as $i => $reviewId) {
            $reviews[$reviewId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    // ── Editor en vivo (BETA, exclusivo Servicios) ──────────────────────────

    public function liveEditor(ServicePage $servicePage)
    {
        $servicePage->load(['sections', 'images']);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $collections = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.service-pages.live-editor', compact('servicePage', 'categories', 'brands', 'collections'));
    }

    /**
     * Renderiza la página pública REAL con un borrador de secciones que
     * viaja en el body (aún no persistido) — a diferencia del modo "edición"
     * de resources/js/admin/email-template-editor.js (que solo relee lo ya
     * guardado en BD), aquí el usuario edita en memoria antes de guardar, así
     * que el preview necesita reflejar ese estado sin tocar la BD.
     */
    public function liveEditorPreview(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'sections'             => 'nullable|array',
            'sections.*.type'      => 'required_with:sections|string',
            'sections.*.title'     => 'nullable|string',
            'sections.*.config'    => 'nullable|array',
            'sections.*.is_active' => 'nullable|boolean',
        ]);

        $draftSections = collect($request->input('sections', []))->map(function ($s, $i) use ($servicePage) {
            $section = new ServiceSection([
                'type'       => $s['type'],
                'title'      => $s['title'] ?? null,
                'config'     => $s['config'] ?? [],
                'sort_order' => $i,
                'is_active'  => (bool) ($s['is_active'] ?? true),
            ]);
            $section->id = $s['id'] ?? null;
            $section->service_page_id = $servicePage->id;

            return $section;
        })->filter(fn ($s) => $s->is_active)->values();

        $html = view('frontend.shop.service-page.show', [
            'servicePage' => $servicePage,
            'sections'    => $draftSections,
            'previewMode' => true,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function liveEditorSave(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            'sections'          => 'required|array',
            'sections.*.type'   => 'required|string|in:' . implode(',', $this->sectionTypes),
            'sections.*.title'  => 'nullable|string|max:255',
            'sections.*.config' => 'nullable|array',
            'sections.*.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $servicePage) {
            $keepIds = [];

            foreach (array_values($request->input('sections')) as $i => $s) {
                $attrs = [
                    'type'       => $s['type'],
                    'title'      => $s['title'] ?? null,
                    'config'     => $s['config'] ?? [],
                    'sort_order' => $i,
                    'is_active'  => (bool) ($s['is_active'] ?? true),
                ];

                if (!empty($s['id'])) {
                    $section = ServiceSection::where('id', $s['id'])
                        ->where('service_page_id', $servicePage->id)
                        ->first();
                    if ($section) {
                        $section->update($attrs);
                        $keepIds[] = $section->id;
                        continue;
                    }
                }

                $created = $servicePage->sections()->create($attrs);
                $keepIds[] = $created->id;
            }

            // No borra secciones que el editor en vivo no mandó de vuelta —
            // solo actualiza/crea las recibidas, mismo criterio conservador
            // que reorderSections() (nunca borra por omisión).
        });

        return response()->json(['success' => true]);
    }
}
