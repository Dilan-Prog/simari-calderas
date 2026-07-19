<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::withCount('items')->orderBy('name')->get();

        return view('admin.menus.index', compact('menus'));
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
            'name'      => 'required|string|max:100|unique:menus,name',
            'location'  => 'required|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $menu = new Menu();
        $menu->name       = $request->name;
        $menu->location   = $request->location;
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

        return view('admin.menus.edit', compact('menu', 'rootItems', 'childrenByParent'));
    }

    public function update(Request $request, string $menu)
    {
        $menu = Menu::findOrFail($menu);

        $request->validate([
            'name'      => 'required|string|max:100|unique:menus,name,' . $menu->id,
            'location'  => 'required|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $menu->name       = $request->name;
        $menu->location   = $request->location;
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

        $request->validate([
            'title'      => 'required|string|max:120',
            'url'        => 'nullable|string|max:255',
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
        ]);

        $item = new MenuItem();
        $item->menu_id     = $menu->id;
        $item->parent_id   = $request->parent_id ?: null;
        $item->title       = $request->title;
        $item->url         = $request->url ?: null;
        $item->target      = $request->target ?: '_self';
        $item->sort_order  = $request->sort_order ?? 0;
        $item->is_active   = $request->boolean('is_active', true);
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

        $request->validate([
            'title'      => 'required|string|max:120',
            'url'        => 'nullable|string|max:255',
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
        ]);

        $menuItem->parent_id   = $request->parent_id ?: null;
        $menuItem->title       = $request->title;
        $menuItem->url         = $request->url ?: null;
        $menuItem->target      = $request->target ?: '_self';
        $menuItem->sort_order  = $request->sort_order ?? $menuItem->sort_order;
        $menuItem->is_active   = $request->boolean('is_active', true);
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
}
