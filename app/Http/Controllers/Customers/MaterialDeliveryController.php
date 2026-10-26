<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MaterialDeliveryReport;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Entregas de material del cliente autenticado: sus pedidos de entrega
 * (SalesOrder) y los reportes de entrega ya firmados. Sin datos monetarios,
 * sin notas internas del pedido ni usuario creador.
 *
 * Los parametros de ruta se resuelven a mano con consultas acotadas al cliente:
 * un registro ajeno (o un borrador) responde 404, nunca 403.
 */
class MaterialDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $customerId = (int) auth('customer')->id();
        $tab        = $request->query('tab') === 'reportes' ? 'reportes' : 'pedidos';

        $statuses = ['pendiente', 'en_preparacion', 'parcialmente_entregado', 'entregado', 'cancelado'];

        // Contadores del encabezado (sobre todos los pedidos del cliente, sin filtros).
        $counts = SalesOrder::where('customer_id', $customerId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'total'        => (int) $counts->sum(),
            'preparacion'  => (int) ($counts['en_preparacion'] ?? 0),
            'parcial'      => (int) ($counts['parcialmente_entregado'] ?? 0),
            'entregado'    => (int) ($counts['entregado'] ?? 0),
        ];

        $reportsTotal = $this->reportsQuery($customerId)->count();

        $orders  = null;
        $reports = null;
        $years   = [];
        $status  = $request->query('status');
        $status  = in_array($status, $statuses, true) ? $status : null;
        $yearRaw = (string) $request->query('year', '');
        $year    = ctype_digit($yearRaw) && (int) $yearRaw >= 2000 && (int) $yearRaw <= 2100 ? (int) $yearRaw : null;
        $search  = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        if ($tab === 'pedidos') {
            $years = SalesOrder::where('customer_id', $customerId)
                ->whereNotNull('created_at')
                ->selectRaw('YEAR(created_at) as y')
                ->distinct()
                ->orderByDesc('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->all();

            $orders = SalesOrder::where('customer_id', $customerId)
                ->select(['id', 'order_number', 'quote_id', 'customer_id', 'status', 'created_at'])
                ->with('quote:id,quote_number,status')
                ->withCount('items')
                ->withSum('items as qty_ordered', 'quantity_ordered')
                ->withSum('items as qty_delivered', 'quantity_delivered')
                ->when($status, fn (Builder $q) => $q->where('status', $status))
                ->when($year, fn (Builder $q) => $q->whereYear('created_at', $year))
                ->when($search !== '', function (Builder $q) use ($search) {
                    $like = '%' . addcslashes($search, '%_') . '%';
                    $q->where(function (Builder $w) use ($like) {
                        $w->where('order_number', 'like', $like)
                          ->orWhereHas('items', fn (Builder $i) => $i->where('product_name', 'like', $like))
                          ->orWhereHas('quote', fn (Builder $qt) => $qt->where('quote_number', 'like', $like));
                    });
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString();
        } else {
            $reports = $this->reportsQuery($customerId)
                ->select([
                    'id', 'report_number', 'sales_order_id', 'customer_id', 'delivery_date',
                    'received_by_name', 'status', 'signed_at', 'created_at',
                ])
                ->with('salesOrder:id,order_number')
                ->withCount('items')
                ->orderByDesc('delivery_date')
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString();
        }

        return view('customers.deliveries.index', [
            'tab'          => $tab,
            'stats'        => $stats,
            'reportsTotal' => $reportsTotal,
            'orders'       => $orders,
            'reports'      => $reports,
            'years'        => $years,
            'statuses'     => $statuses,
            'status'       => $status,
            'year'         => $year,
            'search'       => $search,
        ]);
    }

    public function showOrder(string|int $order)
    {
        $customerId = (int) auth('customer')->id();

        abort_unless(ctype_digit((string) $order), 404);

        $order = SalesOrder::where('customer_id', $customerId)
            ->select(['id', 'order_number', 'quote_id', 'customer_id', 'status', 'created_at', 'updated_at'])
            ->with(['items', 'quote:id,quote_number,status'])
            ->findOrFail((int) $order);

        $reports = $this->reportsQuery($customerId)
            ->where('sales_order_id', $order->id)
            ->select(['id', 'report_number', 'sales_order_id', 'customer_id', 'delivery_date', 'received_by_name', 'status', 'signed_at'])
            ->withCount('items')
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->get();

        $qtyOrdered   = (float) $order->items->sum('quantity_ordered');
        $qtyDelivered = (float) $order->items->sum('quantity_delivered');

        return view('customers.deliveries.order', [
            'order'        => $order,
            'reports'      => $reports,
            'qtyOrdered'   => $qtyOrdered,
            'qtyDelivered' => $qtyDelivered,
            'quoteLinkable' => $order->quote && $order->quote->status !== 'draft',
        ]);
    }

    public function showReport(string|int $report)
    {
        $report = $this->findSignedReport($report, ['salesOrder:id,order_number', 'items', 'images']);

        $signature = null;
        $raw = (string) $report->signature_data;
        if ($raw !== '' && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $raw) === 1) {
            $signature = $raw;
        }

        return view('customers.deliveries.report', [
            'report'    => $report,
            'signature' => $signature,
        ]);
    }

    public function reportPdf(Request $request, string|int $report)
    {
        $report = $this->findSignedReport($report, ['salesOrder.customer', 'items', 'images']);

        $pdf = Pdf::loadView('admin.material-delivery-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        $name = "{$report->report_number}.pdf";

        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Reportes FIRMADOS que pertenecen al cliente (directo o por su pedido). */
    private function reportsQuery(int $customerId): Builder
    {
        return MaterialDeliveryReport::query()
            ->where('status', 'signed')
            ->where(function (Builder $q) use ($customerId) {
                $q->where('customer_id', $customerId)
                  ->orWhereHas('salesOrder', fn (Builder $o) => $o->where('customer_id', $customerId));
            });
    }

    private function findSignedReport(string|int $id, array $with): MaterialDeliveryReport
    {
        abort_unless(ctype_digit((string) $id), 404);

        return $this->reportsQuery((int) auth('customer')->id())
            ->with($with)
            ->findOrFail((int) $id);
    }
}
