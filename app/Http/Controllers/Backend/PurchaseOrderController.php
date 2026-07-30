<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Products;;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'createdBy'])
            ->withCount('items');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('po_number', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn($q2) => $q2->where('company_name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('order_date', $request->date);
        }

        $orders = $query->orderByDesc('order_date')->paginate(15)->withQueryString();

        $stats = [
            'total'    => PurchaseOrder::count(),
            'pending'  => PurchaseOrder::where('status', 'pending')->count(),
            'accepted' => PurchaseOrder::where('status', 'accepted')->count(),
            'rejected' => PurchaseOrder::where('status', 'rejected')->count(),
        ];

        return view('admin.purchase-orders.index', compact('orders', 'stats'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')
            ->get(['id', 'company_name', 'contact_name', 'email', 'phone', 'rfc', 'payment_terms']);

        $nextPoNumber          = PurchaseOrder::generatePoNumber();
        $nextInternalReference = PurchaseOrder::generateInternalReference();

        return view(
            'admin.purchase-orders.create.create',
            compact('suppliers', 'nextPoNumber', 'nextInternalReference')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'order_date'         => 'required|date',
            'status'             => 'required|in:pending,accepted,rejected',
            'internal_reference' => 'required|string|max:50|unique:purchase_orders,internal_reference',
            'subtotal'           => 'required|numeric|min:0',
            'tax_rate'           => 'required|numeric|min:0|max:100',
            'tax_total'          => 'required|numeric|min:0',
            'total'              => 'required|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:180',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
        ]);

        $order = new PurchaseOrder();
        $order->po_number              = $request->po_number;
        $order->supplier_id            = $request->supplier_id;
        $order->created_by_user_id     = $request->created_by_user_id;
        $order->status                 = $request->status;
        $order->order_date             = $request->order_date;
        $order->expected_delivery_date = $request->expected_delivery_date ?? null;
        $order->internal_reference     = $request->internal_reference     ?? null;
        $order->notes                  = $request->notes                  ?? null;
        $order->currency               = $request->currency ?? 'MXN';
        $order->subtotal               = $request->subtotal;
        $order->discount_total         = $request->discount_total ?? 0;
        $order->tax_rate               = $request->tax_rate;
        $order->tax_total              = $request->tax_total;
        $order->total                  = $request->total;
        $order->save();

        // Save items
        foreach ($request->items as $index => $itemData) {
            $order->items()->create([
                'product_id'       => $itemData['product_id']       ?? null,
                'product_name'     => $itemData['product_name'],
                'product_sku'      => $itemData['product_sku']      ?? null,
                'comment'          => $itemData['comment']          ?? null,
                'quantity'         => $itemData['quantity'],
                'unit_price'       => $itemData['unit_price'],
                'discount_percent' => $itemData['discount_percent'] ?? 0,
                'line_total'       => $itemData['line_total']       ?? 0,
                'sort_order'       => $itemData['sort_order']       ?? $index,
            ]);
        }

        // Redirect based on action button
        if ($request->action === 'save') {
            return redirect()->route('admin.purchase-orders.index', $order->id)
                ->with('success', 'Orden de compra creada correctamente.');
        }

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'Borrador guardado correctamente.');
    }

    // Buscador de productos de la OC, filtrado al proveedor elegido — solo
    // devuelve productos vinculados a ese proveedor vía suppliers_products,
    // con su SKU/costo propios (con respaldo al SKU/costo genérico del
    // producto si el proveedor no los tiene capturados todavía).
    public function productsBySupplier(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $products = Products::where('is_active', true)
            ->whereHas('suppliers', fn ($q) => $q->where('suppliers.id', $request->supplier_id))
            ->with(['suppliers' => fn ($q) => $q->where('suppliers.id', $request->supplier_id)])
            ->get(['id', 'name', 'sku', 'cost'])
            ->map(function ($p) {
                $pivot = $p->suppliers->first()?->pivot;

                return [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'sku'   => $pivot?->sku ?: ($p->sku ?? ''),
                    'price' => $pivot?->cost !== null ? (float) $pivot->cost : (float) $p->cost,
                ];
            });

        return response()->json($products);
    }

    public function show(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'createdBy', 'items'])
            ->findOrFail($id);

        return view('admin.purchase-orders.show.show', compact('order'));
    }

    public function edit(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'createdBy', 'items'])
            ->findOrFail($id);

        $suppliers = Supplier::where('status', 'active')
            ->get(['id', 'company_name', 'contact_name', 'email', 'phone', 'rfc', 'payment_terms']);

        $existingItems = $order->items->map(fn($item) => [
            'id'        => $item->id,
            'productId' => $item->product_id,
            'fromCatalog' => $item->product_id !== null,
            'name'      => $item->product_name,
            'sku'       => $item->product_sku  ?? '',
            'comment'   => $item->comment      ?? '',
            'qty'       => $item->quantity,
            'price'     => (float) $item->unit_price,
            'disc'      => (float) $item->discount_percent,
            'lineTotal' => (float) $item->line_total,
        ]);

        return view(
            'admin.purchase-orders.edit.edit',
            compact('order', 'suppliers', 'existingItems')
        );
    }

    public function update(Request $request, string $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        $request->validate([
            'supplier_id'  => 'required|exists:suppliers,id',
            'order_date'   => 'required|date',
            'status'       => 'required|in:pending,accepted,rejected',
            'subtotal'     => 'required|numeric|min:0',
            'tax_rate'     => 'required|numeric|min:0|max:100',
            'tax_total'    => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
            'items'        => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:180',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
        ]);

        $order->supplier_id            = $request->supplier_id;
        $order->status                 = $request->status;
        $order->order_date             = $request->order_date;
        $order->expected_delivery_date = $request->expected_delivery_date ?? null;
        $order->internal_reference     = $request->internal_reference     ?? null;
        $order->notes                  = $request->notes                  ?? null;
        $order->subtotal               = $request->subtotal;
        $order->discount_total         = $request->discount_total ?? 0;
        $order->tax_rate               = $request->tax_rate;
        $order->tax_total              = $request->tax_total;
        $order->total                  = $request->total;
        $order->save();

        // Delete existing items and recreate
        $order->items()->delete();

        foreach ($request->items as $index => $itemData) {
            $order->items()->create([
                'product_id'       => $itemData['product_id']       ?? null,
                'product_name'     => $itemData['product_name'],
                'product_sku'      => $itemData['product_sku']      ?? null,
                'comment'          => $itemData['comment']          ?? null,
                'quantity'         => $itemData['quantity'],
                'unit_price'       => $itemData['unit_price'],
                'discount_percent' => $itemData['discount_percent'] ?? 0,
                'line_total'       => $itemData['line_total']       ?? 0,
                'sort_order'       => $itemData['sort_order']       ?? $index,
            ]);
        }

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(string $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status === 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar una orden aceptada.',
            ], 422);
        }

        $order->delete();
        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'Orden eliminada correctamente.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,accepted,rejected']);
        $order->status = $request->status;
        $order->save();
        return response()->json(['success' => true]);
    }

    public function downloadPdf(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'items'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.purchase-orders.pdf', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$order->po_number}.pdf");
    }

    public function previewPdf(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'items'])->findOrFail($id);
        $pdf = Pdf::loadView('admin.purchase-orders.pdf', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$order->po_number}.pdf");
    }
}
