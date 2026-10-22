<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Libreta de direcciones dentro del portal de servicios (guard `customer`).
 * Reutiliza el modelo y las reglas de la tienda
 * (App\Http\Controllers\Frontend\Shop\CustomerAddressController) para que
 * ambas superficies se comporten igual.
 */
class AddressController extends Controller
{
    public const ESTADOS = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];

    private function customer(): Customer
    {
        return auth('customer')->user();
    }

    /** Una dirección ajena responde 404 (no revela que existe). */
    private function owned(CustomerAddress $address): CustomerAddress
    {
        abort_unless((int) $address->customer_id === (int) auth('customer')->id(), 404);

        return $address;
    }

    private function rules(): array
    {
        return [
            'label'          => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone'          => ['required', 'string', 'max:30'],
            'postal_code'    => ['required', 'digits:5'],
            'state'          => ['required', 'string', 'in:' . implode(',', self::ESTADOS)],
            'city'           => ['required', 'string', 'max:100'],
            'address_line1'  => ['required', 'string', 'max:255'],
            'address_line2'  => ['nullable', 'string', 'max:255'],
            'reference'      => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'recipient_name.required' => 'Indica el nombre de quien recibe.',
            'phone.required'          => 'Indica un teléfono de contacto.',
            'postal_code.required'    => 'Indica el código postal.',
            'postal_code.digits'      => 'El código postal debe tener exactamente 5 dígitos.',
            'state.required'          => 'Selecciona un estado.',
            'state.in'                => 'Selecciona un estado válido.',
            'city.required'           => 'Indica la ciudad.',
            'address_line1.required'  => 'Indica la calle y número.',
        ];
    }

    public function index(): View
    {
        $addresses = $this->customer()
            ->customer_addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return view('customers.addresses', [
            'addresses' => $addresses,
            'estados'   => self::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());
        $customer = $this->customer();

        DB::transaction(function () use ($data, $request, $customer) {
            // La primera dirección siempre queda predeterminada.
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

        return redirect()->route('customer.addresses.index')->with('success', 'Dirección guardada.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->owned($address);

        $data = $request->validate($this->rules(), $this->messages());
        $customer = $this->customer();

        DB::transaction(function () use ($data, $request, $address, $customer) {
            // Si ya era la predeterminada se mantiene (desmarcarla desde aquí
            // la dejaría al cliente sin ninguna); para cambiarla se marca otra.
            $makeDefault = $request->boolean('is_default')
                || $address->is_default
                || $customer->customer_addresses()->count() === 1;

            if ($makeDefault) {
                $customer->customer_addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update([...$data, 'is_default' => $makeDefault]);
        });

        return redirect()->route('customer.addresses.index')->with('success', 'Dirección actualizada.');
    }

    public function makeDefault(CustomerAddress $address): RedirectResponse
    {
        $this->owned($address);
        $customer = $this->customer();

        DB::transaction(function () use ($address, $customer) {
            $customer->customer_addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('customer.addresses.index')->with('success', 'Dirección predeterminada actualizada.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $this->owned($address);

        $wasDefault = $address->is_default;
        $customer = $this->customer();

        DB::transaction(function () use ($address, $customer, $wasDefault) {
            $address->delete();

            // Si se borró la predeterminada y quedan otras, la más reciente
            // pasa a serlo (igual que en la tienda).
            if ($wasDefault) {
                $customer->customer_addresses()->latest('id')->first()?->update(['is_default' => true]);
            }
        });

        return redirect()->route('customer.addresses.index')->with('success', 'Dirección eliminada.');
    }
}
