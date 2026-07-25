<?php

namespace App\Exports\Sheets;

use App\Exports\ProductsExport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Delega en ProductsExport en vez de repetir las 21 columnas: así cualquier
 * columna que se agregue a futuro en el export del catálogo aparece
 * automáticamente también aquí, sin mantener dos listas por separado.
 */
class ProductsUpdateTemplateDataSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    private ProductsExport $inner;

    public function __construct()
    {
        $this->inner = new ProductsExport();
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function query()
    {
        return $this->inner->query();
    }

    public function headings(): array
    {
        return $this->inner->headings();
    }

    /**
     * @param  \App\Models\Products  $product
     */
    public function map($product): array
    {
        return $this->inner->map($product);
    }

    public function styles(Worksheet $sheet)
    {
        return $this->inner->styles($sheet);
    }
}
