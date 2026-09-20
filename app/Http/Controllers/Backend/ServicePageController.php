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
        'gallery_carousel', 'rating_reviews', 'cta_final', 'button', 'table_block',
    ];

    /**
     * Igual que CategoryController::index(): la tabla es un árbol completo
     * (hub → categoría → servicio, hasta 3 niveles), no una lista plana
     * paginada -- el filtro de búsqueda/nivel/estado es 100% client-side
     * sobre las filas ya renderizadas (ver admin.service-pages.index).
     */
    public function index(Request $request)
    {
        $allForTree = ServicePage::with(['children.children'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'service-pages.index')
            ->value('columns');

        return view('admin.service-pages.index', [
            'allForTree' => $allForTree,
            'total' => ServicePage::count(),
            'visibleColumns' => $visibleColumns,
        ]);
    }

    /**
     * "+ Nuevo Servicio" crea un borrador mínimo (nombre/slug provisionales,
     * inactivo) y manda directo al editor en vivo — ahí el admin captura
     * nombre/slug reales en el panel "Información general" más los bloques,
     * todo en una sola pantalla. El formulario clásico de creación (con su
     * propio formulario completo antes de tener bloques) se retiró junto
     * con el de edición -- ver nota arriba de updateGeneral().
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
     * Descripción corta/Precio/SEO/canónica/rating/FAQ) desde el editor en
     * vivo. El formulario clásico de crear/editar (create()/store()/edit()/
     * update(), y el sub-CRUD de secciones que traía) se retiró por completo
     * (2026-09): el editor en vivo ya cubre todo lo que aquel hacía --
     * Galería/Reseñas ganaron su propio panel ahí, y este método cubre el
     * resto. Solo quedan del controlador clásico: index(), destroy() (borrar
     * la página completa, acción del listado) y quickCreate().
     */
    public function updateGeneral(Request $request, ServicePage $servicePage)
    {
        $request->merge(['slug' => Str::slug((string) $request->slug)]);

        $parentId = $request->input('parent_id') ?: null;

        $validated = $request->validate([
            'name'              => 'required|string|max:180',
            'slug'              => [
                'required', 'string', 'max:255',
                Rule::unique('service_pages', 'slug')
                    ->where(fn ($q) => $parentId ? $q->where('parent_id', $parentId) : $q->whereNull('parent_id'))
                    ->ignore($servicePage->id),
            ],
            'page_type'         => 'required|string|in:' . implode(',', [
                ServicePage::TYPE_HUB, ServicePage::TYPE_CATEGORY, ServicePage::TYPE_SERVICE,
            ]),
            // Solo una categoría puede ser padre de un servicio: publicPath()
            // únicamente anida la URL cuando el padre es TYPE_CATEGORY (un
            // hub nunca lo es), así que permitir el hub aquí dejaba crear un
            // servicio con padre real pero sin ninguna ruta pública que lo
            // sirva (showLegacy() exige parent_id NULL, y la nidificada
            // exige un padre categoría) — página huérfana e inalcanzable.
            'parent_id'         => [
                'nullable', 'integer',
                Rule::exists('service_pages', 'id')->where(
                    fn ($q) => $q->where('page_type', ServicePage::TYPE_CATEGORY)
                ),
            ],
            'sort_order'        => 'nullable|integer|min:0',
            'short_description' => 'nullable|string',
            'price'             => 'nullable|numeric|min:0',
            'currency'          => 'nullable|string|max:10',
            'show_price'        => 'nullable|boolean',
            'background_color'  => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'seo_title'         => 'nullable|string|max:160',
            'seo_description'   => 'nullable|string|max:500',
            'canonical_url'     => 'nullable|url|max:255',
            'is_active'         => 'nullable|boolean',
            'faq_items'         => 'nullable|array',
            // Estadísticas de marketing (panel "Información general" del
            // editor en vivo) — ver fillRatingStats().
            'rating_average_displayed'   => 'nullable|numeric|min:0|max:5',
            'rating_total_rated'         => 'nullable|integer|min:0',
            'rating_recommend_percent'   => 'nullable|numeric|min:0|max:100',
            'rating_punctuality_average' => 'nullable|numeric|min:0|max:5',
            'rating_recurring_clients'   => 'nullable|integer|min:0',
            'rating_since_year'          => 'nullable|integer|min:2000|max:2100',
            'rating_distribution'        => 'nullable|array',
        ]);

        // Un hub o una categoría nunca tienen padre — la jerarquía es de
        // máximo 2 niveles bajo /servicios/ y publicPath() ya ignora
        // parent_id para ambos tipos; dejarlo sin forzar aquí dejaba un
        // parent_id "fantasma" que rompía ancestors()/breadcrumb aunque la
        // URL pública fuera correcta. También evita que una página sea su
        // propio ancestro (ciclos al reasignar parent_id).
        if (in_array($validated['page_type'], [ServicePage::TYPE_HUB, ServicePage::TYPE_CATEGORY], true)) {
            $parentId = null;
        }

        // Solo puede existir un hub: la vista pública (/servicios) toma el
        // primero que encuentra con ->first() sin ningún criterio de
        // desempate, así que un segundo hub dejaría en ambigüedad silenciosa
        // cuál de los dos se muestra.
        if ($validated['page_type'] === ServicePage::TYPE_HUB) {
            $otherHubExists = ServicePage::where('page_type', ServicePage::TYPE_HUB)
                ->where('id', '!=', $servicePage->id)
                ->exists();
            if ($otherHubExists) {
                return response()->json(['success' => false, 'errors' => [
                    'page_type' => ['Ya existe un hub de Servicios. Solo puede haber uno — cambia el tipo de la página existente antes de crear otro.'],
                ]], 422);
            }
        }

        // Llegados aquí, $parentId solo puede seguir poblado si page_type es
        // 'service' — hub/category ya lo forzaron a null arriba.
        if ($parentId) {
            if ((int) $parentId === $servicePage->id) {
                return response()->json(['success' => false, 'errors' => [
                    'parent_id' => ['Una página no puede ser su propio padre.'],
                ]], 422);
            }
            $ancestorIds = collect(ServicePage::find($parentId)?->ancestors() ?? [])->pluck('id')->push($parentId);
            if ($ancestorIds->contains($servicePage->id)) {
                return response()->json(['success' => false, 'errors' => [
                    'parent_id' => ['No puedes elegir un descendiente de esta página como su padre.'],
                ]], 422);
            }
        }

        $servicePage->name = $validated['name'];
        $servicePage->slug = $validated['slug'];
        $servicePage->page_type = $validated['page_type'];
        $servicePage->parent_id = $parentId;
        $servicePage->sort_order = $validated['sort_order'] !== null ? (int) $validated['sort_order'] : 0;
        $servicePage->short_description = $validated['short_description'] ?: null;
        $servicePage->price = $validated['price'] !== null && $validated['price'] !== '' ? $validated['price'] : null;
        $servicePage->currency = $validated['currency'] ?: 'MXN';
        $servicePage->show_price = $request->boolean('show_price', true);
        $servicePage->background_color = $validated['background_color'] ?: null;
        $servicePage->seo_title = $validated['seo_title'] ?: null;
        $servicePage->seo_description = $validated['seo_description'] ?: null;
        // Igual que Products::canonical_url: solo se guarda si el checkbox
        // "Es la URL Canónica de este servicio" está DESMARCADO (is_canonical
        // en el payload) -- si está marcado, esta página es su propia
        // canónica y el campo se limpia aunque el input tuviera texto viejo.
        $servicePage->canonical_url = $request->boolean('is_canonical', true) ? null : ($validated['canonical_url'] ?: null);
        $servicePage->is_active = $request->boolean('is_active');
        // Guarda por 'has()', no siempre: si algún llamado futuro a este
        // endpoint omite faq_items, no debe borrar las FAQs existentes por
        // accidente.
        if ($request->has('faq_items')) {
            $servicePage->faqs = $this->mapFaqItems($request) ?: null;
        }
        // Estas cifras nunca alimentan el JSON-LD, solo el copy visual del
        // bloque "rating_reviews" / futuro AggregateRating manual.
        $this->fillRatingStats($servicePage, $request);
        $servicePage->save();

        return response()->json(['success' => true, 'servicePage' => [
            'name' => $servicePage->name,
            'slug' => $servicePage->slug,
            'page_type' => $servicePage->page_type,
            'parent_id' => $servicePage->parent_id,
            'sort_order' => $servicePage->sort_order,
            'public_path' => $servicePage->publicPath(),
            'short_description' => $servicePage->short_description,
            'price' => $servicePage->price,
            'currency' => $servicePage->currency,
            'show_price' => $servicePage->show_price,
            'background_color' => $servicePage->background_color,
            'seo_title' => $servicePage->seo_title,
            'seo_description' => $servicePage->seo_description,
            'canonical_url' => $servicePage->canonical_url,
            'is_active' => $servicePage->is_active,
            'faqs' => $servicePage->faqs,
            'rating_average_displayed' => $servicePage->rating_average_displayed,
            'rating_total_rated' => $servicePage->rating_total_rated,
            'rating_recommend_percent' => $servicePage->rating_recommend_percent,
            'rating_punctuality_average' => $servicePage->rating_punctuality_average,
            'rating_recurring_clients' => $servicePage->rating_recurring_clients,
            'rating_since_year' => $servicePage->rating_since_year,
            'rating_distribution' => $servicePage->rating_distribution,
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
    protected function mapFaqItems(Request $request): array
    {
        return collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();
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

        // append('url'): `url` es un accessor (getUrlAttribute), no está en
        // $appends del modelo, así que sin esto el JSON serializado NO trae
        // esa clave -- el editor en vivo (y la galería clásica) necesitan la
        // URL resuelta (UploadPath::url()) para pintar el <img> recién
        // agregado sin recargar la página.
        return response()->json(['success' => true, 'image' => $image->append('url')]);
    }

    public function updateImage(Request $request, ServicePage $servicePage, ServicePageImage $image)
    {
        abort_unless($image->service_page_id === $servicePage->id, 404);

        $request->validate(['alt_text' => 'nullable|string|max:255']);

        $image->update(['alt_text' => $request->alt_text ?: null]);

        return response()->json(['success' => true, 'image' => $image->append('url')]);
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
        $servicePage->load(['sections', 'images', 'reviews']);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $collections = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Padres elegibles para el selector "Página padre" del panel
        // Información general: solo categorías son padres válidos de un
        // servicio (un hub nunca nidifica la URL vía publicPath(), así que
        // ofrecerlo aquí producía páginas huérfanas — ver la validación
        // equivalente en updateGeneral()).
        $eligibleParents = ServicePage::where('page_type', ServicePage::TYPE_CATEGORY)
            ->where('id', '!=', $servicePage->id)
            ->orderBy('name')
            ->get(['id', 'name', 'page_type']);

        return view('admin.service-pages.live-editor', compact(
            'servicePage', 'categories', 'brands', 'collections', 'eligibleParents'
        ));
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

        $children = $servicePage->activeChildren()->orderBy('name')->get();

        $html = view('frontend.shop.service-page.show', [
            'servicePage' => $servicePage,
            'sections'    => $draftSections,
            'previewMode' => true,
            'ancestors'   => $servicePage->ancestors(),
            'children'    => $children,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function liveEditorSave(Request $request, ServicePage $servicePage)
    {
        $request->validate([
            // 'present' (no 'required'): un array vacío es un estado válido
            // — una página recién creada sin bloques, o el admin borrando el
            // último bloque — y 'required' lo rechaza (Laravel trata [] como
            // "vacío"), lo que antes producía un 422 silencioso resuelto con
            // un alert() nativo que bloqueaba el navegador.
            'sections'          => 'present|array',
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
