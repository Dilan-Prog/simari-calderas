<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceReport;
use App\Models\ServiceReportImage;
use App\Models\TechnicalService;
use App\Models\User;
use App\Services\ServiceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceReportController extends Controller
{
    public function __construct(private ServiceReportService $service) {}

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = ServiceReport::with('assignedUser')->latest('service_date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('report_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('service_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('service_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('service_date', '<=', $request->date_to);
        }

        $reports = $query->paginate(15)->withQueryString();

        // FIX QA: the "Tipo de Servicio" filter's <option value=""> attributes
        // used to be hand-typed guesses ("quimico", "mantenimiento"...) that
        // never matched the real service_type enum, so the filter always
        // returned zero results. Pass the same canonical list the create
        // wizard already uses so the dropdown values are guaranteed correct.
        $serviceTypes = $this->service->getServiceTypes();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'service-reports.index')
            ->value('columns');

        return view('admin.service-reports.index', compact('reports', 'serviceTypes', 'visibleColumns'));
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $users        = User::select('id', 'first_name', 'last_name', 'position')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $serviceTypes = $this->service->getServiceTypes();
        $availableServices = $this->getAvailableServices();

        $fromService = $request->filled('from_service')
            ? TechnicalService::find($request->from_service)
            : null;

        return view('admin.service-reports.create', compact('users', 'serviceTypes', 'availableServices', 'fromService'));
    }

    private function getAvailableServices()
    {
        return TechnicalService::whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->with('customer:id,first_name,last_name,company,rfc,phone')
            ->latest('service_date')
            ->get(['id', 'service_number', 'customer_id', 'service_date']);
    }

    // ── Store (step 1) ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id'          => [
                'required',
                Rule::exists('services', 'id')->whereIn('status', ['scheduled', 'in_progress', 'completed']),
            ],
            'service_date'        => 'required|date',
            'customer_name'       => 'required|string|max:200',
            'customer_company'    => 'nullable|string|max:200',
            'customer_rfc'        => 'nullable|string|max:20',
            'customer_phone'      => 'nullable|string|max:30',
            'assigned_user_id'    => 'required|exists:users,id',
            'service_type'        => 'required|in:chemical_analysis,maintenance_preventive,maintenance_corrective,inspection,cleaning,calibration,activity_report,custom',
            'custom_service_type' => 'required_if:service_type,custom|nullable|string|max:100',
            'week_number'         => 'nullable|integer|min:1|max:53',
            'location'            => 'nullable|string|max:200',
        ]);

        // El cliente se deriva del servicio elegido — nunca se acepta directo
        // del request, así customer_id y service_id nunca quedan desalineados.
        $technicalService = TechnicalService::findOrFail($validated['service_id']);
        $validated['customer_id'] = $technicalService->customer_id;

        $report = $this->service->store($validated, auth()->id());

        return redirect()->route('admin.service-reports.step', [$report, 2])
            ->with('success', "Reporte {$report->report_number} creado. Continúa con el paso 2.");
    }

    // ── Steps ──────────────────────────────────────────────────────────────────

    public function step(ServiceReport $report, int $step)
    {
        // Prevent skipping steps
        if ($step > $report->current_step + 1) {
            return redirect()->route('admin.service-reports.step', [$report, $report->current_step + 1])
                ->with('error', 'No puedes saltar pasos. Completa el paso anterior primero.');
        }

        // Block access to sign step (6) until all data steps (1-5) are complete
        if ($step === 6 && $report->current_step < 5) {
            return redirect()->route('admin.service-reports.step', [$report, $report->current_step + 1])
                ->with('error', 'Debes completar todos los pasos antes de firmar el reporte.');
        }

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser']);

        $data = [
            'report'       => $report,
            'step'         => $step,
            'serviceTypes' => $this->service->getServiceTypes(),
        ];

        if ($step === 1) {
            $data['availableServices'] = $this->getAvailableServices();
            $data['users'] = User::select('id', 'first_name', 'last_name', 'position')
                ->where('status', 'active')->orderBy('first_name')->get();
        }

        if ($step === 2 && $report->usesActivityForm()) {
            $data['systemsChecked'] = $this->service->getSystemsChecked();
        }

        if ($step === 4) {
            $data['images'] = $report->images()->orderBy('sort_order')->get();
        }

        return view("admin.service-reports.steps.step{$step}", $data);
    }

    public function saveStep(Request $request, ServiceReport $report, int $step)
    {
        if (!$report->isEditable()) {
            // A signed report is normally fully frozen, except: an
            // administrator may still attach more photographic evidence
            // (step 4 only) after the fact. This branch handles exactly
            // that case and returns early — it deliberately skips
            // validateStep()/updateStep() so nothing else about the signed
            // report (other fields, current_step) gets touched or the
            // wizard reopened.
            if ($step !== 4 || !auth()->user()->isAdmin()) {
                return redirect()->route('admin.service-reports.show', $report)
                    ->with('error', 'Este reporte ya está firmado y no puede ser modificado.');
            }

            $request->validate([
                'images'   => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);

            $imagesFailed = 0;
            if ($request->hasFile('images')) {
                $imagesFailed = $this->service->saveImages($report, $request->file('images'));
            }

            $redirect = redirect()->route('admin.service-reports.show', $report)
                ->with('success', 'Imágenes agregadas correctamente.');

            if ($imagesFailed > 0) {
                $message = $imagesFailed === 1
                    ? 'No se pudo procesar 1 imagen (formato no compatible o archivo dañado). Las demás se guardaron correctamente.'
                    : "No se pudieron procesar {$imagesFailed} imágenes (formato no compatible o archivos dañados). Las demás se guardaron correctamente.";

                return $redirect->withErrors(['images' => $message]);
            }

            return $redirect;
        }

        $this->validateStep($request, $report, $step);

        $imagesFailed = 0;
        if ($step === 4 && $request->hasFile('images')) {
            $imagesFailed = $this->service->saveImages($report, $request->file('images'));
        }

        $data = $request->all();

        if ($step === 1) {
            // El cliente se deriva del servicio elegido — nunca se acepta
            // directo del request, así customer_id y service_id no quedan
            // desalineados.
            $data['customer_id'] = TechnicalService::findOrFail($data['service_id'])->customer_id;
        }

        $this->service->updateStep($report, $data, $step);

        // Step 6 = signature — handled by sign()
        $nextStep = $step + 1;

        $redirect = $nextStep > 6
            ? redirect()->route('admin.service-reports.show', $report)->with('success', 'Reporte completado exitosamente.')
            : redirect()->route('admin.service-reports.step', [$report, $nextStep])->with('success', "Paso {$step} guardado correctamente.");

        if ($imagesFailed > 0) {
            // FIX: images that GD/Intervention can't decode (unsupported
            // format like HEIC, or a corrupted upload) used to crash the
            // whole step with an uncaught exception and no feedback at all.
            // Now the good images are saved and the user is told how many
            // were skipped and why.
            $message = $imagesFailed === 1
                ? 'No se pudo procesar 1 imagen (formato no compatible o archivo dañado). Las demás se guardaron correctamente.'
                : "No se pudieron procesar {$imagesFailed} imágenes (formato no compatible o archivos dañados). Las demás se guardaron correctamente.";

            return $redirect->withErrors(['images' => $message]);
        }

        return $redirect;
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(ServiceReport $report)
    {
        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'customer', 'images', 'service']);

        $availableServices = $report->service_id ? collect() : $this->getAvailableServices();

        return view('admin.service-reports.show', compact('report', 'availableServices'));
    }

    // ── Edit ───────────────────────────────────────────────────────────────────

    public function edit(ServiceReport $report)
    {
        if (!$report->isEditable()) {
            return redirect()->route('admin.service-reports.show', $report)
                ->with('error', 'Los reportes firmados no pueden ser editados.');
        }

        return redirect()->route('admin.service-reports.step', [$report, 1]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(ServiceReport $report)
    {
        if (!$report->isDeletable()) {
            return redirect()->route('admin.service-reports.index')
                ->with('error', 'Solo se pueden eliminar reportes en estado Borrador.');
        }

        $reportNumber = $report->report_number;
        $report->delete();

        return redirect()->route('admin.service-reports.index')
            ->with('success', "Reporte {$reportNumber} eliminado correctamente.");
    }

    // ── PDF ────────────────────────────────────────────────────────────────────

    public function downloadPdf(ServiceReport $report)
    {
        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$report->report_number}.pdf");
    }

    public function previewPdf(ServiceReport $report)
    {
        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$report->report_number}.pdf");
    }

    // ── Sign ───────────────────────────────────────────────────────────────────

    public function sign(Request $request, ServiceReport $report)
    {
        if (!$report->isEditable()) {
            return redirect()->route('admin.service-reports.show', $report)
                ->with('error', 'Este reporte ya ha sido firmado.');
        }

        if ($report->current_step < 5) {
            return redirect()->route('admin.service-reports.step', [$report, $report->current_step + 1])
                ->with('error', 'Debes completar todos los pasos antes de firmar el reporte.');
        }

        $request->validate([
            'signature_data'     => 'required|string',
            'signature_name'     => 'required|string|max:150',
            'signature_position' => 'required|string|max:100',
            'signature_phone'    => 'nullable|string|max:30',
        ]);

        $this->service->saveSignature($report, $request->only([
            'signature_data',
            'signature_name',
            'signature_position',
            'signature_phone',
        ]));

        return redirect()->route('admin.service-reports.show', $report)
            ->with('success', "Reporte {$report->report_number} firmado y completado exitosamente.");
    }

    // ── Image delete ──────────────────────────────────────────────────────────

    public function destroyImage(ServiceReport $report, ServiceReportImage $image)
    {
        // Mirrors the saveStep() guard: once signed, only an admin may
        // still touch the report's images (add or remove) — everyone else
        // is frozen out, same as the rest of the report.
        if (!$report->isEditable() && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Este reporte ya está firmado; solo un administrador puede modificar sus imágenes.');
        }

        $this->service->deleteReportImage($image);

        return back()->with('success', 'Imagen eliminada correctamente.');
    }

    // ── Vincular servicio (solo admin) ─────────────────────────────────────────
    // Para reportes que quedaron sin servicio de origen y ya no pueden
    // editarse por su estado (todos los firmados) — asigna únicamente el
    // vínculo (y el cliente derivado), sin tocar la firma ni el resto del
    // reporte.
    public function attachService(Request $request, ServiceReport $report): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->whereIn('status', ['scheduled', 'in_progress', 'completed']),
            ],
        ]);

        $technicalService = TechnicalService::findOrFail($validated['service_id']);

        $report->update([
            'service_id'  => $technicalService->id,
            'customer_id' => $technicalService->customer_id,
        ]);

        return redirect()->route('admin.service-reports.show', $report)
            ->with('success', 'Servicio vinculado correctamente.');
    }

    // ── Customer search (AJAX) ─────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $q = $request->get('q', '');

        $customers = Customer::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name', 'like', "%{$q}%")
                      ->orWhere('company', 'like', "%{$q}%")
                      ->orWhere('rfc', 'like', "%{$q}%");
            })
            ->select('id', 'first_name', 'last_name', 'company', 'rfc', 'phone', 'email')
            ->take(10)
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'full_name' => trim("{$c->first_name} {$c->last_name}"),
                'company'   => $c->company,
                'rfc'       => $c->rfc,
                'phone'     => $c->phone,
                'email'     => $c->email,
            ]);

        return response()->json($customers);
    }

    // ── Validation per step ────────────────────────────────────────────────────

    private function validateStep(Request $request, ServiceReport $report, int $step): void
    {
        $rules = match($step) {
            1 => [
                'service_id'          => [
                    'required',
                    Rule::exists('services', 'id')->whereIn('status', ['scheduled', 'in_progress', 'completed']),
                ],
                'service_date'        => 'required|date',
                'customer_name'       => 'required|string|max:200',
                'customer_company'    => 'nullable|string|max:200',
                'customer_rfc'        => 'nullable|string|max:20',
                'customer_phone'      => 'nullable|string|max:30',
                'assigned_user_id'    => 'required|exists:users,id',
                'service_type'        => 'required|in:chemical_analysis,maintenance_preventive,maintenance_corrective,inspection,cleaning,calibration,activity_report,custom',
                'custom_service_type' => 'required_if:service_type,custom|nullable|string|max:100',
                'week_number'         => 'nullable|integer|min:1|max:53',
                'location'            => 'nullable|string|max:200',
            ],
            2 => $this->step2Rules($report),
            3 => [
                'observations'        => 'nullable|string',
                'recommendations'     => 'nullable|string',
                'include_sampling'    => 'nullable|boolean',
                'sampling_date_day'   => 'required_if:include_sampling,1|nullable|integer|min:1|max:31',
                'sampling_date_month' => 'required_if:include_sampling,1|nullable|integer|min:1|max:12',
                'sampling_date_year'  => 'required_if:include_sampling,1|nullable|integer|min:2000|max:2099',
                'analyst_name'        => 'nullable|string|max:150',
                'analyst_position'    => 'nullable|string|max:100',
            ],
            // Step 4 had no rules at all: any file type/size reached the
            // image processor directly. Limits mirror what the UI promises
            // ("JPG, PNG, WEBP · Máximo 5 MB por imagen").
            4 => [
                'images'   => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            ],
            5 => [],
            6 => [
                'signature_data'     => 'required|string',
                'signature_name'     => 'required|string|max:150',
                'signature_position' => 'required|string|max:100',
                'signature_phone'    => 'nullable|string|max:30',
            ],
            default => [],
        };

        $request->validate($rules, [
            'images.*.image' => 'Uno de los archivos no es una imagen válida.',
            'images.*.mimes' => 'Formato de imagen no compatible. Usa JPG, PNG o WEBP.',
            'images.*.max'   => 'Cada imagen debe pesar máximo 5 MB.',
        ]);
    }

    private function step2Rules(ServiceReport $report): array
    {
        if ($report->usesMeasurementsForm()) {
            return [
                'measurements'             => 'required|array|min:1',
                'measurements.*.parameter' => 'required|string|max:100',
                'measurements.*.unit'      => 'nullable|string|max:50',
                'measurements.*.result'    => 'nullable|string|max:100',
                'measurements.*.limit_min' => 'nullable|numeric',
                'measurements.*.limit_max' => 'nullable|numeric',
            ];
        }

        if ($report->usesActivityForm()) {
            return [
                'activity_content' => 'nullable|string',
                'systems_checked'  => 'nullable|array',
            ];
        }

        if ($report->usesCustomForm()) {
            return [
                'custom_fields'              => 'nullable|array',
                'custom_fields.*.field_name' => 'required|string|max:100',
                'custom_fields.*.field_type' => 'required|in:text,number,date,boolean,list',
                'custom_fields.*.field_value'=> 'nullable|string',
            ];
        }

        return [];
    }
}
