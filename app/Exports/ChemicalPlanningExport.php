<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A diferencia de ProductsExport (FromQuery sobre un modelo Eloquent), la
 * fuente aquí ya es una colección de filas pre-agregadas (cliente+producto,
 * ver ChemicalPlanningController::index()) — FromCollection en vez de
 * FromQuery.
 */
class ChemicalPlanningExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Cliente', 'Producto', 'Entregado', 'Por Entregar', 'Proyección', 'Inventario'];
    }

    public function map($row): array
    {
        return [
            $row['customer_name'],
            $row['product_name'],
            $row['delivered'],
            $row['pending'],
            $row['projected_quantity'],
            $row['stock'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
