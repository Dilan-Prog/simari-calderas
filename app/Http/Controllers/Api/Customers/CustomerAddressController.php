<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerAddressResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

/**
 * Fase 5 del plan de integración N8N. Solo lectura, anidada bajo
 * /customers/{customer}/addresses -- ability `customers:read` (misma que
 * leer al cliente dueño de las direcciones).
 */
class CustomerAddressController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        $addresses = $customer->customer_addresses()->orderByDesc('is_default')->get();

        return response()->json(['data' => CustomerAddressResource::collection($addresses)]);
    }
}
