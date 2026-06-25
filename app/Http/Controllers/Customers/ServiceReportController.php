<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\ServiceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ServiceReportController extends Controller
{
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

        return view('customers.service-reports.index', compact('reports'));
    }

    public function show(ServiceReport $report)
    {
        abort_unless($report->customer_id === auth('customer')->id(), 403);

        $report->load(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images']);

        return view('customers.service-reports.show', compact('report'));
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
}
