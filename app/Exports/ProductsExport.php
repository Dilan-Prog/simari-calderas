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
        return Products::with(['category', 'brand', 'images'])->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Nombre', 'SKU', 'Modelo', 'Categoría', 'Marca',
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
        // Precio (H), Precio Comparativo (I) y Costo (J) son montos en
        // pesos — sin este formato se ven como números planos en vez de
        // dinero al abrir el archivo.
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("H2:J{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
