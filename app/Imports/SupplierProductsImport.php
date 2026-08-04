<?php

namespace App\Imports;

use App\Models\Products;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Traits\NormalizesProductFields;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

/**
 * Crea o actualiza vínculos proveedor↔producto (suppliers_products) desde un
 * solo archivo. A diferencia de Productos (que usa 2 clases porque su llave
 * natural es un SKU único), aquí la llave natural es compuesta (proveedor +
 * SKU de producto) y firstOrNew() sobre esa llave cubre crear y actualizar
 * en el mismo flujo: si el vínculo ya existe, una celda opcional vacía
 * significa "no cambiar"; si es un vínculo nuevo, queda vacío/por defecto.
 */
class SupplierProductsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure, WithMultipleSheets
{
    use SkipsFailures;
    use RemembersRowNumber;
    use RegistersEventListeners;
    use NormalizesProductFields;

    /** @var array<string,int> nombre de proveedor (lower/trim) => id */
    private array $suppliersByName = [];

    /** @var array<string,int> SKU de producto (lower/trim) => id */
    private array $productsBySku = [];

    /** Cuando se importa desde la pestaña "Productos" de un proveedor
     *  específico, la columna "Proveedor" se vuelve opcional (se asume este
     *  proveedor) y, si se llena, debe coincidir con él — evita que una fila
     *  copiada/pegada por error asigne el vínculo a otro proveedor. */
    private ?int $forcedSupplierId = null;
    private ?string $forcedSupplierName = null;

    public int $createdCount = 0;
    public int $updatedCount = 0;

    public function __construct(?int $forcedSupplierId = null)
    {
        $this->suppliersByName = Supplier::pluck('id', 'company_name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->productsBySku = Products::pluck('id', 'sku')
            ->mapWithKeys(fn ($id, $sku) => [strtolower(trim($sku)) => $id])
            ->toArray();

        if ($forcedSupplierId) {
            $this->forcedSupplierId = $forcedSupplierId;
            $this->forcedSupplierName = Supplier::find($forcedSupplierId)?->company_name;
        }
    }

    /**
     * Solo se procesa la hoja "ProveedoresProductos" (mismo nombre que usa
     * SupplierProductsExport/SupplierProductsTemplateDataSheet), para que la
     * hoja "Instrucciones" de la plantilla se ignore si se reimporta el
     * archivo completo.
     */
    public function sheets(): array
    {
        return ['ProveedoresProductos' => $this];
    }

    public function model(array $row)
    {
        $supplierId = $this->forcedSupplierId ?? $this->resolveIdByName($row['proveedor'] ?? null, $this->suppliersByName);
        $productId = $this->resolveIdByName($row['sku_producto'] ?? null, $this->productsBySku);

        // rules() ya garantiza que ambos existan antes de llegar aquí; esto
        // es solo una guarda defensiva.
        if (!$supplierId || !$productId) {
            return null;
        }

        $pivot = SupplierProduct::firstOrNew(['supplier_id' => $supplierId, 'product_id' => $productId]);
        $isNew = !$pivot->exists;

        if ($this->present($row['sku_proveedor'] ?? null)) {
            $pivot->sku = $row['sku_proveedor'];
        } elseif ($isNew) {
            $pivot->sku = null;
        }

        if ($this->present($row['costo'] ?? null)) {
            $clean = $this->sanitizePrice($row['costo']);
            if ($clean !== null) {
                $pivot->cost = $clean;
            }
        } elseif ($isNew) {
            $pivot->cost = null;
        }

        if ($this->present($row['lead_time_dias'] ?? null)) {
            $pivot->lead_time_days = (int) $row['lead_time_dias'];
        } elseif ($isNew) {
            $pivot->lead_time_days = null;
        }

        if ($this->present($row['es_principal'] ?? null)) {
            $pivot->is_primary = $this->normalizeProductBool($row['es_principal'], false);
        } elseif ($isNew) {
            $pivot->is_primary = false;
        }

        $pivot->save();
        $isNew ? $this->createdCount++ : $this->updatedCount++;

        if ($pivot->is_primary) {
            SupplierProduct::demoteOtherPrimaries($productId, $pivot->id);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            // Rule::requiredIf() es "implícita" para el validador de Laravel:
            // a diferencia de un closure suelto (que Laravel se salta por
            // completo cuando el valor llega como cadena vacía, sin importar
            // 'nullable'), esta sí se evalúa siempre, así que es la única
            // forma de exigir el proveedor solo cuando NO hay uno forzado.
            'proveedor' => [
                Rule::requiredIf(fn () => !$this->forcedSupplierId),
                function ($attribute, $value, $fail) {
                    if (!$this->present($value)) {
                        return; // vacío + proveedor forzado: se asume ese
                    }

                    $id = $this->suppliersByName[strtolower(trim($value))] ?? null;
                    if (!$id) {
                        $fail("El proveedor \"{$value}\" no existe en el sistema.");
                        return;
                    }

                    if ($this->forcedSupplierId && $id !== $this->forcedSupplierId) {
                        $fail("Esta importación está bloqueada al proveedor \"{$this->forcedSupplierName}\" — esta fila especifica \"{$value}\".");
                    }
                },
            ],
            'sku_producto' => ['required', function ($attribute, $value, $fail) {
                if (!isset($this->productsBySku[strtolower(trim($value ?? ''))])) {
                    $fail("El producto con SKU \"{$value}\" no existe en el sistema.");
                }
            }],
            'sku_proveedor' => 'nullable|string|max:100',
            'costo' => ['nullable', function ($attribute, $value, $fail) {
                if ($this->present($value) && $this->sanitizePrice($value) === null) {
                    $fail('El costo no es un número válido.');
                }
            }],
            'lead_time_dias' => 'nullable|integer|min:0',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'proveedor.required' => 'Falta el proveedor.',
            'sku_producto.required' => 'Falta el SKU del producto.',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
