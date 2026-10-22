<?php

namespace App\Http\Controllers\Backend\Customers;

use App\Http\Controllers\Controller;
use App\Models\ServiceReport;
use App\Models\TechnicalService;
use App\Services\SatisfactionSurveyService;

class CustomerController extends Controller
{
    public function __construct(private SatisfactionSurveyService $survey)
    {
    }

    /**
     * Inicio del portal de servicios: resumen REAL del cliente autenticado.
     * Todo se filtra por customer_id; no se expone ningún monto.
     */
    public function index()
    {
        $customer = auth('customer')->user();
        $today = now()->toDateString();

        $services = TechnicalService::where('customer_id', $customer->id);

        $stats = [
            'upcoming'   => (clone $services)->where('status', 'scheduled')->whereDate('service_date', '>=', $today)->count(),
            'in_process' => (clone $services)->where('status', 'in_progress')->count(),
            'completed'  => (clone $services)->where('status', 'completed')->count(),
            // Misma lógica que la lista de reportes: firmados sin calificación enviada.
            'pending_rating' => $this->survey->pendingFor($customer)->count(),
        ];

        $upcomingServices = (clone $services)
            ->with('serviceType')
            ->where('status', 'scheduled')
            ->whereDate('service_date', '>=', $today)
            ->orderBy('service_date')
            ->orderBy('service_time')
            ->limit(5)
            ->get();

        $latestReports = ServiceReport::where('customer_id', $customer->id)
            ->latest('service_date')
            ->latest('id')
            ->limit(3)
            ->get();

        return view('customers.dashboard', compact('customer', 'stats', 'upcomingServices', 'latestReports'));
    }
}
