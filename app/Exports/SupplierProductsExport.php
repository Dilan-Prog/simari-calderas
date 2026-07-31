<?php

namespace App\Exports;

use App\Models\SupplierProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    // Debe coincidir exactamente con SupplierProductsImport::sheets() para
    // que este mismo archivo se pueda reimportar sin problema.
    public function title(): string
    {
        return 'ProveedoresProductos';
    }

    public function query()
    {
        return SupplierProduct::with(['supplier:id,company_name', 'product:id,name,sku'])
            ->join('suppliers', 'suppliers.id', '=', 'suppliers_products.supplier_id')
            ->orderBy('suppliers.company_name')
            ->select('suppliers_products.*');
    }

    public function headings(): array
    {
        return ['Proveedor', 'SKU Producto', 'Nombre Producto', 'SKU Proveedor', 'Costo', 'Lead Time (días)', 'Es Principal'];
    }

    /**
     * @param  SupplierProduct  $row
     */
    public function map($row): array
    {
        return [
            $row->supplier->company_name ?? '',
            $row->product->sku ?? '',
            $row->product->name ?? '',
            $row->sku,
            $row->cost,
            $row->lead_time_days,
            $row->is_primary ? 'Si' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Costo (E) es un monto en pesos.
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle("E2:E{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
