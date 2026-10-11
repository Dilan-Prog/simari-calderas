<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Products;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\Warehouse;
use App\Services\StockCountService;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    public function __construct(private StockCountService $service)
    {
    }

    public function index(Request $request)
    {
        $query = StockCount::with('warehouse')->withCount([
            'lines', 'lines as counted_lines_count' => fn ($q) => $q->whereNotNull('counted_quantity'),
        ]);

        if ($s = trim((string) $request->input('search'))) {
            $query->where('number', 'like', "%{$s}%");
        }
        if (in_array($request->input('status'), ['abierto', 'en_conteo', 'cerrado', 'ajustado'], true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        return view('admin.stock-counts.index', [
            'counts' => $query->orderByDesc('id')->paginate(25)->withQueryString(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'stats' => [
                'abiertos' => StockCount::whereIn('status', ['abierto', 'en_conteo'])->count(),
                'por_ajustar' => StockCount::where('status', 'cerrado')->count(),
                'ajustados' => StockCount::where('status', 'ajustado')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.stock-counts.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'mode' => 'required|in:all,category,products',
            'category_id' => 'required_if:mode,category|nullable|integer|exists:categories,id',
            'product_ids' => 'required_if:mode,products|nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $count = $this->service->create(
                (int) $data['warehouse_id'], $data['mode'], $data['product_ids'] ?? [],
                isset($data['category_id']) ? (int) $data['category_id'] : null, $data['notes'] ?? null, auth()->id()
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.stock-counts.show', $count)->with('success', "Conteo {$count->number} creado.");
    }

    public function show(Request $request, StockCount $count)
    {
        $count->load('warehouse');
        $editable = in_array($count->status, ['abierto', 'en_conteo'], true);

        $linesQuery = $count->lines()->with('product:id,name,sku,cost');
        if ($s = trim((string) $request->input('search'))) {
            $linesQuery->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if ($request->input('filter') === 'pendientes') {
            $linesQuery->whereNull('counted_quantity');
        } elseif ($request->input('filter') === 'diferencias') {
            $linesQuery->where('difference', '!=', 0);
        }
        $lines = $linesQuery->get()->sortBy(fn ($l) => mb_strtolower($l->product->name ?? ''))->values();

        $report = $count->status === 'abierto' || $count->status === 'en_conteo' ? null : $this->service->report($count);
        $progress = [
            'counted' => $count->lines()->whereNotNull('counted_quantity')->count(),
            'total' => $count->lines()->count(),
            'diffs' => $count->lines()->where('difference', '!=', 0)->count(),
        ];

        return view('admin.stock-counts.show', compact('count', 'lines', 'editable', 'report', 'progress'));
    }

    public function updateLine(Request $request, StockCount $count, StockCountLine $line)
    {
        abort_unless($line->count_id === $count->id, 404);

        $request->validate(['counted_quantity' => 'nullable|integer|min:0']);
        $raw = $request->input('counted_quantity');
        $counted = ($raw === null || $raw === '') ? null : (int) $raw;

        try {
            $line = $this->service->saveLine($count, $line->id, $counted);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'difference' => $line->difference,
            'counted' => $count->lines()->whereNotNull('counted_quantity')->count(),
            'total' => $count->lines()->count(),
            'status' => $count->fresh()->status,
        ]);
    }

    public function close(Request $request, StockCount $count)
    {
        try {
            $count = $this->service->close($count, auth()->id());
            if ($request->boolean('apply')) {
                if (!auth()->user()->hasPermission('stock-counts', 'edit')) {
                    abort(403);
                }
                $this->service->applyAdjustments($count);

                return back()->with('success', 'Conteo cerrado y ajustes generados.');
            }
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Conteo cerrado. Revisa las diferencias y genera los ajustes.');
    }

    public function adjust(StockCount $count)
    {
        try {
            $this->service->applyAdjustments($count);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Ajustes generados en el inventario.');
    }

    public function export(StockCount $count)
    {
        $report = $this->service->report($count->load('warehouse'));

        return response()->streamDownload(function () use ($count, $report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Conteo', $count->number, 'Almacén', $count->warehouse->name, 'Estatus', StockCount::statusLabel($count->status)]);
            fputcsv($out, ['SKU', 'Producto', 'Sistema', 'Contado', 'Diferencia', 'Costo unitario', 'Valor diferencia']);
            foreach ($report['rows'] as $r) {
                $l = $r['line'];
                fputcsv($out, [$l->product->sku, $l->product->name, $l->system_quantity, $l->counted_quantity, $l->difference,
                    number_format($r['cost'], 2, '.', ''), number_format($r['value'], 2, '.', '')]);
            }
            fputcsv($out, ['', 'Total faltantes', '', '', '', '', number_format($report['total_negative'], 2, '.', '')]);
            fputcsv($out, ['', 'Total sobrantes', '', '', '', '', number_format($report['total_positive'], 2, '.', '')]);
            fputcsv($out, ['', 'Neto', '', '', '', '', number_format($report['total_net'], 2, '.', '')]);
            fclose($out);
        }, "{$count->number}-diferencias.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(StockCount $count)
    {
        if (!in_array($count->status, ['abierto', 'en_conteo'], true)) {
            return back()->with('error', 'Solo se pueden eliminar conteos abiertos.');
        }
        $count->delete();

        return redirect()->route('admin.stock-counts.index')->with('success', 'Conteo eliminado.');
    }

    /** Buscador de productos para armar un conteo por selección. */
    public function products(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return response()->json(
            Products::where('is_active', true)
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
                ->orderBy('name')->limit(15)->get(['id', 'name', 'sku'])
        );
    }
}
