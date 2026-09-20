<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CRUD real de direcciones guardadas de un cliente (guard `customer`).
 * Consumido desde 2 lugares: la pestaña "Direcciones" de /cuenta (antes solo
 * visual, ver resources/views/frontend/shop/account/partials/modals.blade.php)
 * y el selector de dirección guardada del paso de Envío del checkout
 * (resources/views/frontend/shop/checkout/shipping.blade.php).
 */
class CustomerAddressController extends Controller
{
    private function rules(): array
    {
        return [
            'label'          => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone'          => ['required', 'string', 'max:30'],
            'postal_code'    => ['required', 'string', 'max:20'],
            'state'          => ['required', 'string', 'max:100'],
            'city'           => ['required', 'string', 'max:100'],
            'address_line1'  => ['required', 'string', 'max:255'],
            'address_line2'  => ['nullable', 'string', 'max:255'],
            'reference'      => ['nullable', 'string', 'max:255'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
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

        $data = $request->validate($this->rules());
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
