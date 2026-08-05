<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Products;
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
        $headerServicios = Menu::where('location', 'header-servicios')->where('is_active', true)->first();

        $view->with([
            'megaMenuCategories'       => $categories,
            'megaMenuCategoryProducts' => $categoryProducts,
            'headerMainItems'          => $headerMain ? $headerMain->rootItems()->get() : collect(),
            'headerServiciosItems'     => $headerServicios ? $headerServicios->rootItems()->get() : collect(),
        ]);
    }
}
