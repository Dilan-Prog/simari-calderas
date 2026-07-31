<?php

namespace App\Exports;

use App\Models\Products;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function query()
    {
        // Sin filtrar por is_primary: se necesitan TODOS los proveedores del
        // producto para la columna "Proveedor(es)" — el SKU del proveedor
        // principal (columna "SKU Proveedor") se calcula en PHP en map(),
        // filtrando esta misma colección ya cargada, en vez de una segunda
        // consulta con la condición a nivel de query.
        return Products::with(['category', 'brand', 'images', 'suppliers'])->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Nombre', 'SKU', 'Modelo', 'SKU Proveedor', 'Proveedor(es)', 'Categoría', 'Marca',
            'Descripción Corta', 'Descripción', 'Precio', 'Precio Comparativo',
            'Costo', 'Stock', 'Unidad Stock', 'Moneda', 'Disponibilidad',
            'Activo', 'Destacado', 'Nuevo', 'Recomendado', 'Publicar Web', 'Imagen URL',
        ];
    }

    /**
     * @param  Products  $product
     */
    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->model,
            $product->suppliers->first(fn ($s) => $s->pivot->is_primary)?->pivot->sku,
            $product->suppliers->map(fn ($s) => $s->company_name . ($s->pivot->sku ? " (SKU: {$s->pivot->sku})" : ''))->implode(', '),
            $product->category->name ?? '',
            $product->brand->name ?? '',
            $product->short_description,
            $product->description,
            $product->price,
            $product->compare_price,
            $product->cost,
            $product->stock,
            $product->stock_unit,
            $product->currency,
            $product->availability,
            $product->is_active ? 'Si' : 'No',
            $product->is_featured ? 'Si' : 'No',
            $product->is_new ? 'Si' : 'No',
            $product->is_recommended ? 'Si' : 'No',
            $product->publish_on_website ? 'Si' : 'No',
            $product->images->first()?->url ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Precio (J), Precio Comparativo (K) y Costo (L) son montos en
        // pesos — sin este formato se ven como números planos en vez de
        // dinero al abrir el archivo. (Corrimiento de una columna más por el
        // nuevo campo "Proveedor(es)" insertado después de "SKU Proveedor".)
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("J2:L{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
