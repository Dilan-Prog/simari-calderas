<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Models\ServicePage;
use Illuminate\Http\Request;

class ServicePageController extends Controller
{
    /**
     * /servicios — nivel 1 (hub). Si no hay un ServicePage page_type=hub
     * capturado todavía, arma una versión mínima sin editar (solo el grid
     * de categorías) para que la ruta nunca truene aunque el admin no haya
     * creado el hub todavía.
     */
    public function hub()
    {
        $hub = ServicePage::where('page_type', ServicePage::TYPE_HUB)
            ->where('is_active', true)
            ->with('images')
            ->first();

        $children = ServicePage::where('page_type', ServicePage::TYPE_CATEGORY)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if (!$hub) {
            $hub = new ServicePage([
                'name' => 'Servicios',
                'slug' => '',
                'page_type' => ServicePage::TYPE_HUB,
                'short_description' => 'Conoce todos nuestros servicios técnicos por tipo de equipo.',
            ]);
        }

        return $this->renderServicePage($hub, $children);
    }

    /**
     * /servicios/{level2} — nivel 2 (categoría, ej. "calderas").
     */
    public function category(Request $request, string $level2)
    {
        $category = ServicePage::where('slug', $level2)
            ->where('page_type', ServicePage::TYPE_CATEGORY)
            ->where('is_active', true)
            ->with('images')
            ->first();

        if (!$category) {
            return $this->redirectOrAbort($request);
        }

        $children = $category->activeChildren()->orderBy('name')->get();

        return $this->renderServicePage($category, $children);
    }

    /**
     * /servicios/{level2}/{level3} — nivel 3 (servicio hoja dentro de una
     * categoría, ej. "calderas/diagnostico").
     */
    public function showNested(Request $request, string $level2, string $level3)
    {
        $category = ServicePage::where('slug', $level2)
            ->where('page_type', ServicePage::TYPE_CATEGORY)
            ->first();

        $servicePage = $category
            ? ServicePage::where('slug', $level3)
                ->where('parent_id', $category->id)
                ->where('page_type', ServicePage::TYPE_SERVICE)
                ->where('is_active', true)
                ->with('images')
                ->first()
            : null;

        if (!$servicePage) {
            return $this->redirectOrAbort($request);
        }

        return $this->renderServicePage($servicePage);
    }

    /**
     * /servicio/{slug} (singular, legacy) — solo para servicios "planos"
     * sin categoría (parent_id null). Un servicio que ya fue reorganizado
     * bajo una categoría deja de responder aquí a propósito: su Redirect
     * 301 (creado automáticamente por ServicePage::booted() al asignarle
     * padre) lo manda a la URL anidada real.
     */
    public function showLegacy(Request $request, string $slug)
    {
        $servicePage = ServicePage::where('slug', $slug)
            ->where('page_type', ServicePage::TYPE_SERVICE)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('images')
            ->first();

        if (!$servicePage) {
            return $this->redirectOrAbort($request);
        }

        return $this->renderServicePage($servicePage);
    }

    protected function redirectOrAbort(Request $request)
    {
        if ($redirect = Redirect::resolve($request->path())) {
            return redirect($redirect->new_path, $redirect->status_code);
        }

        abort(404);
    }

    protected function renderServicePage(ServicePage $servicePage, ?\Illuminate\Support\Collection $children = null)
    {
        $sections = $servicePage->exists
            ? $servicePage->sections()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        $ancestors = $servicePage->exists ? $servicePage->ancestors() : [];
        $children = $children ?? collect();

        return view('frontend.shop.service-page.show', compact('servicePage', 'sections', 'ancestors', 'children'));
    }
}
