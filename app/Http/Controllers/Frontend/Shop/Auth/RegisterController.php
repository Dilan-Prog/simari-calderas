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
    // Nombres: letras (con acentos), espacios, punto, apóstrofo y guion; sin números ni símbolos como # $ %.
    private const NAME_REGEX = '/^[\pL\pM][\pL\pM\s.\'’\-]*$/u';

    // Empresa: además admite números y . , & / ( ) para razones sociales ("Calderas & Co., S.A. de C.V.").
    private const COMPANY_REGEX = '/^[\pL\pN][\pL\pN\pM\s.,&\'’\-\/()]*$/u';

    private const PHONE_REGEX = '/^\+?[\d\s\-().]+$/';

    public function create()
    {
        return view('frontend.shop.account.auth', ['mode' => 'register']);
    }

    public function store(Request $request)
    {
        // Normalizar antes de validar para que unique compare contra lo
        // que realmente se guarda (el admin guarda emails en minúsculas).
        $request->merge(['email' => strtolower((string) $request->input('email'))]);

        // Espacios sobrantes fuera, para que "  Ana " no falle ni se guarde con ellos.
        $request->merge([
            'first_name' => trim((string) $request->input('first_name')),
            'last_name'  => trim((string) $request->input('last_name')),
            'company'    => trim((string) $request->input('company')),
            'phone'      => trim((string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'regex:' . self::NAME_REGEX],
            'last_name'  => ['required', 'string', 'max:100', 'regex:' . self::NAME_REGEX],
            'company'    => ['nullable', 'string', 'max:255', 'regex:' . self::COMPANY_REGEX],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:customers,email', 'unique:customer_portal_users,email'],
            'phone'      => ['required', 'string', 'max:20', 'regex:' . self::PHONE_REGEX, function ($attr, $value, $fail) {
                $digits = strlen(preg_replace('/\D/', '', (string) $value));
                if ($digits < 10 || $digits > 15) {
                    $fail('El teléfono debe tener entre 10 y 15 dígitos.');
                }
            }],
            'password'   => ['required', 'confirmed', Password::min(8)],
            'terms'      => ['accepted'],
            'visitor_uuid' => ['nullable', 'uuid'],
        ], [
            'first_name.required' => 'Escribe tu nombre.',
            'first_name.regex'    => 'El nombre solo puede llevar letras, espacios, punto, apóstrofo y guion.',
            'last_name.required'  => 'Escribe tu apellido.',
            'last_name.regex'     => 'El apellido solo puede llevar letras, espacios, punto, apóstrofo y guion.',
            'company.regex'       => 'La empresa solo puede llevar letras, números, espacios y . , & \' - / ( ).',
            'email.required'      => 'Escribe tu correo electrónico.',
            'email.email'         => 'Escribe un correo válido, por ejemplo nombre@empresa.com.',
            'email.unique'        => 'Este correo ya está registrado. Si eres cliente de Equiterm, usa "¿Olvidaste tu contraseña?" para activar tu acceso.',
            'phone.required'      => 'Escribe tu teléfono.',
            'phone.regex'         => 'El teléfono solo puede llevar números, espacios, guiones, paréntesis y un + al inicio.',
            'password.required'   => 'Escribe una contraseña.',
            'password.confirmed'  => 'Las contraseñas no coinciden.',
            'terms.accepted'      => 'Debes aceptar los Términos y condiciones y el Aviso de privacidad.',
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

        // Atribución publicitaria: el Customer recién creado nunca tiene
        // visitor_uuid previo, pero igual se envuelve en try/catch para no
        // romper el registro real por un huérfano raro contra la FK.
        if (!empty($data['visitor_uuid'])) {
            try {
                $customer->update(['visitor_uuid' => $data['visitor_uuid']]);
            } catch (\Throwable $e) {
                // silencioso a propósito
            }
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        // 'welcome' pinta la tarjeta de bienvenida con primeros pasos (partials/welcome); 'status' queda de respaldo.
        return redirect()->route('shop.account')
            ->with('welcome', true)
            ->with('status', '¡Bienvenido, ' . $customer->first_name . '! Tu cuenta fue creada correctamente.');
    }
}
