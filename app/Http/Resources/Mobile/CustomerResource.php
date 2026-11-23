<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cliente del catálogo para la app móvil (buscador del asistente de reportes y
 * «Programar servicio»).
 *
 * `name` es lo que la app muestra: la empresa si existe y, si no, la persona
 * (mismo criterio que TechnicalService::customer_name en el panel). La persona
 * de contacto va aparte en `contact_name`.
 *
 * Requiere cargada: defaultAddress (puede ser null).
 *
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");
        $addr     = $this->defaultAddress;

        $city  = $addr?->city ?: null;
        $state = $addr?->state ?: null;

        $street = $addr ? trim(implode(', ', array_filter([
            $addr->address_line1,
            $addr->address_line2,
            $city,
            $state,
            $addr->postal_code ? 'C.P. ' . $addr->postal_code : null,
        ]))) : '';

        return [
            'id'           => $this->id,
            'name'         => ($this->company ?: $fullName) ?: null,
            'full_name'    => $fullName ?: null,
            'contact_name' => $fullName ?: null,
            'company'      => $this->company ?: null,
            'rfc'          => $this->rfc ?: null,
            'phone'        => $this->phone ?: null,
            'email'        => $this->email ?: null,
            'address'      => $street !== '' ? $street : null,
            'city'         => $city,
            'state'        => $state,
            'location'     => $city ? ($state ? "{$city}, {$state}" : $city) : $state,
        ];
    }
}
