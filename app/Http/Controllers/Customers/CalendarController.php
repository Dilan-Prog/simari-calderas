<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\TechnicalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Calendario del portal: solo los servicios del cliente autenticado,
 * sin borradores y sin ningun dato monetario.
 */
class CalendarController extends Controller
{
    public function index()
    {
        return view('customers.calendar');
    }

    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date'],
        ]);

        if ($request->filled('month')) {
            $start = Carbon::createFromFormat('!Y-m', $request->query('month'))->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        } elseif ($request->filled('start') && $request->filled('end')) {
            $start = Carbon::parse($request->query('start'))->startOfDay();
            $end   = Carbon::parse($request->query('end'))->endOfDay();
        } else {
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        }

        // Evita rangos desmedidos (maximo ~ 3 meses).
        if ($end->lt($start) || $start->diffInDays($end) > 100) {
            return response()->json(['message' => 'Rango de fechas no valido.'], 422);
        }

        $services = TechnicalService::query()
            ->select(['id', 'service_number', 'service_type_id', 'service_date', 'service_time', 'status'])
            ->where('customer_id', auth('customer')->id())
            ->whereIn('status', TechnicalServiceController::VISIBLE_STATUSES)
            ->whereNotNull('service_date')
            ->whereDate('service_date', '>=', $start->toDateString())
            ->whereDate('service_date', '<=', $end->toDateString())
            ->with('serviceType:id,name')
            ->orderBy('service_date')
            ->orderBy('service_time')
            ->get();

        $events = $services->map(fn (TechnicalService $s) => [
            'id'           => $s->id,
            'number'       => $s->service_number,
            'title'        => $s->serviceType?->name ?? 'Servicio',
            'date'         => $s->service_date->toDateString(),
            'time'         => $s->service_time ? substr($s->service_time, 0, 5) : null,
            'status'       => $s->status,
            'status_label' => $s->status_label,
            'url'          => route('customer.technical-services.show', $s->id),
        ])->values();

        return response()->json([
            'start'  => $start->toDateString(),
            'end'    => $end->toDateString(),
            'events' => $events,
        ]);
    }
}
