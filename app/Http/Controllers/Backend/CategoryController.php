<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * FIX (SEO slugs): Str::slug() strips "/" entirely instead of treating
     * it as a path separator (Str::slug('a/b') === 'ab'), which silently
     * defeated the admin JS's attempt to build a hierarchical "parent/child"
     * slug. Sanitizes each "/"-separated segment on its own so the
     * separator survives.
     */
    private function slugifyPath(string $raw): string
    {
        return collect(explode('/', $raw))
            ->filter(fn ($seg) => $seg !== '')
            ->map(fn ($seg) => Str::slug($seg))
            ->implode('/');
    }

    /**
     * Preguntas frecuentes personalizadas por categoría. Mismo patrón de
     * FAQs que products.faqs/collections.faqs (trim, descartar pares
     * incompletos, null si queda vacío).
     */
    private function fillFaqs(Category $category, Request $request): void
    {
        $faqs = collect((array) $request->input('faq_items', []))
            ->map(fn ($item) => [
                'question' => trim($item['question'] ?? ''),
                'answer'   => trim($item['answer'] ?? ''),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();

        $category->faqs = $faqs ?: null;
    }

    public function index()
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        // FIX: the "Categoría Padre" select in the create/edit modal was
        // reusing the paginated $categories list above (only 15 rows on the
        // current page), so most categories — including main ones sitting
        // past page 1 — never showed up as selectable parents. This is the
        // full, unpaginated list dedicated to that dropdown.
        $allCategories = Category::orderBy('sort_order')->orderBy('name')->get();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'categories.index')
            ->value('columns');

        return view('admin.categories.index', compact('categories', 'allCategories', 'visibleColumns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120',
            'slug'        => 'required|string|max:150|unique:product_categories,slug',
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'seo_title'   => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ]);

        $category = new Category();
        $category->name        = $request->name;
        $category->slug        = $this->slugifyPath($request->slug);
        $category->parent_id   = $request->parent_id   ?? null;
        $category->description = $request->description ?? null;
        $category->image_url   = $request->image_url   ?? null;
        $category->is_active   = $request->boolean('is_active', true);
        $category->sort_order  = $request->sort_order  ?? 0;
        $category->seo_title     = $request->seo_title     ?? null;
        $category->seo_description = $request->seo_description ?? null;
        $this->fillFaqs($category, $request);
        $category->save();

        return response()->json([
            'success'  => true,
            'category' => $category->load('parent'),
        ]);
    }

    public function edit(string $id)
    {
        $category = Category::with(['parent.parent'])->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:120',
            'slug'        => 'required|string|max:150|unique:product_categories,slug,' . $id,
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category->name        = $request->name;
        $category->slug        = $this->slugifyPath($request->slug);
        $category->parent_id   = $request->parent_id   ?? null;
        $category->description = $request->description ?? null;
        $category->image_url   = $request->image_url   ?? null;
        $category->is_active   = $request->boolean('is_active', true);
        $category->sort_order  = $request->sort_order  ?? 0;
        $category->seo_title     = $request->seo_title     ?? null;
        $category->seo_description = $request->seo_description ?? null;
        $this->fillFaqs($category, $request);
        $category->save();

        return response()->json([
            'success'  => true,
            'category' => $category->load('parent'),
        ]);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Prevent deleting if has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar una categoría que tiene subcategorías.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }
}
