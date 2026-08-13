<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use App\Models\StoreOrderStatusLog;
use App\Models\UserColumnPreference;
use App\Support\StoreOrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Órdenes del checkout público de la tienda (StoreOrder) — distinto de
 * "Pedidos" (SalesOrder, generados desde Cotizaciones B2B aceptadas).
 */
class StoreOrderController extends Controller
{
    public function index(Request $request): View
    {
        // Una sola query agrupada para KPIs y contadores de tabs — evita
        // 7+ queries individuales por estatus.
        $byStatus = StoreOrder::selectRaw('status, count(*) as c, sum(total) as s')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $countFor = fn (string $status) => (int) ($byStatus[$status]->c ?? 0);
        $sumFor = fn (string $status) => (float) ($byStatus[$status]->s ?? 0);

        $pagadasStatuses = ['pagado', 'en_preparacion', 'enviado', 'entregado'];

        $kpis = [
            'total' => [
                'count' => (int) $byStatus->sum('c'),
                'sum'   => (float) $byStatus->sum('s'),
            ],
            'pendientes' => [
                'count' => $countFor('pendiente_pago'),
                'sum'   => $sumFor('pendiente_pago'),
            ],
            'pagadas' => [
                'count' => array_sum(array_map($countFor, $pagadasStatuses)),
                'sum'   => array_sum(array_map($sumFor, $pagadasStatuses)),
            ],
            'canceladas' => [
                'count' => $countFor('cancelado'),
                'sum'   => $sumFor('cancelado'),
            ],
        ];

        $tabCounts = ['todos' => (int) $byStatus->sum('c')];
        foreach (array_keys(StoreOrderStatus::META) as $status) {
            $tabCounts[$status] = $countFor($status);
        }

        $orders = $this->filteredOrdersQuery($request)
            ->with(['paymentMethod', 'customer'])
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $visibleColumns = UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'orders.index')
            ->value('columns');

        $filters = $request->only(['q', 'status', 'from', 'to']);

        return view('admin.orders.index', compact('orders', 'kpis', 'tabCounts', 'filters', 'visibleColumns'));
    }

    public function show(StoreOrder $order): View
    {
        $order->load(['items.product', 'paymentMethod', 'customer', 'statusLogs.changedBy']);

        $allowedTransitions = StoreOrderStatus::allowedTransitions($order->status);

        return view('admin.orders.show', compact('order', 'allowedTransitions'));
    }

    public function updateStatus(Request $request, StoreOrder $order)
    {
        $allowed = StoreOrderStatus::allowedTransitions($order->status);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($allowed)],
            'motivo' => ['nullable', 'string', Rule::in(array_keys(StoreOrderStatus::MOTIVOS))],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'cancelado' && empty($validated['motivo'])) {
            return back()->withErrors(['motivo' => 'Selecciona un motivo de cancelación.'])->withInput();
        }

        if (StoreOrderStatus::isBackward($order->status, $validated['status']) && empty($validated['note'])) {
            return back()->withErrors(['note' => 'Explica en una nota interna por qué se retrocede el estatus.'])->withInput();
        }

        DB::transaction(function () use ($order, $validated) {
            StoreOrderStatusLog::create([
                'store_order_id'     => $order->id,
                'from_status'        => $order->status,
                'to_status'          => $validated['status'],
                'note'               => $validated['note'] ?? null,
                'motivo'             => $validated['motivo'] ?? null,
                'changed_by_user_id' => auth()->id(),
            ]);

            $order->update(['status' => $validated['status']]);
        });

        return back()->with('success', 'Estatus de la orden actualizado.');
    }

    public function export(Request $request)
    {
        $orders = $this->filteredOrdersQuery($request)->with(['paymentMethod', 'customer'])->get();

        $rows = $orders->map(fn (StoreOrder $o) => [
            'folio'   => $o->order_number,
            'cliente' => $o->contact_name,
            'email'   => $o->contact_email,
            'tipo'    => $o->customer_id ? 'Cuenta' : 'Invitado',
            'total'   => $o->total,
            'pago'    => $o->paymentMethod?->name,
            'estatus' => StoreOrderStatus::meta($o->status)['label'] ?? $o->status,
            'fecha'   => $o->created_at?->format('d/m/Y H:i'),
        ]);

        $headings = ['Folio', 'Cliente', 'Email', 'Tipo', 'Total', 'Método de pago', 'Estatus', 'Fecha'];

        return Excel::download(new ReportExport($rows, $headings), 'ordenes-' . now()->format('Y-m-d') . '.csv', ExcelFormat::CSV);
    }

    public function downloadTaxCertificate(StoreOrder $order)
    {
        abort_unless($order->requires_invoice && $order->tax_certificate_path, 404);
        abort_unless(Storage::disk('local')->exists($order->tax_certificate_path), 404);

        return response()->download(
            Storage::disk('local')->path($order->tax_certificate_path),
            'constancia-fiscal-' . $order->order_number . '.' . pathinfo($order->tax_certificate_path, PATHINFO_EXTENSION)
        );
    }

    private function filteredOrdersQuery(Request $request): Builder
    {
        $query = StoreOrder::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('order_number', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('contact_email', 'like', "%{$q}%")
                    ->orWhere('contact_phone', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query;
    }
}
