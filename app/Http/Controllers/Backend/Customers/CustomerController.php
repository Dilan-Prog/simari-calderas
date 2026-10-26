<?php

namespace App\Http\Controllers\Backend\Customers;

use App\Http\Controllers\Controller;
use App\Models\ServiceReport;
use App\Models\TechnicalService;
use App\Services\SatisfactionSurveyService;
use App\Support\PortalContext;

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

        // Un usuario del equipo (roles del portal) solo ve los paneles de los módulos
        // que su rol permite: no se consultan ni se muestran los demás.
        $can = [
            'services' => PortalContext::can('technical-services'),
            'reports'  => PortalContext::can('service-reports'),
            'request'  => PortalContext::can('service-requests'),
            'calendar' => PortalContext::can('calendar'),
            'history'  => PortalContext::can('history'),
        ];

        $services = TechnicalService::where('customer_id', $customer->id);

        $stats = [
            'upcoming'   => $can['services'] ? (clone $services)->where('status', 'scheduled')->whereDate('service_date', '>=', $today)->count() : 0,
            'in_process' => $can['services'] ? (clone $services)->where('status', 'in_progress')->count() : 0,
            'completed'  => $can['services'] ? (clone $services)->where('status', 'completed')->count() : 0,
            // Misma lógica que la lista de reportes: firmados sin calificación enviada.
            'pending_rating' => $can['reports'] ? $this->survey->pendingFor($customer)->count() : 0,
        ];

        $upcomingServices = $can['services']
            ? (clone $services)
                ->with('serviceType')
                ->where('status', 'scheduled')
                ->whereDate('service_date', '>=', $today)
                ->orderBy('service_date')
                ->orderBy('service_time')
                ->limit(5)
                ->get()
            : collect();

        $latestReports = $can['reports']
            ? ServiceReport::where('customer_id', $customer->id)
                ->latest('service_date')
                ->latest('id')
                ->limit(3)
                ->get()
            : collect();

        $displayName = PortalContext::displayName();

        return view('customers.dashboard', compact('customer', 'stats', 'upcomingServices', 'latestReports', 'can', 'displayName'));
    }
}
