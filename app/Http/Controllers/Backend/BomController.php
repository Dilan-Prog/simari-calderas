<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Products;
use App\Services\BomService;
use Illuminate\Http\Request;

class BomController extends Controller
{
    public function __construct(private BomService $service)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', '');

        $boms = Bom::with('product:id,name,sku,price,price_includes_tax,currency,cost')
            ->withCount('lines')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('product_id')->orderByDesc('version')
            ->paginate(20)->withQueryString();

        $costs = [];
        foreach ($boms as $bom) {
            $costs[$bom->id] = $this->service->costOfBom($bom);
        }

        return view('admin.boms.index', compact('boms', 'costs', 'q', 'status'));
    }

    public function create(Request $request)
    {
        $product = $request->filled('product_id') ? Products::find($request->get('product_id')) : null;
        $bom = new Bom(['yield_quantity' => 1, 'is_active' => $product ? !$product->activeBom()->exists() : true]);
        if ($product) {
            $bom->setRelation('product', $product);
        }

        return view('admin.boms.form', ['bom' => $bom, 'lines' => collect(), 'product' => $product, 'isEdit' => false]);
    }

    public function store(Request $request)
    {
        [$data, $lines] = $this->validated($request);
        $bom = $this->service->create($data, $lines);

        return redirect()->route('admin.boms.show', $bom)->with('success', "Receta v{$bom->version} creada.");
    }

    public function show(Bom $bom)
    {
        $bom->load('product.activeBom', 'lines.component');
        $product = $bom->product;
        $tree = $this->service->tree($bom);
        $cost = $this->service->costOfBom($bom);
        $price = $product->base_price;
        $margin = $price > 0 ? round((($price - $cost) / $price) * 100, 1) : null;
        $otherVersions = Bom::where('product_id', $bom->product_id)->orderByDesc('version')
            ->withCount('lines')->get();

        return view('admin.boms.show', compact('bom', 'product', 'tree', 'cost', 'price', 'margin', 'otherVersions'));
    }

    public function edit(Bom $bom)
    {
        $bom->load('product', 'lines.component');

        return view('admin.boms.form', ['bom' => $bom, 'lines' => $bom->lines, 'product' => $bom->product, 'isEdit' => true]);
    }

    public function update(Request $request, Bom $bom)
    {
        [$data, $lines] = $this->validated($request, $bom->product_id);
        $this->service->update($bom, $data, $lines);

        return redirect()->route('admin.boms.show', $bom)->with('success', 'Receta actualizada.');
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();

        return redirect()->route('admin.boms.index')->with('success', 'Receta eliminada.');
    }

    public function duplicate(Bom $bom)
    {
        $copy = $this->service->duplicate($bom);

        return redirect()->route('admin.boms.edit', $copy)->with('success', "Se creó la versión v{$copy->version} a partir de v{$bom->version}.");
    }

    public function activate(Bom $bom)
    {
        $this->service->activate($bom);

        return back()->with('success', "La versión v{$bom->version} es ahora la vigente.");
    }

    /** Buscador de productos (componentes y producto fabricado). */
    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $exclude = (int) $request->get('exclude', 0);

        $items = Products::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude))
            ->withExists(['activeBom as has_bom'])
            ->orderBy('name')->limit(15)
            ->get(['id', 'name', 'sku', 'stock_unit', 'cost', 'currency'])
            ->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                'unit' => $p->stock_unit, 'has_bom' => (bool) $p->has_bom,
            ]);

        return response()->json($items);
    }

    private function validated(Request $request, ?int $fixedProductId = null): array
    {
        $rules = [
            'name'                         => 'nullable|string|max:255',
            'yield_quantity'               => 'required|numeric|gt:0|max:99999999',
            'notes'                        => 'nullable|string|max:5000',
            'is_active'                    => 'nullable|boolean',
            'lines'                        => 'required|array|min:1',
            'lines.*.component_product_id' => 'required|integer|exists:products,id',
            'lines.*.quantity'             => 'required|numeric|gt:0|max:99999999',
            'lines.*.unit'                 => 'nullable|string|max:30',
            'lines.*.scrap_percent'        => 'nullable|numeric|min:0|max:999.99',
            'lines.*.notes'                => 'nullable|string|max:255',
        ];
        if (!$fixedProductId) {
            $rules['product_id'] = 'required|integer|exists:products,id';
        }
        $validated = $request->validate($rules, [
            'product_id.required' => 'Selecciona el producto fabricado.',
            'lines.required'      => 'Agrega al menos un componente.',
            'lines.min'           => 'Agrega al menos un componente.',
        ]);

        $data = [
            'product_id'     => $fixedProductId ?: (int) $validated['product_id'],
            'name'           => $validated['name'] ?? null,
            'yield_quantity' => $validated['yield_quantity'],
            'notes'          => $validated['notes'] ?? null,
            'is_active'      => $request->boolean('is_active'),
        ];

        return [$data, array_values($validated['lines'])];
    }
}
