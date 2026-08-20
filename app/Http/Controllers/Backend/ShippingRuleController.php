<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ShippingRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD del catálogo "Reglas de Envío" (admin). Regla de costo/umbral de
 * envío gratis aplicable a nivel Marca O Categoría (nunca ambas, nunca
 * ninguna) — complementa el shipping_cost/free_shipping_threshold propio de
 * cada Products (feature aparte, no tocada aquí). Mismo estilo de
 * respuestas JSON/patrón de rutas que PaymentMethodController.
 *
 * `scope_type` es un campo solo de formulario (in:brand,category) para que
 * el admin elija el radio button — nunca se persiste, solo decide si
 * brand_id o category_id se guarda (el otro queda null).
 */
class ShippingRuleController extends Controller
{
    public function index()
    {
        $shippingRules = ShippingRule::with(['brand', 'category'])
            ->orderBy('brand_id')
            ->orderBy('category_id')
            ->get();

        $usedBrandIds = ShippingRule::whereNotNull('brand_id')->pluck('brand_id');
        $usedCategoryIds = ShippingRule::whereNotNull('category_id')->pluck('category_id');

        $brands = Brand::where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.shipping-rules.index', compact(
            'shippingRules',
            'brands',
            'categories',
            'usedBrandIds',
            'usedCategoryIds'
        ));
    }

    /**
     * Valida según scope_type: si es 'brand', brand_id requerido+exists y
     * único en shipping_rules (ignorando el registro actual al editar);
     * category_id se descarta (null). Si es 'category', viceversa.
     */
    private function validateShippingRule(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'scope_type' => 'required|in:brand,category',
            'shipping_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $brandUniqueRule = Rule::unique('shipping_rules', 'brand_id');
        $categoryUniqueRule = Rule::unique('shipping_rules', 'category_id');
        if ($ignoreId) {
            $brandUniqueRule = $brandUniqueRule->ignore($ignoreId);
            $categoryUniqueRule = $categoryUniqueRule->ignore($ignoreId);
        }

        if ($data['scope_type'] === 'brand') {
            $validated = $request->validate([
                'brand_id' => ['required', 'integer', 'exists:brands,id', $brandUniqueRule],
            ], [
                'brand_id.unique' => 'Esta marca ya tiene una regla de envío configurada.',
            ]);
            $data['brand_id'] = $validated['brand_id'];
            $data['category_id'] = null;
        } else {
            $validated = $request->validate([
                'category_id' => ['required', 'integer', 'exists:product_categories,id', $categoryUniqueRule],
            ], [
                'category_id.unique' => 'Esta categoría ya tiene una regla de envío configurada.',
            ]);
            $data['category_id'] = $validated['category_id'];
            $data['brand_id'] = null;
        }

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validateShippingRule($request);

        $shippingRule = new ShippingRule();
        $shippingRule->brand_id = $data['brand_id'];
        $shippingRule->category_id = $data['category_id'];
        $shippingRule->shipping_cost = $data['shipping_cost'] ?? null;
        $shippingRule->free_shipping_threshold = $data['free_shipping_threshold'] ?? null;
        $shippingRule->is_active = $request->boolean('is_active', true);
        $shippingRule->save();

        return response()->json([
            'success' => true,
            'shippingRule' => $shippingRule->load(['brand', 'category']),
        ]);
    }

    public function edit($id)
    {
        $shippingRule = ShippingRule::findOrFail($id);

        return response()->json($shippingRule);
    }

    public function update(Request $request, $id)
    {
        $shippingRule = ShippingRule::findOrFail($id);

        $data = $this->validateShippingRule($request, (int) $id);

        $shippingRule->brand_id = $data['brand_id'];
        $shippingRule->category_id = $data['category_id'];
        $shippingRule->shipping_cost = $data['shipping_cost'] ?? null;
        $shippingRule->free_shipping_threshold = $data['free_shipping_threshold'] ?? null;
        $shippingRule->is_active = $request->boolean('is_active', true);
        $shippingRule->save();

        return response()->json([
            'success' => true,
            'shippingRule' => $shippingRule->load(['brand', 'category']),
        ]);
    }

    public function destroy($id)
    {
        $shippingRule = ShippingRule::findOrFail($id);
        $shippingRule->delete();

        return response()->json(['success' => true]);
    }
}
