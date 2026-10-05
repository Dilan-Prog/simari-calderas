<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\PaymentMethod;
use App\Models\Products;
use App\Models\Redirect;
use App\Services\ProductBlocks;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $product = Products::where('slug', $slug)
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images', 'documents', 'brand', 'category.parent.parent'])
            ->first();

        if (!$product) {
            // FIX (SEO redirects): mismo razonamiento que
            // CatalogController::category()/CollectionController::show() —
            // el patrón de ruta /producto/{slug} sigue haciendo match
            // sintáctico con cualquier slug viejo, así que Route::fallback()
            // nunca ve este caso; el miss ocurre acá adentro.
            if ($redirect = Redirect::resolve($request->path())) {
                return redirect($redirect->new_path, $redirect->status_code);
            }
            abort(404);
        }

        $specifications = $product->specifications
            ? collect(json_decode($product->specifications, true) ?? [])
            : collect();

        // Secciones globales legadas (page='product'): solo si el flag las
        // reactiva; por defecto la página de producto usa únicamente los
        // bloques dinámicos asignados a cada producto (ProductBlocks).
        $sections = config('shop.product_global_sections')
            ? HomeSection::where('page', 'product')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
            : collect();

        // Bloques dinámicos del producto: 'stack' (a todo el ancho debajo de
        // .product-main) y 'sidebar' (columna derecha, sobre "Medios de pago").
        $blocks = ProductBlocks::forProduct($product);
        $blocksStack = $blocks['stack'];
        $blocksSidebar = $blocks['sidebar'];

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.shop.product.show', compact(
            'product', 'specifications', 'sections', 'blocksStack', 'blocksSidebar', 'paymentMethods'
        ));
    }
}
