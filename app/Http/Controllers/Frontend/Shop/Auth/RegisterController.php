<?php

namespace App\Http\Controllers\Frontend\Shop\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('frontend.shop.account.auth', ['mode' => 'register']);
    }

    public function store(Request $request)
    {
        // Normalizar antes de validar para que unique compare contra lo
        // que realmente se guarda (el admin guarda emails en minúsculas).
        $request->merge(['email' => strtolower((string) $request->input('email'))]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'company'    => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'phone'      => ['required', 'string', 'max:20'],
            'password'   => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Este correo ya está registrado. Si eres cliente de Equiterm, usa "¿Olvidaste tu contraseña?" para activar tu acceso.',
        ]);

        $customer = Customer::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'password_hash' => Hash::make($data['password']),
            'status'        => 'active',
            'source'        => 'web',
            // Columnas NOT NULL sin default en la tabla; '' = "no
            // especificado" (mismo criterio que el formulario del admin,
            // que permite dejarlas vacías). Se completan después desde el
            // admin o en el perfil del cliente.
            'document_type' => '',
            'company'       => $data['company'] ?? '',
            'rfc'           => '',
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('shop.account')
            ->with('status', '¡Bienvenido, ' . $customer->first_name . '! Tu cuenta fue creada correctamente.');
    }
}
