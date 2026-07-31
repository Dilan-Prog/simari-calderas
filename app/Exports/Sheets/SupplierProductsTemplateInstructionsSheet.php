<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierProductsTemplateInstructionsSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['Cómo llenar la plantilla de Proveedores Productos', '', ''],
            ['Llena en la hoja "ProveedoresProductos" cada vínculo proveedor-producto que quieras crear o actualizar. Si el proveedor y el producto ya están vinculados, se actualiza ese vínculo; si no, se crea uno nuevo. Una celda vacía en un vínculo que ya existe significa "no cambiar ese campo" — nunca lo borra.', '', ''],
            ['', '', ''],
            ['Columna', 'Obligatorio', 'Formato / valores permitidos'],
            ['Proveedor', 'Sí', 'Debe coincidir EXACTAMENTE con el nombre de un proveedor ya registrado en el sistema (no distingue mayúsculas/minúsculas).'],
            ['SKU Producto', 'Sí', 'Debe coincidir EXACTAMENTE con el SKU de un producto ya registrado en el sistema.'],
            ['Nombre Producto', 'No', 'Solo de referencia — no se lee al importar, es únicamente para que identifiques la fila.'],
            ['SKU Proveedor', 'No', 'Texto libre. Código con el que ese proveedor identifica este producto. Vacío en un vínculo existente = no cambia.'],
            ['Costo', 'No', 'Número, con o sin signo de $ y comas (1500 o $1,500.00 funcionan igual). Vacío en un vínculo existente = no cambia.'],
            ['Lead Time (días)', 'No', 'Número entero. Vacío en un vínculo existente = no cambia.'],
            ['Es Principal', 'No', 'Si / No. Si marcas "Si", ese proveedor queda como el principal de ese producto y cualquier otro proveedor que ya fuera principal para el mismo producto deja de serlo automáticamente.'],
            ['', '', ''],
            ['Al importar:', '', ''],
            ['- Si el Proveedor o el SKU Producto no existen, esa fila se reporta como error y no se crea ni actualiza nada.', '', ''],
            ['- Si el vínculo proveedor-producto ya existe, se actualiza; si no existe, se crea.', '', ''],
            ['- Una celda vacía en un vínculo que ya existía conserva el valor actual — no lo borra ni lo resetea.', '', ''],
            ['- No agregues, quites ni renombres columnas de la hoja "ProveedoresProductos": el sistema las reconoce por su nombre.', '', ''],
            ['- No renombres la pestaña "ProveedoresProductos": el sistema solo lee los datos de la hoja que tenga ese nombre exacto.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:C1');

        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->mergeCells('A2:C2');
        $sheet->getRowDimension(2)->setRowHeight(60);

        $sheet->getStyle('A4:C4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F3B57');

        foreach (range(5, 11) as $row) {
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
        }

        $sheet->getStyle('A13')->getFont()->setBold(true);
        $sheet->mergeCells('A13:C13');

        foreach (range(14, 18) as $row) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 14,
            'C' => 70,
        ];
    }
}
