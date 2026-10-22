<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Services\TechnicalServiceService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * El cliente del portal solicita (programa) un servicio técnico. Se guarda como
 * TechnicalService en `draft` sin usuario creador (ver TechnicalService::scopeCustomerRequests):
 * el personal lo revisa en /admin/servicios-tecnicos y lo programa o lo rechaza.
 * El cliente solo escribe y lee sus propios datos; nunca se muestran importes.
 */
class ServiceRequestController extends Controller
{
    private const TOKEN_KEY = 'service_request_token';
    private const MAX_PER_HOUR = 5;

    /** Ventanas de horario preferido (solo orientativas; el personal confirma la hora). */
    public const TIME_WINDOWS = [
        'any'       => 'Cualquier horario',
        'morning'   => 'Mañana (8:00 - 12:00)',
        'afternoon' => 'Tarde (12:00 - 17:00)',
    ];

    public function __construct(private TechnicalServiceService $tsService)
    {
        // Anti-spam: máximo 5 solicitudes por hora, por cliente (no por IP: el guard
        // por defecto es `web`, así que `throttle:` no distinguiría clientes). El
        // contador solo avanza cuando la solicitud se guarda (ver store()).
        $this->middleware(function (Request $request, Closure $next) {
            if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_PER_HOUR)) {
                $minutes = (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60);

                return back()->withInput()->withErrors([
                    'throttle' => "Enviaste varias solicitudes seguidas. Inténtalo de nuevo en {$minutes} min o contáctanos.",
                ]);
            }

            return $next($request);
        })->only('store');
    }

    public function create(Request $request): View
    {
        $customer = auth('customer')->user();

        // Token de un solo uso (generado en servidor) para que el doble clic /
        // reenvío del formulario no cree solicitudes duplicadas.
        if (! $request->session()->has(self::TOKEN_KEY)) {
            $request->session()->put(self::TOKEN_KEY, (string) Str::uuid());
        }

        $serviceTypes = ServiceType::active()->orderBy('name')->pluck('name', 'id');

        $addresses = $customer->customer_addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn (CustomerAddress $a) => [
                'id'         => $a->id,
                'label'      => $a->label ?: 'Dirección',
                'is_default' => (bool) $a->is_default,
                'text'       => $this->formatAddress($a),
                'reference'  => $a->reference,
            ])
            ->filter(fn (array $a) => $a['text'] !== '')
            ->values();

        $pendingRequests = TechnicalService::customerRequests()
            ->where('customer_id', $customer->id)
            ->with('serviceType')
            ->latest('requested_at')
            ->limit(10)
            ->get();

        return view('customers.service-requests.create', [
            'serviceTypes'    => $serviceTypes,
            'addresses'       => $addresses,
            'pendingRequests' => $pendingRequests,
            'timeWindows'     => self::TIME_WINDOWS,
            'customer'        => $customer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'service_type_id' => ['required', 'integer', 'exists:service_types,id,is_active,1'],
            'service_date'    => ['required', 'date', 'after_or_equal:today'],
            'time_window'     => ['nullable', 'in:' . implode(',', array_keys(self::TIME_WINDOWS))],
            'address'         => ['required', 'string', 'max:255'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'description'     => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'service_type_id.required'    => 'Elige el tipo de servicio.',
            'service_type_id.exists'      => 'El tipo de servicio elegido no está disponible.',
            'service_date.required'       => 'Indica la fecha en que te gustaría el servicio.',
            'service_date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'address.required'            => 'Indica la dirección donde se realizará el servicio.',
            'description.required'        => 'Cuéntanos qué necesitas o qué problema tienes.',
            'description.min'             => 'Describe con un poco más de detalle (mínimo 10 caracteres).',
        ]);

        $windowKey   = $validated['time_window'] ?? 'any';
        $windowLabel = $windowKey === 'any' ? null : self::TIME_WINDOWS[$windowKey];

        // El horario preferido no tiene columna propia (service_time es una hora exacta
        // y aún no está confirmada): va al final de la descripción para que el personal lo vea.
        $description = trim($validated['description']);
        if ($windowLabel) {
            $description .= "\n\nHorario preferido: {$windowLabel}";
        }

        $token = $request->session()->get(self::TOKEN_KEY) ?: (string) Str::uuid();

        $this->tsService->storeCustomerRequest($customer->id, [
            'draft_token'     => $token,
            'service_type_id' => (int) $validated['service_type_id'],
            'service_date'    => $validated['service_date'],
            'address'         => trim($validated['address']),
            'reference'       => isset($validated['reference']) ? trim($validated['reference']) : null,
            'description'     => $description,
        ], $windowLabel);

        RateLimiter::hit($this->throttleKey(), 3600);

        // El token se consume: la siguiente solicitud genera uno nuevo.
        $request->session()->forget(self::TOKEN_KEY);

        return redirect()
            ->route('customer.service-requests.create')
            ->with('success', 'Recibimos tu solicitud; te confirmaremos la fecha.');
    }

    private function throttleKey(): string
    {
        return 'service-request:' . auth('customer')->id();
    }

    private function formatAddress(CustomerAddress $a): string
    {
        $cityLine = trim(implode(', ', array_filter([$a->city, $a->state])));
        if ($a->postal_code) {
            $cityLine = trim($cityLine . ' C.P. ' . $a->postal_code);
        }

        return implode(', ', array_filter([
            trim((string) $a->address_line1),
            trim((string) $a->address_line2),
            $cityLine,
        ]));
    }
}
