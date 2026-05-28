<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceReport;
use App\Models\ServiceReportImage;
use App\Models\User;
use App\Services\ServiceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
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

        return view('admin.service-reports.index', compact('reports'));
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create()
    {
        $customers    = Customer::select('id', 'first_name', 'last_name', 'company', 'rfc', 'phone', 'email')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $users        = User::select('id', 'first_name', 'last_name', 'position')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $serviceTypes = $this->service->getServiceTypes();

        return view('admin.service-reports.create', compact('customers', 'users', 'serviceTypes'));
    }

    // ── Store (step 1) ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'service_date'        => 'required|date',
            'customer_name'       => 'required|string|max:200',
            'customer_company'    => 'nullable|string|max:200',
            'customer_rfc'        => 'nullable|string|max:20',
            'customer_phone'      => 'nullable|string|max:30',
            'customer_id'         => 'nullable|exists:customers,id',
            'assigned_user_id'    => 'required|exists:users,id',
            'service_type'        => 'required|in:chemical_analysis,maintenance_preventive,maintenance_corrective,inspection,cleaning,calibration,activity_report,custom',
            'custom_service_type' => 'required_if:service_type,custom|nullable|string|max:100',
            'week_number'         => 'nullable|integer|min:1|max:53',
            'location'            => 'nullable|string|max:200',
        ]);

        $report = $this->service->store($request->all(), auth()->id());

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

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser']);

        $data = [
            'report'       => $report,
            'step'         => $step,
            'serviceTypes' => $this->service->getServiceTypes(),
        ];

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
            return redirect()->route('admin.service-reports.show', $report)
                ->with('error', 'Este reporte ya está firmado y no puede ser modificado.');
        }

        $this->validateStep($request, $report, $step);

        if ($step === 4 && $request->hasFile('images')) {
            $this->service->saveImages($report, $request->file('images'));
        }

        $this->service->updateStep($report, $request->all(), $step);

        // Step 6 = signature — handled by sign()
        $nextStep = $step + 1;

        if ($nextStep > 6) {
            return redirect()->route('admin.service-reports.show', $report)
                ->with('success', 'Reporte completado exitosamente.');
        }

        return redirect()->route('admin.service-reports.step', [$report, $nextStep])
            ->with('success', "Paso {$step} guardado correctamente.");
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(ServiceReport $report)
    {
        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'customer', 'images']);
        return view('admin.service-reports.show', compact('report'));
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
        $this->service->deleteReportImage($image);

        return back()->with('success', 'Imagen eliminada correctamente.');
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
                'service_date'        => 'required|date',
                'customer_name'       => 'required|string|max:200',
                'customer_company'    => 'nullable|string|max:200',
                'customer_rfc'        => 'nullable|string|max:20',
                'customer_phone'      => 'nullable|string|max:30',
                'customer_id'         => 'nullable|exists:customers,id',
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
            4, 5 => [],
            6 => [
                'signature_data'     => 'required|string',
                'signature_name'     => 'required|string|max:150',
                'signature_position' => 'required|string|max:100',
                'signature_phone'    => 'nullable|string|max:30',
            ],
            default => [],
        };

        $request->validate($rules);
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
