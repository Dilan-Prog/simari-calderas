<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbandonedCartController extends Controller
{
    public function index(Request $request): View
    {
        $horas = (int) $request->input('horas', 24);
        $horas = in_array($horas, [2, 24, 72, 168], true) ? $horas : 24;

        $tipo = $request->input('tipo', 'carrito');
        $tipo = in_array($tipo, ['carrito', 'checkout'], true) ? $tipo : 'carrito';

        $threshold = now()->subHours($horas);

        // Closure reutilizable: se necesita reconstruir el query base para el
        // listado paginado y para los conteos de KPI de ambos tipos a la vez
        // (clone sobre un closure no clona el builder que produce, así que
        // simplemente se invoca de nuevo — reconstruir el builder es barato).
        $base = fn (string $tipoFiltro) => Cart::query()
            ->whereHas('items')
            ->whereNull('dismissed_at')
            ->whereNull('converted_to_store_order_id')
            ->where('last_activity_at', '<', $threshold)
            ->when($tipoFiltro === 'carrito',
                fn ($q) => $q->whereNull('checkout_started_at'),
                fn ($q) => $q->whereNotNull('checkout_started_at'));

        $carts = $base($tipo)->with('items.product', 'customer')->orderBy('last_activity_at')->paginate(30)->withQueryString();

        $kpis = [
            'carrito_count'   => $base('carrito')->count(),
            'checkout_count'  => $base('checkout')->count(),
            'potential_value' => $base($tipo)
                ->join('cart_items', 'cart_items.cart_id', '=', 'carts.id')
                ->sum(\DB::raw('cart_items.quantity * cart_items.unit_price_snapshot')),
        ];

        return view('admin.abandoned-carts.index', compact('carts', 'kpis', 'horas', 'tipo'));
    }

    public function dismiss(Cart $cart): RedirectResponse
    {
        $cart->update(['dismissed_at' => now(), 'last_activity_at' => null]);

        // Evento de tracking publicitario: solo cuando el staff descarta
        // manualmente un carrito abandonado (nunca un barrido automático).
        if ($cart->visitor_uuid !== null) {
            $adVisit = \App\Models\AdVisit::where('visitor_uuid', $cart->visitor_uuid)->first();

            if ($adVisit) {
                \App\Models\AdEvent::create([
                    'ad_visit_id' => $adVisit->id,
                    'event_type'  => 'cart_abandoned',
                    'url'         => route('checkout.index'),
                    'occurred_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Carrito descartado.');
    }
}
