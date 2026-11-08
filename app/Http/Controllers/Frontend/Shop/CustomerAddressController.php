<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CRUD real de direcciones guardadas de un cliente (guard `customer`).
 * Consumido desde 2 lugares: la pestaña "Direcciones" de /cuenta (antes solo
 * visual, ver resources/views/frontend/shop/account/partials/modals.blade.php)
 * y el selector de dirección guardada del paso de Envío del checkout
 * (resources/views/frontend/shop/checkout/shipping.blade.php).
 */
class CustomerAddressController extends Controller
{
    public const STATES = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];

    // Personas y ciudades: letras (con acentos), espacios, punto, apóstrofo y guion; sin números ni #$%.
    private const NAME_REGEX = '/^[\pL\pM][\pL\pM\s.\'’\-]*$/u';

    // Etiqueta: además admite números y . , & / ( ).
    private const LABEL_REGEX = '/^[\pL\pN][\pL\pN\pM\s.,&\'’\-\/()]*$/u';

    // Calle, colonia y referencias: números y # ° (p. ej. "Av. Juárez #123 int. 4"), pero no $ % @ etc.
    private const STREET_REGEX = '/^[\pL\pN][\pL\pN\pM\s.,#°\'’\-\/()]*$/u';

    private const PHONE_REGEX = '/^\+?[\d\s\-().]+$/';

    private function rules(): array
    {
        return [
            'label'          => ['nullable', 'string', 'max:100', 'regex:' . self::LABEL_REGEX],
            'recipient_name' => ['required', 'string', 'max:150', 'regex:' . self::NAME_REGEX],
            'phone'          => ['required', 'string', 'max:30', 'regex:' . self::PHONE_REGEX, function ($attr, $value, $fail) {
                $digits = strlen(preg_replace('/\D/', '', (string) $value));
                if ($digits < 10 || $digits > 15) {
                    $fail('El teléfono debe tener entre 10 y 15 dígitos.');
                }
            }],
            'postal_code'    => ['required', 'string', 'regex:/^\d{5}$/'],
            'state'          => ['required', 'string', Rule::in(self::STATES)],
            'city'           => ['required', 'string', 'max:100', 'regex:' . self::NAME_REGEX],
            'address_line1'  => ['required', 'string', 'max:255', 'regex:' . self::STREET_REGEX],
            'address_line2'  => ['nullable', 'string', 'max:255', 'regex:' . self::STREET_REGEX],
            'reference'      => ['nullable', 'string', 'max:255', 'regex:' . self::STREET_REGEX],
        ];
    }

    private function messages(): array
    {
        return [
            'label.regex'              => 'La etiqueta solo puede llevar letras, números, espacios y . , & \' - / ( ).',
            'recipient_name.required'  => 'Escribe el nombre de quien recibe.',
            'recipient_name.regex'     => 'El nombre solo puede llevar letras, espacios, punto, apóstrofo y guion.',
            'phone.required'           => 'Escribe un teléfono de contacto.',
            'phone.regex'              => 'El teléfono solo puede llevar números, espacios, guiones, paréntesis y un + al inicio.',
            'postal_code.required'     => 'Escribe tu código postal.',
            'postal_code.regex'        => 'El código postal debe tener 5 dígitos.',
            'state.required'           => 'Selecciona un estado.',
            'state.in'                 => 'Selecciona un estado de la lista.',
            'city.required'            => 'Escribe la ciudad.',
            'city.regex'               => 'La ciudad solo puede llevar letras, espacios, punto, apóstrofo y guion.',
            'address_line1.required'   => 'Escribe la calle y el número.',
            'address_line1.regex'      => 'La calle solo puede llevar letras, números, espacios y . , # ° - / ( ).',
            'address_line2.regex'      => 'La colonia solo puede llevar letras, números, espacios y . , # ° - / ( ).',
            'reference.regex'          => 'Las referencias solo pueden llevar letras, números, espacios y . , # ° - / ( ).',
        ];
    }

    /** Quita espacios sobrantes antes de validar para que "  Ana " no falle ni se guarde con ellos. */
    private function trimmed(Request $request): void
    {
        $request->merge(collect(['label', 'recipient_name', 'phone', 'postal_code', 'city', 'address_line1', 'address_line2', 'reference'])
            ->mapWithKeys(fn ($k) => [$k => $request->has($k) ? trim((string) $request->input($k)) : null])
            ->filter(fn ($v) => $v !== null)->all());
    }
    public function store(Request $request): RedirectResponse
    {
        $this->trimmed($request);
        $data = $request->validate($this->rules(), $this->messages());
        $customer = Auth::guard('customer')->user();

        DB::transaction(function () use ($data, $request, $customer) {
            // La primera dirección de un cliente siempre queda predeterminada
            // -- sin esto, un cliente con una sola dirección guardada podría
            // quedarse sin ninguna marcada como default.
            $makeDefault = $customer->customer_addresses()->doesntExist() || $request->boolean('is_default');

            if ($makeDefault) {
                $customer->customer_addresses()->update(['is_default' => false]);
            }

            $customer->customer_addresses()->create([
                ...$data,
                'country'    => 'MX',
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('status', 'Dirección guardada.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->customer_id === Auth::guard('customer')->id(), 403);

        $this->trimmed($request);
        $data = $request->validate($this->rules(), $this->messages());
        $customer = $address->customer;

        DB::transaction(function () use ($data, $request, $address, $customer) {
            $makeDefault = $request->boolean('is_default') || $customer->customer_addresses()->count() === 1;

            if ($makeDefault) {
                $customer->customer_addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update([...$data, 'is_default' => $makeDefault]);
        });

        return back()->with('status', 'Dirección actualizada.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->customer_id === Auth::guard('customer')->id(), 403);

        $wasDefault = $address->is_default;
        $customer = $address->customer;
        $address->delete();

        // Si se borró la predeterminada y quedan otras, la más reciente pasa
        // a serlo -- nunca dejar al cliente sin ninguna dirección default
        // mientras le quede al menos una guardada.
        if ($wasDefault) {
            $customer->customer_addresses()->latest('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('status', 'Dirección eliminada.');
    }
}
