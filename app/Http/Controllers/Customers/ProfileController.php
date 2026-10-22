<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * "Mi perfil": datos del cliente autenticado. Solo se pueden editar los datos
     * de contacto y fiscales; `notes`, `status`, `portal_access`, `source`,
     * `document_type` y `password_hash` son internos / de administración.
     */
    public function show()
    {
        $customer = auth('customer')->user();

        $counts = [
            'services_total'     => $customer->services()->count(),
            'services_completed' => $customer->services()->where('status', 'completed')->count(),
            'services_upcoming'  => $customer->services()->where('status', 'scheduled')->whereDate('service_date', '>=', now()->toDateString())->count(),
            'reports_total'      => $customer->serviceReports()->count(),
            'reports_signed'     => $customer->serviceReports()->where('status', 'signed')->count(),
        ];

        return view('customers.profile', compact('customer', 'counts'));
    }

    /**
     * Actualiza datos personales / de empresa / fiscales.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        // Normalizar antes de validar para que `unique` compare contra lo que
        // realmente se guarda (correo en minúsculas, RFC en mayúsculas).
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'rfc'   => ($rfc = strtoupper(preg_replace('/\s+/', '', (string) $request->input('rfc')))) !== '' ? $rfc : null,
        ]);

        $data = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['nullable', 'string', 'max:100'],
            'email'        => ['required', 'string', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone'        => ['required', 'string', 'max:20'],
            'company'      => ['nullable', 'string', 'max:255'],
            'rfc'          => ['nullable', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u'],
            'tipo_persona' => ['nullable', 'in:fisica,moral'],
        ], [
            'required'     => 'El campo :attribute es obligatorio.',
            'string'       => 'El campo :attribute no es válido.',
            'max'          => 'El campo :attribute no puede tener más de :max caracteres.',
            'email'        => 'Escribe un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado en otra cuenta.',
            'rfc.regex'    => 'El RFC no tiene un formato válido (12 caracteres para persona moral, 13 para persona física).',
            'in'           => 'Selecciona una opción válida en :attribute.',
        ], [
            'first_name'   => 'nombre',
            'last_name'    => 'apellido',
            'email'        => 'correo electrónico',
            'phone'        => 'teléfono',
            'company'      => 'empresa',
            'rfc'          => 'RFC',
            'tipo_persona' => 'tipo de persona',
        ]);

        // company y last_name son NOT NULL en la tabla: '' = "no especificado".
        $customer->fill([
            'first_name'   => trim($data['first_name']),
            'last_name'    => trim($data['last_name'] ?? ''),
            'email'        => $data['email'],
            'phone'        => trim($data['phone']),
            'company'      => trim($data['company'] ?? ''),
            'rfc'          => $data['rfc'] ?? null,
            'tipo_persona' => $data['tipo_persona'] ?? null,
        ])->save();

        return redirect()->route('customer.profile')->with('success', 'Tus datos se actualizaron correctamente.');
    }

    /**
     * Cambia la contraseña verificando la actual (columna password_hash).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'required'            => 'El campo :attribute es obligatorio.',
            'confirmed'           => 'La confirmación de la nueva contraseña no coincide.',
            'different'           => 'La nueva contraseña debe ser distinta a la actual.',
            'password.min'        => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ], [
            'current_password' => 'contraseña actual',
            'password'         => 'nueva contraseña',
        ]);

        if (!$customer->password_hash || !Hash::check($request->input('current_password'), $customer->password_hash)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $customer->forceFill([
            'password_hash'  => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        // Mantener la sesión del cliente iniciada con el nuevo hash.
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.profile')->with('success', 'Tu contraseña se actualizó correctamente.');
    }
}
