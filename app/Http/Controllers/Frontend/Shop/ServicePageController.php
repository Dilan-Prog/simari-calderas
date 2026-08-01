<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Models\ServicePage;
use Illuminate\Http\Request;

class ServicePageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $servicePage = ServicePage::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$servicePage) {
            if ($redirect = Redirect::resolve($request->path())) {
                return redirect($redirect->new_path, $redirect->status_code);
            }
            abort(404);
        }

        $sections = $servicePage->sections()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.shop.service-page.show', compact('servicePage', 'sections'));
    }
}
