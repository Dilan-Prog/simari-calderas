<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MaterialDeliveryReport;
use App\Models\MaterialDeliveryReportImage;
use App\Models\SalesOrder;
use App\Services\MaterialDeliveryReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialDeliveryReportController extends Controller
{
    public function __construct(private MaterialDeliveryReportService $service) {}

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = MaterialDeliveryReport::with('salesOrder.customer')->latest('delivery_date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('report_number', 'like', "%{$search}%")
                  ->orWhereHas('salesOrder', function ($sq) use ($search) {
                      $sq->where('order_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('salesOrder.customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('delivery_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('delivery_date', '<=', $request->date_to);
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('admin.material-delivery-reports.index', compact('reports'));
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create()
    {
        $salesOrders = $this->getEligibleSalesOrders();

        return view('admin.material-delivery-reports.create', compact('salesOrders'));
    }

    private function getEligibleSalesOrders()
    {
        return SalesOrder::whereIn('status', ['en_preparacion', 'parcialmente_entregado', 'entregado'])
            ->with('customer')
            ->latest()
            ->get(['id', 'order_number', 'customer_id', 'status']);
    }

    // ── Store (step 1) ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id'   => [
                'required',
                Rule::exists('sales_orders', 'id')->whereIn('status', ['en_preparacion', 'parcialmente_entregado', 'entregado']),
            ],
            'delivery_date'    => 'required|date',
            'delivery_location'=> 'nullable|string|max:200',
        ]);

        $report = $this->service->store($validated, auth()->id());

        return redirect()->route('admin.material-delivery-reports.step', [$report, 2])
            ->with('success', "Reporte {$report->report_number} creado. Continúa con el paso 2.");
    }

    // ── Steps ──────────────────────────────────────────────────────────────────

    public function step(MaterialDeliveryReport $report, int $step)
    {
        // Prevent skipping steps
        if ($step > $report->current_step + 1) {
            return redirect()->route('admin.material-delivery-reports.step', [$report, $report->current_step + 1])
                ->with('error', 'No puedes saltar pasos. Completa el paso anterior primero.');
        }

        $report->load(['salesOrder.customer', 'items']);

        $data = [
            'report' => $report,
            'step'   => $step,
        ];

        if ($step === 1) {
            $data['salesOrders'] = $this->getEligibleSalesOrders();
        }

        if ($step === 4) {
            $data['images'] = $report->images()->orderBy('sort_order')->get();
        }

        return view("admin.material-delivery-reports.steps.step{$step}", $data);
    }

    public function saveStep(Request $request, MaterialDeliveryReport $report, int $step)
    {
        if (!$report->isEditable()) {
            return redirect()->route('admin.material-delivery-reports.show', $report)
                ->with('error', 'Este reporte ya está firmado y no puede ser modificado.');
        }

        $this->validateStep($request, $step);

        $data = $request->all();

        $this->service->updateStep($report, $data, $step);

        // El wizard tiene 6 pasos (Datos, Líneas, Observaciones, Fotos, Resumen,
        // Firma); el paso 6 se firma vía sign(), no vía saveStep().
        $nextStep = $step + 1;

        return $nextStep > 5
            ? redirect()->route('admin.material-delivery-reports.step', [$report, 6])->with('success', "Paso {$step} guardado correctamente.")
            : redirect()->route('admin.material-delivery-reports.step', [$report, $nextStep])->with('success', "Paso {$step} guardado correctamente.");
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(MaterialDeliveryReport $report)
    {
        $report->load(['salesOrder.customer', 'items', 'images']);

        return view('admin.material-delivery-reports.show', compact('report'));
    }

    // ── Edit ───────────────────────────────────────────────────────────────────

    public function edit(MaterialDeliveryReport $report)
    {
        if (!$report->isEditable()) {
            return redirect()->route('admin.material-delivery-reports.show', $report)
                ->with('error', 'Los reportes firmados no pueden ser editados.');
        }

        return redirect()->route('admin.material-delivery-reports.step', [$report, $report->current_step]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(MaterialDeliveryReport $report)
    {
        if (!$report->isDeletable()) {
            return redirect()->route('admin.material-delivery-reports.index')
                ->with('error', 'Solo se pueden eliminar reportes en estado Borrador.');
        }

        $reportNumber = $report->report_number;
        $report->delete();

        return redirect()->route('admin.material-delivery-reports.index')
            ->with('success', "Reporte {$reportNumber} eliminado correctamente.");
    }

    // ── PDF ────────────────────────────────────────────────────────────────────

    public function downloadPdf(MaterialDeliveryReport $report)
    {
        $report->load(['salesOrder.customer', 'items', 'images']);

        $pdf = Pdf::loadView('admin.material-delivery-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$report->report_number}.pdf");
    }

    public function previewPdf(MaterialDeliveryReport $report)
    {
        $report->load(['salesOrder.customer', 'items', 'images']);

        $pdf = Pdf::loadView('admin.material-delivery-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$report->report_number}.pdf");
    }

    // ── Sign ───────────────────────────────────────────────────────────────────

    public function sign(Request $request, MaterialDeliveryReport $report)
    {
        if (!$report->isEditable()) {
            return redirect()->route('admin.material-delivery-reports.show', $report)
                ->with('error', 'Este reporte ya ha sido firmado.');
        }

        $request->validate([
            'signature_data'        => 'required|string',
            'received_by_name'      => 'required|string|max:150',
            'received_by_position'  => 'nullable|string|max:100',
            'received_by_phone'     => 'nullable|string|max:30',
        ]);

        $this->service->saveSignature($report, $request->only([
            'signature_data',
            'received_by_name',
            'received_by_position',
            'received_by_phone',
        ]));

        return redirect()->route('admin.material-delivery-reports.show', $report)
            ->with('success', "Reporte {$report->report_number} firmado y completado exitosamente.");
    }

    // ── Image delete ──────────────────────────────────────────────────────────

    public function destroyImage(MaterialDeliveryReport $report, MaterialDeliveryReportImage $image)
    {
        if (!$report->isEditable() && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Este reporte ya está firmado; solo un administrador puede modificar sus imágenes.');
        }

        $this->service->deleteReportImage($image);

        return back()->with('success', 'Imagen eliminada correctamente.');
    }

    // ── Validation per step ────────────────────────────────────────────────────

    private function validateStep(Request $request, int $step): void
    {
        $rules = match($step) {
            1 => [
                // sales_order_id NO se revalida aquí: solo se fija una vez en
                // store() y la vista del paso 1 lo muestra de solo lectura
                // (cambiar el pedido invalidaría las líneas ya capturadas en
                // el paso 2, que están ligadas a sus SalesOrderItem).
                'delivery_date'     => 'required|date',
                'delivery_location' => 'nullable|string|max:200',
            ],
            2 => [
                // La vista envía TODAS las líneas del pedido (no solo las que
                // se entregan en este evento) para poder mostrar cant.
                // pedida/pendiente de cada una. quantity_delivered_in_event
                // NO es required|min:1 aquí: una línea en 0 significa "no se
                // entrega en este evento" y se descarta en el servicio.
                'items'                                 => 'required|array|min:1',
                'items.*.sales_order_item_id'            => 'required|integer|exists:sales_order_items,id',
                'items.*.quantity_delivered_in_event'    => 'nullable|integer|min:0',
            ],
            3 => [
                'observations' => 'nullable|string',
            ],
            4 => [
                'images'   => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            ],
            default => [],
        };

        $request->validate($rules, [
            'images.*.image' => 'Uno de los archivos no es una imagen válida.',
            'images.*.mimes' => 'Formato de imagen no compatible. Usa JPG, PNG o WEBP.',
            'images.*.max'   => 'Cada imagen debe pesar máximo 5 MB.',
        ]);
    }
}
