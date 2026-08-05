<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ChemicalPlanningExport;
use App\Http\Controllers\Controller;
use App\Models\ChemicalProjection;
use App\Models\Customer;
use App\Models\Products;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ChemicalPlanningController extends Controller
{
    public function index()
    {
        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'chemical-planning.index')
            ->value('columns');

        return view('admin.chemical-planning.index', [
            'rows' => $this->buildRows(),
            'visibleColumns' => $visibleColumns,
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildRows();

        if ($request->input('format') === 'pdf') {
            return Pdf::loadView('admin.chemical-planning.pdf', ['rows' => $rows])
                ->setPaper('a4', 'landscape')->download('planeacion-quimicos-' . now()->format('Y-m-d') . '.pdf');
        }

        $filename = 'planeacion-quimicos-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ChemicalPlanningExport($rows), $filename);
    }

    public function updateProjection(Request $request)
    {
        $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'product_id'         => 'required|exists:products,id',
            'projected_quantity' => 'required|numeric|min:0',
            'period'             => 'nullable|string|max:20',
        ]);

        ChemicalProjection::updateOrCreate(
            ['customer_id' => $request->customer_id, 'product_id' => $request->product_id],
            ['projected_quantity' => $request->projected_quantity, 'period' => $request->period]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Una fila por combinación cliente+producto — agrega entregado/pendiente
     * desde los Pedidos (sales_order_items), cruza con la proyección manual
     * y con el stock actual del producto.
     */
    protected function buildRows(): \Illuminate\Support\Collection
    {
        // DB::table() (no Eloquent) a propósito: SalesOrderItem define un
        // accessor getPendingAttribute() para su uso normal (progreso de
        // entrega por línea) que colisionaría con el alias SQL "pending" de
        // esta agregación — Eloquent prioriza el accessor sobre la columna
        // cruda del SELECT y el valor real quedaría siempre en 0.
        $aggregates = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereNotNull('sales_order_items.product_id')
            ->whereNotNull('sales_orders.customer_id')
            ->where('sales_orders.status', '!=', 'cancelado')
            ->select('sales_orders.customer_id', 'sales_order_items.product_id')
            ->selectRaw('SUM(sales_order_items.quantity_delivered) as delivered_total')
            ->selectRaw('SUM(sales_order_items.quantity_ordered - sales_order_items.quantity_delivered) as pending_total')
            ->groupBy('sales_orders.customer_id', 'sales_order_items.product_id')
            ->get();

        if ($aggregates->isEmpty()) {
            return collect();
        }

        $customerIds = $aggregates->pluck('customer_id')->unique();
        $productIds = $aggregates->pluck('product_id')->unique();

        $customers = Customer::whereIn('id', $customerIds)->get()->keyBy('id');
        $products = Products::whereIn('id', $productIds)->get()->keyBy('id');

        $projections = ChemicalProjection::whereIn('customer_id', $customerIds)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy(fn ($p) => $p->customer_id . '-' . $p->product_id);

        return $aggregates->map(function ($row) use ($customers, $products, $projections) {
            $customer = $customers->get($row->customer_id);
            $product = $products->get($row->product_id);
            $projection = $projections->get($row->customer_id . '-' . $row->product_id);

            return [
                'customer_id'        => $row->customer_id,
                'customer_name'      => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : '—',
                'product_id'         => $row->product_id,
                'product_name'       => $product->name ?? '—',
                'delivered'          => (int) $row->delivered_total,
                'pending'            => (int) $row->pending_total,
                'projected_quantity' => $projection ? (float) $projection->projected_quantity : 0,
                'period'             => $projection->period ?? null,
                'stock'              => $product->stock ?? 0,
                'stock_unit'         => $product->stock_unit ?? '',
            ];
        })->values();
    }
}
