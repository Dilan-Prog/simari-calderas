<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(
            $request->only('email')
        );

        // Mensaje genérico siempre (anti-enumeración de correos): el status
        // real de Password::sendResetLink() nunca se expone — Laravel
        // devuelve un mensaje distinto según si el correo existe o no en
        // `users`, lo que permitiría enumerar cuentas de staff/admin.
        // Mismo criterio ya usado en el flujo de clientes (ver
        // PasswordResetController::email()).
        return back()->with('status', 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.');
    }
}
