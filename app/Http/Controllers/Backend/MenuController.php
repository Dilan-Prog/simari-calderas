<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::withCount('items')->orderBy('name')->get();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'menus.index')
            ->value('columns');

        return view('admin.menus.index', compact('menus', 'visibleColumns'));
    }

    public function create()
    {
        // La creación de un menú se hace desde el modal en el índice
        // (mismo patrón que Categorías), esta ruta solo existe para
        // mantener la convención REST — no hay una página dedicada.
        return redirect()->route('admin.menus.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:menus,name',
            'location'   => ['required', Rule::in(['header-main', 'header-servicios', 'footer'])],
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $menu = new Menu();
        $menu->name       = $request->name;
        $menu->location   = $request->location;
        $menu->sort_order = $request->sort_order ?? 0;
        $menu->is_active  = $request->boolean('is_active', true);
        $menu->save();

        return response()->json([
            'success' => true,
            'menu'    => $menu,
        ]);
    }

    public function edit(string $menu)
    {
        $menu = Menu::findOrFail($menu);

        // No usamos Menu::rootItems()/MenuItem::children() aquí porque esas
        // relaciones filtran is_active=true (pensadas para el frontend
        // público) — en el admin necesitamos ver también los inactivos
        // para poder editarlos/reactivarlos.
        $allItems = $menu->items()->orderBy('sort_order')->get();
        $rootItems = $allItems->whereNull('parent_id')->values();
        $childrenByParent = $allItems->whereNotNull('parent_id')->groupBy('parent_id');

        // Árbol de categorías activas (3 niveles), mismo shape que usa
        // ProductController::create() para el selector en cascada del
        // formulario de productos.
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)->select('id', 'name', 'parent_id');
                    }])
                    ->select('id', 'name', 'parent_id');
            }])
            ->get(['id', 'name']);

        $collections = Collection::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Nombres legibles para el resumen de destino de cada ítem existente
        // en el árbol del admin ("Categoría: Calentadores Masstercal"). Como
        // linked_entity_type/linked_entity_id es una convención propia (no
        // una relación morph de Eloquent), agrupamos IDs por tipo y hacemos
        // una consulta batch por tipo en vez de cargar uno por uno (N+1).
        $idsByType = $allItems->whereNotNull('linked_entity_type')->groupBy('linked_entity_type')
            ->map(fn ($items) => $items->pluck('linked_entity_id')->filter()->unique()->values());

        $linkedEntityNames = [];

        $namesByType = [
            'category'   => Category::whereIn('id', $idsByType->get('category', collect()))->pluck('name', 'id'),
            'collection' => Collection::whereIn('id', $idsByType->get('collection', collect()))->pluck('name', 'id'),
            'brand'      => Brand::whereIn('id', $idsByType->get('brand', collect()))->pluck('name', 'id'),
            'product'    => Products::whereIn('id', $idsByType->get('product', collect()))->pluck('name', 'id'),
        ];

        $staticPageLabels = [
            'privacy-notice'   => 'Aviso de Privacidad',
            'terms-of-service' => 'Términos y Condiciones',
            'return-policy'    => 'Política de Devoluciones',
        ];

        foreach ($allItems as $menuItem) {
            $type = $menuItem->linked_entity_type;

            if ($type === 'static_page') {
                $linkedEntityNames[$menuItem->id] = $staticPageLabels[$menuItem->url] ?? $menuItem->url;
            } elseif (isset($namesByType[$type]) && $menuItem->linked_entity_id) {
                $linkedEntityNames[$menuItem->id] = $namesByType[$type][$menuItem->linked_entity_id] ?? null;
            }
        }

        return view('admin.menus.edit', compact(
            'menu', 'rootItems', 'childrenByParent',
            'categories', 'collections', 'brands', 'linkedEntityNames'
        ));
    }

    public function update(Request $request, string $menu)
    {
        $menu = Menu::findOrFail($menu);

        $request->validate([
            'name'       => 'required|string|max:100|unique:menus,name,' . $menu->id,
            'location'   => ['required', Rule::in(['header-main', 'header-servicios', 'footer'])],
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $menu->name       = $request->name;
        $menu->location   = $request->location;
        $menu->sort_order = $request->sort_order ?? 0;
        $menu->is_active  = $request->boolean('is_active', true);
        $menu->save();

        return response()->json([
            'success' => true,
            'menu'    => $menu,
        ]);
    }

    public function destroy(string $menu)
    {
        $menu = Menu::findOrFail($menu);
        $menu->delete();

        return response()->json(['success' => true]);
    }

    public function storeItem(Request $request, string $menu)
    {
        $menu = Menu::findOrFail($menu);

        $request->validate(array_merge([
            'title'      => 'required|string|max:120',
            'target'     => 'nullable|in:_self,_blank',
            'parent_id'  => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id')->where(function ($query) use ($menu) {
                    $query->where('menu_id', $menu->id)->whereNull('parent_id');
                }),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ], $this->linkTypeValidationRules($request)));

        $item = new MenuItem();
        $item->menu_id     = $menu->id;
        $item->parent_id   = $request->parent_id ?: null;
        $item->title       = $request->title;
        $item->target      = $request->target ?: '_self';
        $item->sort_order  = $request->sort_order ?? 0;
        $item->is_active   = $request->boolean('is_active', true);
        $this->applyLinkTypeToItem($item, $request);
        $item->save();

        return response()->json([
            'success' => true,
            'item'    => $item,
        ]);
    }

    public function updateItem(Request $request, string $menu, string $item)
    {
        $menu = Menu::findOrFail($menu);
        $menuItem = MenuItem::where('menu_id', $menu->id)->findOrFail($item);

        $request->validate(array_merge([
            'title'      => 'required|string|max:120',
            'target'     => 'nullable|in:_self,_blank',
            'parent_id'  => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($menuItem) {
                    if ($value && (int) $value === (int) $menuItem->id) {
                        $fail('Un elemento no puede ser su propio padre.');
                    }
                },
                Rule::exists('menu_items', 'id')->where(function ($query) use ($menu) {
                    $query->where('menu_id', $menu->id)->whereNull('parent_id');
                }),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ], $this->linkTypeValidationRules($request)));

        $menuItem->parent_id   = $request->parent_id ?: null;
        $menuItem->title       = $request->title;
        $menuItem->target      = $request->target ?: '_self';
        $menuItem->sort_order  = $request->sort_order ?? $menuItem->sort_order;
        $menuItem->is_active   = $request->boolean('is_active', true);
        $this->applyLinkTypeToItem($menuItem, $request);
        $menuItem->save();

        return response()->json([
            'success' => true,
            'item'    => $menuItem,
        ]);
    }

    public function destroyItem(string $menu, string $item)
    {
        $menu = Menu::findOrFail($menu);
        $menuItem = MenuItem::where('menu_id', $menu->id)->findOrFail($item);
        $menuItem->delete();

        return response()->json(['success' => true]);
    }

    public function reorderItems(Request $request, string $menu)
    {
        $menu = Menu::findOrFail($menu);

        $request->validate([
            'order'     => 'required|array|min:1',
            'order.*'   => 'integer|exists:menu_items,id',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
        ]);

        $items = MenuItem::whereIn('id', $request->order)
            ->where('menu_id', $menu->id)
            ->where('parent_id', $request->input('parent_id'))
            ->get()
            ->keyBy('id');

        if ($items->count() !== count($request->order)) {
            return response()->json([
                'success' => false,
                'message' => 'El orden recibido no coincide con los elementos de este menú.',
            ], 422);
        }

        foreach (array_values($request->order) as $i => $itemId) {
            $items[$itemId]->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Endpoint ligero para el buscador de productos del selector de destino
     * del ítem de menú. Mismo contrato simplificado que
     * QuoteController::searchProducts() (id/name/sku/slug, take(12),
     * is_active=1) pero sin la lógica de precio/moneda — un ítem de menú
     * solo necesita identificar el producto y mostrarlo de forma legible.
     */
    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');

        $products = Products::where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'sku', 'slug')
            ->take(12)
            ->get();

        return response()->json([
            'success'  => true,
            'products' => $products,
        ]);
    }

    /**
     * Reglas de validación para los campos del selector de destino
     * estructurado (link_type + su campo asociado). Solo el campo del tipo
     * seleccionado se exige — el resto queda opcional para no romper el
     * envío del formulario (que puede incluir campos ocultos de otros tipos
     * de destino no seleccionados).
     */
    private function linkTypeValidationRules(Request $request): array
    {
        $entityTypes = ['category', 'collection', 'brand', 'product'];
        $linkType = $request->input('link_type');

        return [
            'link_type' => ['required', Rule::in(['category', 'collection', 'brand', 'product', 'static_page', 'custom_url'])],
            'linked_entity_id' => [
                Rule::requiredIf(in_array($linkType, $entityTypes, true)),
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($linkType) {
                    if (!$value) {
                        return;
                    }

                    $exists = match ($linkType) {
                        'category'   => Category::whereKey($value)->exists(),
                        'collection' => Collection::whereKey($value)->exists(),
                        'brand'      => Brand::whereKey($value)->exists(),
                        'product'    => Products::whereKey($value)->exists(),
                        default      => true,
                    };

                    if (!$exists) {
                        $fail('El destino seleccionado no existe.');
                    }
                },
            ],
            'static_page_route' => [
                Rule::requiredIf($linkType === 'static_page'),
                'nullable',
                Rule::in(['privacy-notice', 'terms-of-service', 'return-policy']),
            ],
            'url' => [
                Rule::requiredIf($linkType === 'custom_url'),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Traduce link_type + su campo asociado (ya validados) a las columnas
     * reales del MenuItem, respetando exactamente lo que espera leer
     * MenuItem::getResolvedUrlAttribute(): url guarda la URL cruda para
     * custom_url, el NOMBRE de ruta para static_page, y queda null para los
     * demás tipos (donde linked_entity_id es la fuente de verdad).
     */
    private function applyLinkTypeToItem(MenuItem $item, Request $request): void
    {
        $linkType = $request->input('link_type');
        $item->linked_entity_type = $linkType;

        switch ($linkType) {
            case 'category':
            case 'collection':
            case 'brand':
            case 'product':
                $item->linked_entity_id = $request->linked_entity_id;
                $item->url = null;
                break;

            case 'static_page':
                $item->linked_entity_id = null;
                $item->url = $request->static_page_route;
                break;

            case 'custom_url':
            default:
                $item->linked_entity_id = null;
                $item->url = $request->url ?: null;
                break;
        }
    }
}
