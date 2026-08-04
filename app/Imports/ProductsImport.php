<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Products;
use App\Traits\ImageUploadTrait;
use App\Traits\NormalizesProductFields;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnFailure, WithMultipleSheets, WithEvents
{
    use SkipsFailures;
    use RemembersRowNumber;
    use RegistersEventListeners;
    use ImageUploadTrait;
    use NormalizesProductFields;

    /** @var array<string,string> sku => URL de imagen pendiente de descargar (se resuelve en afterImport, una vez que los productos ya tienen id) */
    private array $pendingImageUrls = [];

    /** Filas cuya imagen no se pudo descargar: [['sku' => x, 'url' => y], ...] */
    public array $imageDownloadFailures = [];

    /** @var array<string,int> nombre de categoría (lower/trim) => id */
    private array $categoriesByName = [];

    /** @var array<string,int> nombre de marca (lower/trim) => id */
    private array $brandsByName = [];

    /** @var array<string,true> SKUs ya existentes en BD (lower/trim) */
    private array $existingSkus = [];

    /** @var array<string,true> SKUs ya vistos en este mismo archivo */
    private array $seenInFile = [];

    /** Filas omitidas por SKU duplicado: [['row' => n, 'sku' => x], ...] */
    public array $skippedDuplicates = [];

    public int $createdCount = 0;

    public function __construct()
    {
        $this->categoriesByName = Category::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->brandsByName = Brand::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->existingSkus = Products::pluck('sku')
            ->mapWithKeys(fn ($sku) => [strtolower(trim($sku)) => true])
            ->toArray();
    }

    /**
     * Solo se procesa la hoja "Productos" (el mismo nombre que usa
     * ProductsTemplateDataSheet). Esto evita que la hoja "Instrucciones" de
     * nuestra propia plantilla se intente leer como si fueran filas de
     * producto cuando el usuario reimporta el archivo completo sin borrarla.
     */
    public function sheets(): array
    {
        return ['Productos' => $this];
    }

    public function model(array $row)
    {
        $skuKey = strtolower(trim($row['sku'] ?? ''));

        if (isset($this->existingSkus[$skuKey]) || isset($this->seenInFile[$skuKey])) {
            $this->skippedDuplicates[] = [
                'row' => $this->getRowNumber(),
                'sku' => $row['sku'],
            ];

            return null;
        }
        $this->seenInFile[$skuKey] = true;

        $categoryId = $this->resolveIdByName($row['categoria'] ?? null, $this->categoriesByName);
        $brandId = $this->resolveIdByName($row['marca'] ?? null, $this->brandsByName);

        // rules() ya garantiza que categoria/marca existan antes de llegar
        // aquí; esto es solo una guarda defensiva.
        if (!$categoryId || !$brandId) {
            return null;
        }

        $this->createdCount++;

        $product = new Products([
            'name' => $row['nombre'],
            'sku' => $row['sku'],
            'model' => $row['modelo'] ?? null,
            // El proveedor ahora se administra en la pestaña "Proveedores"
            // del producto (tabla suppliers_products, con SKU propio por
            // proveedor) — la importación multi-proveedor por Excel queda
            // para una fase futura.
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'short_description' => $row['descripcion_corta'] ?? null,
            'description' => $row['descripcion'] ?? null,
            'price' => $this->sanitizePrice($row['precio']),
            'compare_price' => $this->sanitizePrice($row['precio_comparativo'] ?? null),
            'cost' => $this->sanitizePrice($row['costo'] ?? null) ?? 0,
            'stock' => $row['stock'] ?? 0,
            'stock_unit' => $this->normalizeProductEnum($row['unidad_stock'] ?? null, ['pieza', 'juego', 'kit', 'metro', 'kg', 'litro'], 'pieza'),
            'currency' => $this->normalizeProductEnum($row['moneda'] ?? null, ['MXN', 'USD'], 'MXN', true),
            'is_active' => $this->normalizeProductBool($row['activo'] ?? true, true),
            'is_featured' => $this->normalizeProductBool($row['destacado'] ?? false, false),
            'is_new' => $this->normalizeProductBool($row['nuevo'] ?? false, false),
            'is_recommended' => $this->normalizeProductBool($row['recomendado'] ?? false, false),
            'publish_on_website' => $this->normalizeProductBool($row['publicar_web'] ?? false, false),
        ]);
        $product->slug = Str::slug($row['nombre']) . '-' . Str::random(6);
        // availability no está en $fillable del modelo (igual que en
        // ProductController), se asigna por propiedad directa.
        $product->availability = $this->normalizeProductAvailability($row['disponibilidad'] ?? null);

        if (!empty($row['imagen_url'])) {
            // El producto todavía no tiene id en este punto (WithBatchInserts
            // difiere el INSERT real), así que la descarga se resuelve en
            // afterImport() una vez que ya existen en BD y se pueden buscar
            // por SKU (único).
            $this->pendingImageUrls[$row['sku']] = trim($row['imagen_url']);
        }

        return $product;
    }

    /**
     * Descarga las imágenes pendientes (columna "Imagen URL") una vez que
     * todos los productos del archivo ya fueron insertados. No bloquea la
     * importación si una URL falla — se reporta en imageDownloadFailures.
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
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => 0,
                ]);
            } else {
                $this->imageDownloadFailures[] = ['sku' => $product->sku, 'url' => $url];
            }
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'categoria' => ['required', function ($attribute, $value, $fail) {
                if (!isset($this->categoriesByName[strtolower(trim($value ?? ''))])) {
                    $fail("La categoría \"{$value}\" no existe en el sistema.");
                }
            }],
            'marca' => ['required', function ($attribute, $value, $fail) {
                if (!isset($this->brandsByName[strtolower(trim($value ?? ''))])) {
                    $fail("La marca \"{$value}\" no existe en el sistema.");
                }
            }],
            'precio' => ['required', function ($attribute, $value, $fail) {
                $clean = $this->sanitizePrice($value);
                if ($clean === null || $clean < 0) {
                    $fail('El precio no es un número válido.');
                }
            }],
            'costo' => ['nullable', function ($attribute, $value, $fail) {
                $clean = $this->sanitizePrice($value);
                if ($value !== null && trim((string) $value) !== '' && ($clean === null || $clean < 0)) {
                    $fail('El costo no es un número válido.');
                }
            }],
            'precio_comparativo' => ['nullable', function ($attribute, $value, $fail) {
                $clean = $this->sanitizePrice($value);
                if ($value !== null && trim((string) $value) !== '' && ($clean === null || $clean < 0)) {
                    $fail('El precio comparativo no es un número válido.');
                }
            }],
            'stock' => 'nullable|integer|min:0',
            'imagen_url' => 'nullable|url|max:2048',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'Falta el nombre del producto.',
            'sku.required' => 'Falta el SKU del producto.',
            'precio.required' => 'Falta el precio del producto.',
            'precio.numeric' => 'El precio no es un número válido.',
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

}
