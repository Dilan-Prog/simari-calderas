<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\Redirect;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $collection = Collection::where('slug', $slug)
            ->where('is_active', true)
            ->with('rules')
            ->first();

        if (!$collection) {
            // FIX (SEO redirects): same reasoning as CatalogController::category()
            // — a renamed collection slug is a DB miss inside a matched route,
            // which Route::fallback() alone can't catch.
            if ($redirect = Redirect::resolve($request->path())) {
                return redirect($redirect->new_path, $redirect->status_code);
            }
            abort(404);
        }

        $products = $collection->productsQuery()
            ->paginate(24)
            ->withQueryString();

        $sections = HomeSection::where('page', 'collection')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.shop.collection.show', compact('collection', 'products', 'sections'));
    }
}
