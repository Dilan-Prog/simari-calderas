<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Products;
use App\Traits\ImageUploadTrait;
use App\Traits\NormalizesProductFields;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;

/**
 * Actualiza productos EXISTENTES a partir de un archivo re-descargado desde
 * el catálogo real (ver ProductsUpdateTemplateExport). A diferencia de
 * ProductsImport (que crea productos nuevos y omite SKUs duplicados), aquí
 * el SKU debe ya existir, y una celda vacía significa "no cambiar ese
 * campo" — nunca se sobreescribe con un valor por defecto ni se borra.
 */
class ProductsUpdateImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure, WithMultipleSheets, WithEvents
{
    use SkipsFailures;
    use RemembersRowNumber;
    use RegistersEventListeners;
    use ImageUploadTrait;
    use NormalizesProductFields;

    private const STOCK_UNITS = ['pieza', 'juego', 'kit', 'metro', 'kg', 'litro'];
    private const CURRENCIES = ['MXN', 'USD', 'EUR'];

    /** @var array<string,string> sku => URL de imagen pendiente de descargar */
    private array $pendingImageUrls = [];

    /** Filas cuya imagen no se pudo descargar: [['sku' => x, 'url' => y], ...] */
    public array $imageDownloadFailures = [];

    /** @var array<string,int> nombre de categoría (lower/trim) => id */
    private array $categoriesByName = [];

    /** @var array<string,int> nombre de marca (lower/trim) => id */
    private array $brandsByName = [];

    /** @var array<string,int> SKU (lower/trim) => id del producto existente */
    private array $existingSkuToId = [];

    /** Filas omitidas porque el SKU no existe en el sistema: [['row' => n, 'sku' => x], ...] */
    public array $skippedNotFound = [];

    public int $updatedCount = 0;

    public function __construct()
    {
        $this->categoriesByName = Category::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->brandsByName = Brand::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->existingSkuToId = Products::pluck('id', 'sku')
            ->mapWithKeys(fn ($id, $sku) => [strtolower(trim($sku)) => $id])
            ->toArray();
    }

    /**
     * Igual que ProductsImport::sheets() — solo lee la hoja "Productos" para
     * ignorar la hoja "Instrucciones" si el usuario reimporta el archivo
     * completo.
     */
    public function sheets(): array
    {
        return ['Productos' => $this];
    }

    private function present($value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    public function model(array $row)
    {
        $skuKey = strtolower(trim($row['sku'] ?? ''));
        $productId = $this->existingSkuToId[$skuKey] ?? null;

        if (!$productId) {
            $this->skippedNotFound[] = [
                'row' => $this->getRowNumber(),
                'sku' => $row['sku'] ?? '',
            ];

            return null;
        }

        $product = Products::find($productId);
        if (!$product) {
            // Borrado por otra fila/proceso entre la carga del mapa de SKUs
            // y este punto — caso extremo, se trata igual que "no existe".
            $this->skippedNotFound[] = [
                'row' => $this->getRowNumber(),
                'sku' => $row['sku'] ?? '',
            ];

            return null;
        }

        if ($this->present($row['nombre'] ?? null)) {
            $product->name = $row['nombre'];
        }
        if ($this->present($row['modelo'] ?? null)) {
            $product->model = $row['modelo'];
        }
        // El proveedor ahora se administra en la pestaña "Proveedores" del
        // producto (tabla suppliers_products, con SKU propio por
        // proveedor) — la actualización multi-proveedor por Excel queda
        // para una fase futura.
        if ($this->present($row['categoria'] ?? null)) {
            $categoryId = $this->resolveIdByName($row['categoria'], $this->categoriesByName);
            if ($categoryId) {
                $product->category_id = $categoryId;
            }
        }
        if ($this->present($row['marca'] ?? null)) {
            $brandId = $this->resolveIdByName($row['marca'], $this->brandsByName);
            if ($brandId) {
                $product->brand_id = $brandId;
            }
        }
        if ($this->present($row['descripcion_corta'] ?? null)) {
            $product->short_description = $row['descripcion_corta'];
        }
        if ($this->present($row['descripcion'] ?? null)) {
            $product->description = $row['descripcion'];
        }
        if ($this->present($row['precio'] ?? null)) {
            $clean = $this->sanitizePrice($row['precio']);
            if ($clean !== null) {
                $product->price = $clean;
            }
        }
        if ($this->present($row['precio_comparativo'] ?? null)) {
            $clean = $this->sanitizePrice($row['precio_comparativo']);
            if ($clean !== null) {
                $product->compare_price = $clean;
            }
        }
        if ($this->present($row['costo'] ?? null)) {
            $clean = $this->sanitizePrice($row['costo']);
            if ($clean !== null) {
                $product->cost = $clean;
            }
        }
        if ($this->present($row['stock'] ?? null)) {
            $product->stock = (int) $row['stock'];
        }
        if ($this->present($row['unidad_stock'] ?? null)) {
            $product->stock_unit = $this->normalizeProductEnum($row['unidad_stock'], self::STOCK_UNITS, $product->stock_unit);
        }
        if ($this->present($row['moneda'] ?? null)) {
            $product->currency = $this->normalizeProductEnum($row['moneda'], self::CURRENCIES, $product->currency, true);
        }
        if ($this->present($row['disponibilidad'] ?? null)) {
            $product->availability = $this->normalizeProductAvailability($row['disponibilidad']);
        }
        if ($this->present($row['activo'] ?? null)) {
            $product->is_active = $this->normalizeProductBool($row['activo'], $product->is_active);
        }
        if ($this->present($row['destacado'] ?? null)) {
            $product->is_featured = $this->normalizeProductBool($row['destacado'], $product->is_featured);
        }
        if ($this->present($row['nuevo'] ?? null)) {
            $product->is_new = $this->normalizeProductBool($row['nuevo'], $product->is_new);
        }
        if ($this->present($row['recomendado'] ?? null)) {
            $product->is_recommended = $this->normalizeProductBool($row['recomendado'], $product->is_recommended);
        }
        if ($this->present($row['publicar_web'] ?? null)) {
            $product->publish_on_website = $this->normalizeProductBool($row['publicar_web'], $product->publish_on_website);
        }

        $product->save();
        $this->updatedCount++;

        if ($this->present($row['imagen_url'] ?? null)) {
            // El producto ya existe (a diferencia de ProductsImport), pero
            // se resuelve igual en afterImport() para mantener el mismo
            // mecanismo de descarga con manejo de fallos.
            $this->pendingImageUrls[$product->sku] = trim($row['imagen_url']);
        }

        return null;
    }

    /**
     * Descarga las imágenes pendientes (columna "Imagen URL") y las agrega
     * como imagen ADICIONAL — nunca reemplaza ni borra las que ya tenía el
     * producto.
     */
    public function afterImport(AfterImport $event): void
    {
        if (empty($this->pendingImageUrls)) {
            return;
        }

        $products = Products::whereIn('sku', array_keys($this->pendingImageUrls))->get(['id', 'sku']);

        foreach ($products as $product) {
            $url = $this->pendingImageUrls[$product->sku];
            $path = $this->downloadImageFromUrl($url);

            if ($path) {
                $nextSortOrder = ProductImage::where('product_id', $product->id)->max('sort_order');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $nextSortOrder === null ? 0 : $nextSortOrder + 1,
                ]);
            } else {
                $this->imageDownloadFailures[] = ['sku' => $product->sku, 'url' => $url];
            }
        }
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:100',
            'categoria' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && !isset($this->categoriesByName[strtolower(trim($value))])) {
                    $fail("La categoría \"{$value}\" no existe en el sistema.");
                }
            }],
            'marca' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && !isset($this->brandsByName[strtolower(trim($value))])) {
                    $fail("La marca \"{$value}\" no existe en el sistema.");
                }
            }],
            'precio' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && $this->sanitizePrice($value) === null) {
                    $fail('El precio no es un número válido.');
                }
            }],
            'costo' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && $this->sanitizePrice($value) === null) {
                    $fail('El costo no es un número válido.');
                }
            }],
            'precio_comparativo' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && $this->sanitizePrice($value) === null) {
                    $fail('El precio comparativo no es un número válido.');
                }
            }],
            'stock' => 'nullable|integer|min:0',
            'unidad_stock' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && !in_array(strtolower(trim($value)), array_map('strtolower', self::STOCK_UNITS), true)) {
                    $fail("La unidad de stock \"{$value}\" no es válida.");
                }
            }],
            'moneda' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && !in_array(strtoupper(trim($value)), self::CURRENCIES, true)) {
                    $fail("La moneda \"{$value}\" no es válida.");
                }
            }],
            'imagen_url' => 'nullable|url|max:2048',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'sku.required' => 'Falta el SKU del producto a actualizar.',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
