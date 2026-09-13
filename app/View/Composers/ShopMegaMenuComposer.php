<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Products;
use App\Models\ServicePage;
use Illuminate\View\View;

class ShopMegaMenuComposer
{
    public function compose(View $view): void
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name');
                    }]);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $publishedProducts = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        // Los productos se etiquetan en la categoria hoja (la mas profunda), por lo que
        // agrupar solo por category_id deja vacia la columna de "Productos destacados"
        // cada vez que se pasa el mouse sobre una categoria que tiene subcategorias
        // (ninguna categoria padre tiene productos etiquetados directamente en ella).
        // Por eso aqui agregamos, para cada categoria del arbol (nivel 1, 2 y 3), sus
        // productos propios MAS los de todos sus descendientes, igual que hace
        // Category::idsWithChildren() para la pagina de catalogo, pero aplicado de
        // forma recursiva en cada nivel del mega-menu.
        // toBase() evita que only() se comporte como el de Eloquent\Collection (que
        // asume que cada item es un Model con getKey()); tras groupBy() cada item es
        // en realidad una sub-coleccion de productos, no un Model.
        $productsByCategory = $publishedProducts->groupBy('category_id')->toBase();

        $categoryProducts = collect();

        // Recolecta los ids de una categoria y de todos sus descendientes.
        $collectIds = function ($category) use (&$collectIds) {
            $ids = collect([$category->id]);

            foreach ($category->children as $child) {
                $ids = $ids->merge($collectIds($child));
            }

            return $ids;
        };

        // Recorre el arbol de categorias (padre -> hijos -> nietos) y calcula, para
        // cada una, sus productos + los de toda su rama descendiente.
        $buildCategoryProducts = function ($category) use (&$buildCategoryProducts, &$collectIds, $productsByCategory, &$categoryProducts) {
            $ids = $collectIds($category);

            $categoryProducts->put(
                $category->id,
                $productsByCategory->only($ids)
                    ->flatten(1)
                    ->unique('id')
                    ->sortByDesc('is_featured')
                    ->values()
                    ->take(4)
            );

            foreach ($category->children as $child) {
                $buildCategoryProducts($child);
            }
        };

        foreach ($categories as $category) {
            $buildCategoryProducts($category);
        }

        $headerMain = Menu::where('location', 'header-main')->where('is_active', true)->first();

        // El mega-menú "Servicios" se alimenta directo de la jerarquía real
        // de ServicePage (categoría -> servicios hoja) en vez del sistema
        // genérico de Menú (location='header-servicios') — no tiene sentido
        // curar a mano un menú aparte cuando el catálogo de Servicios ya es
        // la fuente de verdad de esa misma estructura de 2 niveles.
        $serviceCategories = ServicePage::where('page_type', ServicePage::TYPE_CATEGORY)
            ->where('is_active', true)
            ->with(['activeChildren' => fn ($q) => $q->orderBy('name')
                ->with([
                    'images' => fn ($qi) => $qi->orderBy('sort_order'),
                    'reviews' => fn ($qr) => $qr->where('is_visible', true)->orderBy('sort_order')->limit(1),
                ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $serviceCategories->each(function (ServicePage $category) {
            $category->activeChildren->each(function (ServicePage $child) use ($category) {
                $child->setRelation('parent', $category);
                $this->attachMegaMenuPromoData($child);
            });
        });

        $view->with([
            'megaMenuCategories'        => $categories,
            'megaMenuCategoryProducts'  => $categoryProducts,
            'headerMainItems'           => $headerMain ? $headerMain->rootItems()->get() : collect(),
            'megaMenuServiceCategories' => $serviceCategories,
        ]);
    }

    /**
     * Precalcula, sobre el propio modelo (atributos ad-hoc, no persistidos),
     * todo lo que el panel de promoción del mega-menú de Servicios necesita
     * mostrar de un servicio hoja — mantiene el Blade enfocado en markup en
     * vez de repetir esta lógica ahí. Usa solo datos ya reales del servicio
     * (rating_*, reseñas visibles, galería); si algo no está configurado
     * simplemente no se muestra esa parte del panel (nunca se inventa dato).
     */
    protected function attachMegaMenuPromoData(ServicePage $child): void
    {
        // cover_image_url debería estar sincronizado con la imagen
        // sort_order=0 de la galería, pero no todo camino de escritura lo
        // garantiza — se usa como respaldo la primera imagen de la galería
        // si el campo está vacío, para no dejar el panel sin foto principal
        // teniendo imágenes reales cargadas.
        $child->setAttribute('promoMainImageUrl', $child->cover_image_url ?: $child->images->first()?->url);
        $child->setAttribute('promoThumbs', $child->images->skip(1)->take(2)->values());

        $child->setAttribute('promoStars', collect(range(1, 5))->map(
            fn ($i) => $child->rating_average_displayed && $i <= round($child->rating_average_displayed)
        ));

        $child->setAttribute('promoChips', collect([
            $child->rating_punctuality_average ? 'Puntualidad ' . number_format($child->rating_punctuality_average, 1) : null,
            $child->rating_recommend_percent ? 'Recomendación ' . number_format($child->rating_recommend_percent, 0) . '%' : null,
            $child->rating_recurring_clients ? 'Clientes recurrentes ' . number_format($child->rating_recurring_clients, 0) . '%' : null,
        ])->filter()->take(3)->values());

        $review = $child->reviews->first();

        if ($review) {
            $metaParts = collect([
                $review->customer_role,
                $review->customer_company,
                trim(collect([$review->customer_city, $review->customer_state])->filter()->implode(', ')),
            ])->filter();

            $child->setAttribute('promoQuoteText', \Illuminate\Support\Str::limit($review->comment, 140));
            $child->setAttribute('promoQuoteAuthor', $review->customer_name . ($metaParts->isNotEmpty() ? ' · ' . $metaParts->implode(' · ') : ''));
        } else {
            $child->setAttribute('promoQuoteText', null);
            $child->setAttribute('promoQuoteAuthor', null);
        }
    }
}
