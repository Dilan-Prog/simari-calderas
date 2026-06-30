<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\TechnicalService;
use Illuminate\Http\Request;

class TechnicalServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = TechnicalService::where('customer_id', auth('customer')->id())
            ->with(['serviceType', 'assignedTechnicians'])
            ->latest('service_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->paginate(15)->withQueryString();

        return view('customers.technical-services.index', compact('services'));
    }

    public function show(TechnicalService $service)
    {
        abort_unless($service->customer_id === auth('customer')->id(), 403);

        $service->load(['assignedTechnicians', 'plannedMaterials', 'serviceType', 'customer']);

        return view('customers.technical-services.show', compact('service'));
    }
}
