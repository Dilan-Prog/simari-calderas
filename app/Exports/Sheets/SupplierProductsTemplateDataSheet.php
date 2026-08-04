<?php

namespace App\Exports\Sheets;

use App\Models\Products;
use App\Models\Supplier;
use App\Support\ExcelDropdown;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierProductsTemplateDataSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private ?string $forcedSupplierName = null)
    {
    }

    // Debe coincidir con SupplierProductsImport::sheets().
    public function title(): string
    {
        return 'ProveedoresProductos';
    }

    public function headings(): array
    {
        return ['Proveedor', 'SKU Producto', 'Nombre Producto', 'SKU Proveedor', 'Costo', 'Lead Time (días)', 'Es Principal'];
    }

    public function array(): array
    {
        // Proveedores/productos reales tomados de la BD (nunca inventados)
        // para que la plantilla nunca referencie algo inexistente. Si la
        // descarga viene escopeada a un proveedor (desde su pestaña
        // Productos), los ejemplos usan siempre su nombre real.
        $supplierNames = $this->forcedSupplierName
            ? [$this->forcedSupplierName, $this->forcedSupplierName]
            : Supplier::where('status', 'active')->orderBy('company_name')->limit(2)->pluck('company_name')->toArray();
        $products = Products::where('is_active', true)->orderBy('id', 'desc')->limit(2)->get(['sku', 'name']);

        // Si el catálogo/proveedores todavía tienen menos de 2 registros
        // activos, se repite el último para no dejar celdas vacías.
        while (count($supplierNames) < 2 && count($supplierNames) > 0) {
            $supplierNames[] = end($supplierNames);
        }
        $productList = $products->values();
        while ($productList->count() < 2 && $productList->count() > 0) {
            $productList->push($productList->last());
        }

        if ($supplierNames === [] || $productList->isEmpty()) {
            // Catálogo/proveedores vacíos — no hay nada real que ejemplificar.
            return [];
        }

        return [
            [
                $supplierNames[0], $productList[0]->sku, $productList[0]->name,
                'PROV-EJ-001', 1500, 5, 'Si',
            ],
            [
                $supplierNames[1] ?? $supplierNames[0], $productList[1]->sku ?? $productList[0]->sku, $productList[1]->name ?? $productList[0]->name,
                '', '', '', 'No',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Costo (E) es un monto en pesos.
        $sheet->getStyle('E2:E1000')
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');

        // Es Principal (G): dropdown Sí/No en vez de texto libre.
        ExcelDropdown::applyListDropdown($sheet, 'G', 2, 1000, ['Si', 'No'], 'Es Principal');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F3B57'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 26, 'B' => 16, 'C' => 32, 'D' => 18, 'E' => 12, 'F' => 16, 'G' => 12,
        ];
    }
}
