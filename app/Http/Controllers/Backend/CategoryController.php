<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Redirect;
use App\Services\Catalog\CategorySlugs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Slug final de la categoría: SIEMPRE la ruta completa con sus ancestros ("padre/hija"), sin importar lo
     * que se haya escrito en el campo (solo cuenta su último segmento). Así una subcategoría nunca queda con
     * un slug suelto ("boiler-de-paso") que rompe la URL pública y el SEO. Valida que no choque con otra
     * categoría ni exceda el largo de la columna.
     */
    private function resolveSlug(Request $request, ?int $ignoreId = null): string
    {
        $parent = $request->filled('parent_id') ? Category::find($request->parent_id) : null;
        $slug = CategorySlugs::build($parent, (string) $request->slug);

        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'El slug no puede quedar vacío.']);
        }
        if (mb_strlen($slug) > CategorySlugs::MAX_LENGTH) {
            throw ValidationException::withMessages(['slug' => 'La URL completa (con la ruta del padre) supera ' . CategorySlugs::MAX_LENGTH . ' caracteres; acorta el slug.']);
        }
        $taken = Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($taken) {
            throw ValidationException::withMessages(['slug' => 'Ya existe otra categoría con la URL /catalogo/' . $slug . '.']);
        }

        return $slug;
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
            'slug'        => 'required|string|max:150',
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'seo_title'   => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ], [
            'seo_title.max' => 'El título SEO no puede tener más de 60 caracteres.',
            'seo_description.max' => 'La meta description no puede tener más de 160 caracteres.',
        ]);

        $slug = $this->resolveSlug($request);

        $category = new Category();
        $category->name        = $request->name;
        $category->slug        = $slug;
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
            'slug'        => 'required|string|max:150',
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            // FIX (reported bug): faltaban aquí — sí estaban en store() —
            // así que editar (no crear) una categoría con un Título SEO de
            // más de 60 caracteres pasaba la validación pero luego tronaba
            // con un 500 al chocar contra el límite real de la columna
            // seo_title VARCHAR(60) en la base de datos.
            'seo_title'   => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ], [
            'seo_title.max' => 'El título SEO no puede tener más de 60 caracteres.',
            'seo_description.max' => 'La meta description no puede tener más de 160 caracteres.',
        ]);

        // Una categoría no puede colgar de sí misma ni de una de sus descendientes (rompería la ruta).
        if ($request->filled('parent_id') && CategorySlugs::isSelfOrDescendant((int) $category->id, (int) $request->parent_id)) {
            throw ValidationException::withMessages(['parent_id' => 'La categoría padre no puede ser ella misma ni una de sus subcategorías.']);
        }
        $oldSlug = $category->slug;
        $slug = $this->resolveSlug($request, (int) $category->id);

        $category->name        = $request->name;
        $category->slug        = $slug;
        $category->parent_id   = $request->parent_id   ?? null;
        $category->description = $request->description ?? null;
        $category->image_url   = $request->image_url   ?? null;
        $category->is_active   = $request->boolean('is_active', true);
        $category->sort_order  = $request->sort_order  ?? 0;
        $category->seo_title     = $request->seo_title     ?? null;
        $category->seo_description = $request->seo_description ?? null;
        $this->fillFaqs($category, $request);

        // Si la URL cambió, la vieja redirige (301) a la nueva; los descendientes los registra Category::saved().
        DB::transaction(function () use ($category, $oldSlug) {
            $category->save();
            if ($category->wasChanged('slug')) {
                Redirect::record('/catalogo/' . $oldSlug, '/catalogo/' . $category->slug);
            }
        });

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
