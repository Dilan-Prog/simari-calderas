<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(private SalesOrderService $salesOrderService)
    {
    }

    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'quote'])->withCount('items');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('company', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'       => SalesOrder::count(),
            'pendiente'   => SalesOrder::where('status', 'pendiente')->count(),
            'parcial'     => SalesOrder::where('status', 'parcialmente_entregado')->count(),
            'entregado'   => SalesOrder::where('status', 'entregado')->count(),
        ];

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'sales-orders.index')
            ->value('columns');

        return view('admin.sales-orders.index', compact('orders', 'stats', 'visibleColumns'));
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'quote', 'createdBy', 'items.product']);

        return view('admin.sales-orders.show', ['order' => $salesOrder]);
    }

    public function registerDelivery(Request $request, SalesOrder $salesOrder, SalesOrderItem $item)
    {
        abort_unless($item->sales_order_id === $salesOrder->id, 404);

        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $item->pending,
        ]);

        $this->salesOrderService->registerDelivery($item, (int) $request->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Entrega registrada correctamente.',
        ]);
    }

    public function updateStatus(Request $request, SalesOrder $salesOrder)
    {
        $request->validate(['status' => 'required|in:cancelado,pendiente,en_preparacion']);
        $salesOrder->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
