<?php

namespace App\Http\Controllers\Frontend\Shop\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CartRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('frontend.shop.account.auth', ['mode' => 'login']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // El admin guarda emails en minúsculas (ClientManageController).
        $email = strtolower($request->input('email'));
        $throttleKey = Str::transliterate($email . '|shop|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $credentials = ['email' => $email, 'password' => $request->input('password')];

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $customer = Auth::guard('customer')->user();

        if ($customer->status !== 'active') {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Contáctanos para reactivarla.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        // IMPORTANTE: regenerate() rota session()->getId() -- el merge de
        // carrito abandonado debe resolver "el carrito actual" DESPUÉS de
        // esto (con el id de sesión nuevo), nunca antes, o el carrito que
        // acabamos de fusionar quedaría ligado a un session_id que el
        // navegador ya no trae en su cookie.
        $request->session()->regenerate();

        $currentCart = Cart::firstOrCreate(
            ['session_id' => session()->getId()],
            ['customer_id' => $customer->id]
        );
        $recovered = app(CartRecoveryService::class)->recoverForCustomer($customer, $currentCart);

        if ($recovered) {
            session()->flash('success', '¡Recuperamos los productos que habías dejado en tu carrito!');
        }

        // Clientes con portal activado por el admin van directo al portal de
        // servicios; el resto, a su cuenta de la tienda.
        if ($customer->portal_access) {
            return redirect()->route('customer.dashboard');
        }

        return redirect()->intended(route('shop.account'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
