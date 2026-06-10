<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\TechnicalService;
use App\Services\TechnicalServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalServiceController extends Controller
{
    public function __construct(private TechnicalServiceService $tsService) {}

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $viewMode = $request->get('view', 'calendar');
        $month    = (int) $request->get('month', now()->month);
        $year     = (int) $request->get('year', now()->year);

        $currentMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
        $currentYear  = $year;
        $technicians  = $this->tsService->getTechnicians();

        if ($viewMode === 'table') {
            $query = TechnicalService::with(['customer', 'serviceType', 'assignedTechnicians'])
                ->latest('service_date');

            if ($request->filled('technician_id')) {
                $query->byTechnician((int) $request->technician_id);
            }
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('service_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('service_date', '<=', $request->date_to);
            }

            $services = $query->paginate(15)->withQueryString();

            return view('admin.technical-services.index', compact(
                'services', 'technicians', 'currentMonth', 'currentYear', 'viewMode'
            ));
        }

        // Calendar view
        $services = $this->tsService->getCalendarEvents(
            $month,
            $year,
            $request->filled('technician_id') ? (int) $request->technician_id : null,
            $request->filled('status') ? $request->status : null
        );

        return view('admin.technical-services.index', compact(
            'services', 'technicians', 'currentMonth', 'currentYear', 'viewMode'
        ));
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $serviceTypes = $this->tsService->getServiceTypes();
        $technicians  = $this->tsService->getTechnicians();

        $customers = Customer::select('id', 'first_name', 'last_name', 'company')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $fromQuote = null;
        $service   = null;

        if ($request->filled('from_quote')) {
            $fromQuote = \App\Models\Quote::find($request->from_quote);
        }

        // $service is null on initial create; step1 blade handles this via null-safe operators
        return view('admin.technical-services.create', compact(
            'service', 'serviceTypes', 'customers', 'technicians', 'fromQuote'
        ));
    }

    // ── Store (Paso 1 — AJAX) ──────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'service_type_id'    => 'required|exists:service_types,id',
            'service_date'       => 'required|date',
            'service_time'       => 'nullable|date_format:H:i',
            'estimated_duration' => 'nullable',
            'priority'           => 'nullable|in:normal,high,urgent',
            'description'        => 'nullable|string',
            'address'            => 'nullable|string|max:255',
            'reference'          => 'nullable|string|max:255',
            'location'           => 'nullable|string|max:200',
            'week_number'        => 'nullable|integer|min:1|max:53',
            'from_quote_id'      => 'nullable|exists:quotes,id',
        ]);

        $service = $this->tsService->store($validated, auth()->id());

        return response()->json([
            'success'  => true,
            'message'  => "Servicio {$service->service_number} creado correctamente.",
            'redirect' => route('admin.technical-services.step', [$service, 2]),
        ]);
    }

    // ── Step — show ────────────────────────────────────────────────────────────

    public function step(TechnicalService $service, int $step): View|RedirectResponse
    {
        // Prevent skipping steps
        if ($step > $service->current_step + 1) {
            return redirect()
                ->route('admin.technical-services.step', [$service, $service->current_step + 1])
                ->with('error', 'No puedes saltar pasos. Completa el paso anterior primero.');
        }

        $serviceTypes = $this->tsService->getServiceTypes();
        $technicians  = $this->tsService->getTechnicians();

        $customers = Customer::select('id', 'first_name', 'last_name', 'company')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $fromQuote           = $service->fromQuote;
        $assignedTechnicians = collect();
        $plannedMaterials    = collect();

        if ($step === 2) {
            $service->load('assignedTechnicians');
            $assignedTechnicians = $service->assignedTechnicians;
        }

        if ($step === 3) {
            $service->load('plannedMaterials');
            $plannedMaterials = $service->plannedMaterials;
        }

        if ($step === 4) {
            $service->load([
                'assignedTechnicians',
                'plannedMaterials',
                'customer',
                'serviceType',
                'fromQuote',
            ]);
        }

        return view('admin.technical-services.create', compact(
            'service',
            'serviceTypes',
            'customers',
            'technicians',
            'fromQuote',
            'assignedTechnicians',
            'plannedMaterials'
        ));
    }

    // ── Step — save ────────────────────────────────────────────────────────────

    public function saveStep(Request $request, TechnicalService $service, int $step): JsonResponse|RedirectResponse
    {
        if (!$service->isEditable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este servicio no puede ser modificado en su estado actual.',
                ], 422);
            }
            return redirect()
                ->route('admin.technical-services.show', $service)
                ->with('error', 'Este servicio no puede ser modificado.');
        }

        $this->validateStep($request, $step);

        // Step 4: final confirmation — change status to scheduled
        if ($step === 4) {
            $this->tsService->updateStatus($service, 'scheduled', null, auth()->id());
            $service->update(['current_step' => 4]);

            return redirect()
                ->route('admin.technical-services.show', $service)
                ->with('success', "Servicio {$service->service_number} programado exitosamente.");
        }

        // Steps 1, 2, 3: save via AJAX
        $this->tsService->saveStep($service, $request->all(), $step);

        return response()->json([
            'success'  => true,
            'message'  => "Paso {$step} guardado correctamente.",
            'redirect' => route('admin.technical-services.step', [$service, $step + 1]),
        ]);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(TechnicalService $service): View
    {
        $service->load([
            'assignedTechnicians',
            'plannedMaterials',
            'logs.performedBy',
            'serviceType',
            'customer',
            'createdBy',
            'fromQuote',
        ]);

        return view('admin.technical-services.show', compact('service'));
    }

    // ── Edit ───────────────────────────────────────────────────────────────────

    public function edit(TechnicalService $service): RedirectResponse
    {
        if (!$service->isEditable()) {
            return redirect()
                ->route('admin.technical-services.show', $service)
                ->with('error', 'Este servicio no puede ser editado en su estado actual.');
        }

        return redirect()->route('admin.technical-services.step', [$service, 1]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(TechnicalService $service): RedirectResponse
    {
        if (!$service->isDeletable()) {
            return redirect()
                ->route('admin.technical-services.index')
                ->with('error', 'Solo se pueden eliminar servicios en estado Borrador.');
        }

        $number = $service->service_number;
        $service->delete();

        return redirect()
            ->route('admin.technical-services.index')
            ->with('success', "Servicio {$number} eliminado correctamente.");
    }

    // ── Update date (drag & drop — AJAX) ───────────────────────────────────────

    public function updateDate(Request $request, TechnicalService $service): JsonResponse
    {
        $request->validate([
            'service_date' => 'required|date',
        ]);

        if (!$service->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede mover un servicio en estado ' . $service->status_label . '.',
            ], 422);
        }

        $this->tsService->updateDate($service, $request->service_date, auth()->id());

        return response()->json([
            'success'      => true,
            'message'      => 'Fecha actualizada correctamente.',
            'service_date' => $service->fresh()->service_date->format('Y-m-d'),
        ]);
    }

    // ── Update status (AJAX) ───────────────────────────────────────────────────

    public function updateStatus(Request $request, TechnicalService $service): JsonResponse
    {
        $request->validate([
            'status'  => 'required|in:scheduled,in_progress,completed,cancelled',
            'comment' => 'nullable|string|max:500',
        ]);

        if (!$service->isCancellable() && $request->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Este servicio no puede ser cancelado.',
            ], 422);
        }

        $this->tsService->updateStatus(
            $service,
            $request->status,
            $request->comment,
            auth()->id()
        );

        $service->refresh();

        return response()->json([
            'success'      => true,
            'message'      => "Estado actualizado a {$service->status_label}.",
            'status'       => $service->status,
            'status_label' => $service->status_label,
        ]);
    }

    // ── Generate report ────────────────────────────────────────────────────────

    public function generateReport(TechnicalService $service): RedirectResponse
    {
        return redirect()->route('admin.service-reports.create', [
            'from_service' => $service->id,
        ]);
    }

    // ── Search technicians (AJAX) ──────────────────────────────────────────────

    public function searchTechnicians(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 1) {
            return response()->json(['technicians' => []]);
        }

        $technicians = $this->tsService->searchTechnicians($q);

        return response()->json(['technicians' => $technicians]);
    }

    // ── Search materials (AJAX) ────────────────────────────────────────────────

    public function searchMaterials(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 1) {
            return response()->json(['materials' => []]);
        }

        $materials = Product::where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'sku', 'price')
            ->orderBy('name')
            ->take(10)
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'sku'   => $p->sku,
                'price' => $p->price,
            ]);

        return response()->json(['materials' => $materials]);
    }

    // ── Validation per step ────────────────────────────────────────────────────

    private function validateStep(Request $request, int $step): void
    {
        $rules = match ($step) {
            1 => [
                'customer_id'        => 'required|exists:customers,id',
                'service_type_id'    => 'required|exists:service_types,id',
                'service_date'       => 'required|date',
                'service_time'       => 'nullable|date_format:H:i',
                'estimated_duration' => 'nullable',
                'priority'           => 'nullable|in:normal,high,urgent',
                'description'        => 'nullable|string',
                'address'            => 'nullable|string|max:255',
                'reference'          => 'nullable|string|max:255',
                'location'           => 'nullable|string|max:200',
                'week_number'        => 'nullable|integer|min:1|max:53',
            ],
            2 => [
                'technician_ids'   => 'nullable|array',
                'technician_ids.*' => 'exists:users,id',
            ],
            3 => [
                'materials'                => 'nullable|array',
                'materials.*.product_id'   => 'nullable|exists:products,id',
                'materials.*.product_name' => 'required_with:materials|string|max:180',
                'materials.*.product_sku'  => 'nullable|string|max:80',
                'materials.*.quantity'     => 'required_with:materials|integer|min:1',
                'materials.*.unit'         => 'required_with:materials|in:litros,kg,piezas,metros,galones,otro',
                'materials.*.notes'        => 'nullable|string',
                'materials.*.sort_order'   => 'nullable|integer',
            ],
            4  => [],
            default => [],
        };

        $request->validate($rules);
    }
}
