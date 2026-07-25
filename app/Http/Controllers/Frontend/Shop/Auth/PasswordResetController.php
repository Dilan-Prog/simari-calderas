<?php

namespace App\Http\Controllers\Frontend\Shop\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('frontend.shop.account.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('customers')->sendResetLink([
            'email' => strtolower($request->input('email')),
        ]);

        // Mensaje genérico siempre (anti-enumeración de correos): no
        // revelamos si el email existe o no en la base de clientes.
        return back()->with('status', 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.');
    }

    public function reset(Request $request, string $token)
    {
        return view('frontend.shop.account.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge(['email' => strtolower((string) $request->input('email'))]);

        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                // El broker no escribe la contraseña por sí mismo aquí: la
                // columna del modelo es password_hash (no password), así que
                // se asigna manualmente.
                $customer->forceFill([
                    'password_hash'  => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('shop.login')->with('status', 'Tu contraseña fue restablecida. Ya puedes iniciar sesión.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
