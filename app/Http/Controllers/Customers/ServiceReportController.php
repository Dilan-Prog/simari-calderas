<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\ServiceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\SatisfactionSurveyService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceReportController extends Controller
{
    public function __construct(private SatisfactionSurveyService $survey)
    {
    }

    public function index(Request $request)
    {
        $query = ServiceReport::where('customer_id', auth('customer')->id())
            ->with('assignedUser')
            ->latest('service_date');

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

        $pendingRatingIds = $this->survey->pendingFor(auth('customer')->user())->pluck('id')->all();

        return view('customers.service-reports.index', compact('reports', 'pendingRatingIds'));
    }

    public function show(ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        $canRate = $this->survey->isRateable($report);
        $myRating = $this->survey->submittedFor($report);

        return view('customers.service-reports.show', compact('report', 'canRate', 'myRating'));
    }

    public function downloadPdf(ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$report->report_number}.pdf");
    }

    public function previewPdf(ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$report->report_number}.pdf");
    }

    // ── Califica este servicio ────────────────────────────────────────────────

    public function rate(Request $request, ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        $show = route('customer.service-reports.show', $report);

        if ($request->isMethod('post')) {
            try {
                $this->survey->submit($report, [
                    'card_id' => $request->input('card_id'),
                    'chips'   => (array) $request->input('chips', []),
                    'comment' => $request->input('comment'),
                ]);
            } catch (ValidationException $e) {
                return redirect(route('customer.service-reports.rate', $report) . ($request->boolean('editar') ? '?editar=1' : ''))->withInput()->withErrors($e->errors());
            }

            return redirect()->route('customer.service-reports.rate', $report)->with('rated', true);
        }

        $data = $this->survey->screenData($report, (bool) session('rated'), $request->boolean('editar'));
        if ($data === null) {
            return redirect($show)->with('error', 'Este reporte no se puede calificar por ahora.');
        }

        return view('service-ratings.rate', $data + [
            'action'     => route('customer.service-reports.rate', $report),
            'skipAction' => route('customer.service-reports.rate.skip', $report),
            'backUrl'    => $show,
            'reportUrl'  => $show,
            'editUrl'    => route('customer.service-reports.rate', $report) . '?editar=1',
        ]);
    }

    public function skipRating(ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        if ($report->status === 'signed') {
            $this->survey->skip($report);
        }

        return redirect()->route('customer.service-reports.show', $report);
    }
}
