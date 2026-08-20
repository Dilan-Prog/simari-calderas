<?php

namespace App\Exports;

use App\Models\Products;
use App\Support\CategoryCascadeDropdownBuilder;
use App\Support\ExcelDropdown;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function query()
    {
        // Sin filtrar por is_primary: se necesitan TODOS los proveedores del
        // producto para la columna "Proveedor(es)" — el SKU del proveedor
        // principal (columna "SKU Proveedor") se calcula en PHP en map(),
        // filtrando esta misma colección ya cargada, en vez de una segunda
        // consulta con la condición a nivel de query. category.parent.parent
        // se precarga para poder descomponer la categoría real del producto
        // en Principal/Subcategoría/Categoría Hija sin N+1 (Category::levelNames()).
        return Products::with(['category.parent.parent', 'brand', 'images', 'suppliers'])->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Nombre', 'SKU', 'Modelo', 'SKU Proveedor', 'Proveedor(es)',
            'Categoría Principal', 'Subcategoría', 'Categoría Hija', 'Marca',
            'Descripción Corta', 'Descripción', 'Precio', 'Precio Comparativo',
            'Costo', 'Stock', 'Unidad Stock', 'Moneda', 'Disponibilidad',
            'Activo', 'Destacado', 'Nuevo', 'Recomendado', 'Publicar Web', 'Imagen URL',
            'Costo Envío', 'Envío Gratis Desde', 'Punto Reorden', 'Mostrar Merchant Center',
        ];
    }

    /**
     * @param  Products  $product
     */
    public function map($product): array
    {
        [$categoriaPrincipal, $subcategoria, $categoriaHija] = $product->category?->levelNames() ?? ['', '', ''];

        return [
            $product->name,
            $product->sku,
            $product->model,
            $product->suppliers->first(fn ($s) => $s->pivot->is_primary)?->pivot->sku,
            $product->suppliers->map(fn ($s) => $s->company_name . ($s->pivot->sku ? " (SKU: {$s->pivot->sku})" : ''))->implode(', '),
            $categoriaPrincipal,
            $subcategoria,
            $categoriaHija,
            $product->brand->name ?? '',
            $product->short_description,
            $product->description,
            $product->price,
            $product->compare_price,
            $product->cost,
            $product->stock,
            $product->stock_unit,
            $product->currency,
            match ($product->availability) {
                'out_of_stock' => 'Agotado',
                'on_order' => 'Sobre Pedido',
                default => 'Disponible',
            },
            $product->is_active ? 'Si' : 'No',
            $product->is_featured ? 'Si' : 'No',
            $product->is_new ? 'Si' : 'No',
            $product->is_recommended ? 'Si' : 'No',
            $product->publish_on_website ? 'Si' : 'No',
            $product->images->first()?->url ?? '',
            $product->shipping_cost,
            $product->free_shipping_threshold,
            $product->reorder_point,
            $product->show_in_merchant_center ? 'Si' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Precio (L), Precio Comparativo (M) y Costo (N) son montos en
        // pesos — sin este formato se ven como números planos en vez de
        // dinero al abrir el archivo.
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("L2:N{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');
        // Costo Envío (Y) / Envío Gratis Desde (Z) — mismos montos en pesos.
        $sheet->getStyle("Y2:Z{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');

        // Rango extendido más allá de los datos actuales, para que el
        // archivo siga siendo útil si el admin agrega filas nuevas antes de
        // reimportarlo (flujo de "actualizar en lote" reutilizando este
        // mismo export como plantilla de partida).
        $dropdownLastRow = $lastRow + 500;

        CategoryCascadeDropdownBuilder::apply($sheet, 'F', 'G', 'H', 2, $dropdownLastRow);

        ExcelDropdown::applyListDropdown($sheet, 'P', 2, $dropdownLastRow, ['pieza', 'juego', 'kit', 'metro', 'kg', 'litro'], 'Unidad de Stock');
        ExcelDropdown::applyListDropdown($sheet, 'Q', 2, $dropdownLastRow, ['MXN', 'USD'], 'Moneda');
        ExcelDropdown::applyListDropdown($sheet, 'R', 2, $dropdownLastRow, ['Disponible', 'Agotado', 'Sobre Pedido'], 'Disponibilidad');
        foreach (['S', 'T', 'U', 'V', 'W'] as $boolColumn) {
            ExcelDropdown::applyListDropdown($sheet, $boolColumn, 2, $dropdownLastRow, ['Si', 'No'], 'Sí / No');
        }
        ExcelDropdown::applyListDropdown($sheet, 'AB', 2, $dropdownLastRow, ['Si', 'No'], 'Sí / No');

        // Estilo base del encabezado aplicado directamente (no vía el array
        // de retorno) para poder pintar el acento naranja de las columnas
        // con dropdown DESPUÉS, sin que se sobreescriba — el array que
        // devuelve styles() lo aplica el framework al final del método, así
        // que cualquier estilo de fila 1 puesto ahí pisaría un acento
        // aplicado antes dentro del cuerpo del método.
        $sheet->getStyle('A1:AB1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => ExcelDropdown::HEADER_FILL_COLOR],
            ],
        ]);

        ExcelDropdown::applyDropdownColumnHeaderAccent($sheet, ['F', 'G', 'H', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'AB']);

        return [];
    }
}
