<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\ProductImage;
use App\Models\ProductDocument;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ImageUploadTrait;

    // FIX (Documentación tab): maps each of the 6 upload slots in the view
    // to its product_documents.type enum value.
    private const DOCUMENT_FIELDS = [
        'doc_ficha'         => 'ficha',
        'doc_manual'        => 'manual',
        'doc_catalogo'      => 'catalogo',
        'doc_certificacion' => 'certificacion',
        'doc_garantia'      => 'garantia',
        'doc_otro'          => 'otro',
    ];

    /**
     * Replace the product's document of the given type with the uploaded
     * file. Each of the 6 slots in the UI is a single fixed spot, so a new
     * upload replaces (not adds to) the previous one for that type.
     */
    private function saveProductDocument(Products $product, $file, string $type): void
    {
        $existing = $product->documents()->where('type', $type)->first();
        if ($existing) {
            $this->deleteImage($existing->path);
            $existing->delete();
        }

        $dir = public_path('product-documents');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = uniqid() . '.' . $ext;
        $file->move($dir, $filename);

        ProductDocument::create([
            'product_id'    => $product->id,
            'type'          => $type,
            'path'          => 'product-documents/' . $filename,
            'original_name' => $file->getClientOriginalName(),
            'created_at'    => now(),
        ]);
    }
    public function index()
    {
        $products = Products::with(['category', 'brand', 'images'])->get([
            'id',
            'name',
            'sku',
            'price',
            'cost',
            'stock',
            // FIX BUG 11/9: stock_unit is now a real column (see BUG 9) —
            // added here so index.blade.php's inventory summary shows the
            // real unit instead of always falling back to 'unidades'.
            'stock_unit',
            'is_active',
            'is_featured',
            'cover_image_url',
            'category_id',
            'brand_id',
        ]);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
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

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        $lastProduct = Products::orderBy('id', 'desc')->first();
        $nextNumber = $lastProduct ? (intval(substr($lastProduct->sku, 5)) + 1) : 1;
        $sku = 'ALMC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return view('admin.products.create_product.create', compact('categories', 'brands', 'sku'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku',
            // FIX BUG 1: category_id is NOT NULL in the DB with an FK
            // (onDelete restrict) to product_categories, but had no
            // validation rule — a request bypassing the JS guard passed
            // validation and crashed the INSERT with a silent 500
            // QueryException instead of a 422.
            'category_id'       => 'required|exists:product_categories,id',
            // FIX BUG 7: brand_id was marked required (* + required attr)
            // in the view and enforced client-side, but had no server rule
            // — nullable in DB, so a bypass silently saved a brand-less
            // product. Option A chosen: make it truly required.
            'brand_id'          => 'required|exists:brands,id',
            'model'             => 'nullable|string|max:100',
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            // FIX BUG 3/5: added validation for the new columns.
            'tags'              => 'nullable|string',
            'seo_keywords'      => 'nullable|string|max:255',
            'og_title'          => 'nullable|string|max:255',
            'og_description'    => 'nullable|string',
            'og_image'          => 'nullable|url|max:255',
            // FIX BUG 9: added validation for the new currency/stock_unit
            // columns.
            'currency'          => 'nullable|in:MXN,USD,EUR',
            'stock_unit'        => 'nullable|in:pieza,juego,kit,metro,kg,litro',
            'slug'              => 'nullable|string|max:255|unique:products,slug',
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
            'is_new'            => 'nullable|boolean',
            'is_recommended'    => 'nullable|boolean',
            'availability'      => 'nullable|in:available,on_order,out_of_stock',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,jpg,png|max:2048',
            // FIX (Documentación tab): added validation for the 6 document
            // uploads — previously had no name= at all, so nothing reached
            // the server to validate.
            'doc_ficha'         => 'nullable|file|mimes:pdf|max:5120',
            'doc_manual'        => 'nullable|file|mimes:pdf|max:5120',
            'doc_catalogo'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_certificacion' => 'nullable|file|mimes:pdf|max:5120',
            'doc_garantia'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_otro'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $product = new Products();
        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug
            ? Str::slug($request->slug)
            : Str::slug($request->name);
        // FIX (reported bug): 'model' has a real column and a name="model"
        // input in the view, but was never assigned — always lost silently.
        $product->model              = $request->model         ?? null;
        $product->price             = $request->price;
        $product->cost              = $request->cost           ?? 0;
        $product->compare_price     = $request->compare_price  ?? null;
        $product->stock             = $request->stock          ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description    ?? null;
        // FIX BUG 10: weight/height/width/length removed from validate()
        // and assignment — postponed per decision, no UI inputs exist for
        // them yet. Columns stay null until a future ticket adds the UI.
        $product->seo_title         = $request->seo_title      ?? null;
        $product->seo_description   = $request->seo_description ?? null;
        // FIX BUG 3: JS serializes the tag chips into a JSON string in a
        // hidden input named 'tags'; decode it here so the 'array' cast on
        // the model re-encodes it consistently.
        $product->tags               = $request->filled('tags') ? json_decode($request->tags, true) : null;
        // FIX BUG 5: seo_keywords + Open Graph fields now have a real
        // column and a name= attribute in the view.
        $product->seo_keywords      = $request->seo_keywords   ?? null;
        $product->og_title          = $request->og_title       ?? null;
        $product->og_description    = $request->og_description ?? null;
        $product->og_image          = $request->og_image       ?? null;
        // FIX BUG 9: currency + stock_unit now have a real column and a
        // name= attribute in the view.
        $product->currency          = $request->currency       ?? 'MXN';
        $product->stock_unit        = $request->stock_unit     ?? 'pieza';
        $product->category_id       = $request->category_id    ?? null;
        $product->brand_id          = $request->brand_id       ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->is_new            = $request->boolean('is_new', false);
        $product->is_recommended    = $request->boolean('is_recommended', false);
        $product->availability      = $request->availability ?? 'available';
        // Save specifications
        if ($request->filled('spec_key')) {
            $specs = [];
            foreach ($request->spec_key as $i => $key) {
                if (!empty($key)) {
                    $specs[] = [
                        'key'   => $key,
                        'value' => $request->spec_value[$i] ?? '',
                    ];
                }
            }
            $product->specifications = json_encode($specs);
        }

        $product->save();

        if ($request->hasFile('images')) {
            $paths = $this->uploadImages($request->file('images'));
            foreach ($paths as $i => $path) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $i,
                ]);
            }
        }

        // FIX (Documentación tab): save each uploaded document slot.
        foreach (self::DOCUMENT_FIELDS as $field => $type) {
            if ($request->hasFile($field)) {
                $this->saveProductDocument($product, $request->file($field), $type);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(string $id)
    {
        // FIX (Documentación tab): eager-load documents so the edit view
        // can show what's already uploaded per type.
        $product = Products::with(['category', 'brand', 'images', 'documents'])->findOrFail($id);

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

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        return view(
            'admin.products.edit_product.edit',
            compact('product', 'categories', 'brands')
        );
    }

    public function update(Request $request, string $id)
    {
        $product = Products::findOrFail($id);

        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku,' . $id,
            // FIX BUG 1: category_id is NOT NULL in the DB with an FK
            // (onDelete restrict) to product_categories, but had no
            // validation rule here either — same 500 QueryException risk
            // as store(), now returns a 422 instead.
            'category_id'       => 'required|exists:product_categories,id',
            // FIX BUG 7: mirrors store() — brand_id is now truly required.
            'brand_id'          => 'required|exists:brands,id',
            'model'             => 'nullable|string|max:100',
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            // FIX BUG 3/5: added validation for the new columns.
            'tags'              => 'nullable|string',
            'seo_keywords'      => 'nullable|string|max:255',
            'og_title'          => 'nullable|string|max:255',
            'og_description'    => 'nullable|string',
            'og_image'          => 'nullable|url|max:255',
            // FIX BUG 9: added validation for the new currency/stock_unit
            // columns.
            'currency'          => 'nullable|in:MXN,USD,EUR',
            'stock_unit'        => 'nullable|in:pieza,juego,kit,metro,kg,litro',
            'slug'              => 'nullable|string|max:255|unique:products,slug,' . $id,
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
            'is_new'            => 'nullable|boolean',
            'is_recommended'    => 'nullable|boolean',
            'availability'      => 'nullable|in:available,on_order,out_of_stock',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,jpg,png|max:2048',
            'delete_images'     => 'nullable|array',
            'delete_images.*'   => 'integer|exists:product_images,id',
            // FIX (Documentación tab): mirrors store() — validation for the
            // 6 document uploads.
            'doc_ficha'         => 'nullable|file|mimes:pdf|max:5120',
            'doc_manual'        => 'nullable|file|mimes:pdf|max:5120',
            'doc_catalogo'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_certificacion' => 'nullable|file|mimes:pdf|max:5120',
            'doc_garantia'      => 'nullable|file|mimes:pdf|max:5120',
            'doc_otro'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug
            ? Str::slug($request->slug)
            : Str::slug($request->name);
        // FIX (reported bug): 'model' has a real column and a name="model"
        // input in the view, but was never assigned — always lost silently.
        $product->model              = $request->model         ?? null;
        $product->price             = $request->price;
        $product->cost              = $request->cost           ?? 0;
        $product->compare_price     = $request->compare_price  ?? null;
        $product->stock             = $request->stock          ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description    ?? null;
        // FIX BUG 10: weight/height/width/length removed from validate()
        // and assignment — postponed per decision, no UI inputs exist for
        // them yet. Columns stay null until a future ticket adds the UI.
        $product->seo_title         = $request->seo_title      ?? null;
        $product->seo_description   = $request->seo_description ?? null;
        // FIX BUG 3: same as store() — decode the JSON string from the
        // hidden 'tags' input so the model's 'array' cast re-encodes it.
        $product->tags               = $request->filled('tags') ? json_decode($request->tags, true) : null;
        // FIX BUG 5: seo_keywords + Open Graph fields now have a real
        // column and a name= attribute in the view.
        $product->seo_keywords      = $request->seo_keywords   ?? null;
        $product->og_title          = $request->og_title       ?? null;
        $product->og_description    = $request->og_description ?? null;
        $product->og_image          = $request->og_image       ?? null;
        // FIX BUG 9: currency + stock_unit now have a real column and a
        // name= attribute in the view.
        $product->currency          = $request->currency       ?? 'MXN';
        $product->stock_unit        = $request->stock_unit     ?? 'pieza';
        $product->category_id       = $request->category_id    ?? null;
        $product->brand_id          = $request->brand_id       ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->is_new            = $request->boolean('is_new', false);
        $product->is_recommended    = $request->boolean('is_recommended', false);
        $product->availability      = $request->availability ?? 'available';

        // Save specifications
        if ($request->filled('spec_key')) {
            $specs = [];
            foreach ($request->spec_key as $i => $key) {
                if (!empty($key)) {
                    $specs[] = [
                        'key'   => $key,
                        'value' => $request->spec_value[$i] ?? '',
                    ];
                }
            }
            $product->specifications = json_encode($specs);
        } else {
            // Clear specs if all were deleted
            $product->specifications = null;
        }

        $product->save();

        if ($request->filled('delete_images')) {
            $toDelete = ProductImage::whereIn('id', $request->delete_images)
                ->where('product_id', $product->id)
                ->get();
            foreach ($toDelete as $img) {
                $this->deleteImage($img->image_url);
                $img->delete();
            }
        }

        if ($request->hasFile('images')) {
            $existingCount = $product->images()->count();
            $paths = $this->uploadImages($request->file('images'));
            foreach ($paths as $i => $path) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $existingCount + $i,
                ]);
            }
        }

        // FIX (Documentación tab): save/replace each uploaded document slot.
        foreach (self::DOCUMENT_FIELDS as $field => $type) {
            if ($request->hasFile($field)) {
                $this->saveProductDocument($product, $request->file($field), $type);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        // FIX (Documentación tab): eager-load documents too so their
        // physical files can be cleaned up below.
        $product = Products::with(['images', 'documents'])->findOrFail($id);

        // FIX BUG 2: Guard against FK constraint violations before deleting.
        // 6 tables reference products.id with onDelete('restrict'); deleting
        // a product still referenced by any of them crashed with an
        // uncaught QueryException (500) instead of a clear 422. Two checks
        // use real Eloquent relations (models exist); the other four use
        // DB::table() directly since no Eloquent model exists yet for those
        // tables (order_items, inventory_movements,
        // service_materials_used/returned) — creating one is outside this
        // module's isolated scope.
        $blockers = [];
        if ($product->purchaseOrderItems()->count() > 0) {
            $blockers[] = 'órdenes de compra';
        }
        if ($product->serviceMaterialPlans()->count() > 0) {
            $blockers[] = 'planes de materiales de servicio';
        }
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'órdenes de venta';
        }
        if (DB::table('service_materials_used')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'materiales usados en servicios';
        }
        if (DB::table('service_materials_returned')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'materiales devueltos de servicios';
        }
        if (DB::table('inventory_movements')->where('product_id', $product->id)->exists()) {
            $blockers[] = 'movimientos de inventario';
        }

        if (!empty($blockers)) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar \"{$product->name}\" porque tiene " . implode(', ', $blockers) . ' asociados.',
            ], 422);
        }

        foreach ($product->images as $img) {
            $this->deleteImage($img->image_url);
        }

        // FIX (Documentación tab): clean up physical document files too —
        // the DB rows cascade-delete via the FK, but the files on disk
        // wouldn't without this.
        foreach ($product->documents as $doc) {
            $this->deleteImage($doc->path);
        }

        $product->suppliers()->detach();
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
