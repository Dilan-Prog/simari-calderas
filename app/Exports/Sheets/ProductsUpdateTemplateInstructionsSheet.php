<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsUpdateTemplateInstructionsSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['Cómo llenar la plantilla de actualización de productos', '', ''],
            ['Este archivo ya trae tus productos reales con sus valores actuales. Edita solo las celdas que quieras cambiar y deja las demás tal como están: a diferencia de la plantilla de creación, aquí una celda vacía significa "no cambiar" — nunca borra ni resetea el campo en el producto ya guardado.', '', ''],
            ['', '', ''],
            ['Columna', 'Obligatorio', 'Formato / valores permitidos'],
            ['Nombre', 'No', 'Vacío = no cambia. Si se llena, reemplaza el nombre actual.'],
            ['SKU', 'Sí', 'Identifica qué producto se actualiza. No lo cambies ni lo dejes vacío.'],
            ['Modelo', 'No', 'Vacío = no cambia.'],
            ['SKU Proveedor', 'No', 'Vacío = no cambia.'],
            ['Proveedor(es)', 'No', 'Solo de referencia — no se lee al actualizar (la importación multi-proveedor por Excel es una fase futura).'],
            ['Categoría Principal', 'No', 'Vacío = no cambia. Si llenas cualquier campo de categoría, Categoría Principal es obligatoria — selecciónala del menú desplegable.'],
            ['Subcategoría', 'No', 'Vacío = no cambia la categoría. Selecciónala del menú desplegable (depende de la Categoría Principal elegida en la misma fila).'],
            ['Categoría Hija', 'No', 'Vacío = no cambia la categoría. Selecciónala del menú desplegable (depende de la Subcategoría elegida en la misma fila). Si la llenas, Subcategoría también debe estar llena.'],
            ['Marca', 'No', 'Vacío = no cambia. Si se llena, debe coincidir EXACTAMENTE con el nombre de una marca ya registrada en el sistema.'],
            ['Descripción Corta', 'No', 'Vacío = no cambia.'],
            ['Descripción', 'No', 'Vacío = no cambia.'],
            ['Precio', 'No', 'Vacío = no cambia. Si se llena, número, con o sin signo de $ y comas (10000 o $10,000.00 funcionan igual).'],
            ['Precio Comparativo', 'No', 'Vacío = no cambia.'],
            ['Costo', 'No', 'Vacío = no cambia.'],
            ['Stock', 'No', 'Vacío = no cambia. Si se llena, número entero.'],
            ['Unidad Stock', 'No', 'Vacío = no cambia. Selecciónala del menú desplegable: pieza, juego, kit, metro, kg, litro.'],
            ['Moneda', 'No', 'Vacío = no cambia. Selecciónala del menú desplegable: MXN, USD.'],
            ['Disponibilidad', 'No', 'Vacío = no cambia. Selecciónala del menú desplegable: Disponible, Agotado, Sobre Pedido.'],
            ['Activo', 'No', 'Vacío = no cambia. Si / No.'],
            ['Destacado', 'No', 'Vacío = no cambia. Si / No.'],
            ['Nuevo', 'No', 'Vacío = no cambia. Si / No.'],
            ['Recomendado', 'No', 'Vacío = no cambia. Si / No.'],
            ['Publicar Web', 'No', 'Vacío = no cambia. Si / No.'],
            ['Imagen URL', 'No', 'Vacío = no agrega nada. Si se llena con un link (https://...), esa imagen se AGREGA como imagen adicional — nunca reemplaza ni borra las imágenes que el producto ya tenía.'],
            ['', '', ''],
            ['Al importar:', '', ''],
            ['- Si un SKU no existe en el sistema, esa fila se omite y se te informa cuál fue. Esta plantilla nunca crea productos nuevos.', '', ''],
            ['- Una celda vacía conserva el valor actual del producto — no lo borra ni lo resetea.', '', ''],
            ['- Si una Categoría, Marca, Unidad Stock o Moneda no coincide con un valor válido, esa fila se reporta como error y no se aplica ningún cambio de esa fila.', '', ''],
            ['- No cambies ni borres la columna SKU: es la que identifica qué producto se actualiza.', '', ''],
            ['- No agregues, quites ni renombres columnas de la hoja "Productos": el sistema las reconoce por su nombre.', '', ''],
            ['- No renombres la pestaña "Productos": el sistema solo lee los datos de la hoja que tenga ese nombre exacto.', '', ''],
            ['- Categoría Hija requiere que Subcategoría también esté llena — no puedes saltarte un nivel.', '', ''],
            ['- Si el nombre de una categoría se repite en más de una rama del catálogo, el desplegable en cascada de Subcategoría/Categoría Hija podría no mostrar opciones para esa fila — escribe el valor manualmente, la validación al importar de todas formas lo revisa.', '', ''],
            ['- Los desplegables en cascada de categoría funcionan de forma confiable en Microsoft Excel. En Google Sheets, LibreOffice Calc o Apple Numbers pueden no aparecer — si no ves el desplegable, escribe el valor manualmente; el sistema valida los datos al importar de cualquier forma.', '', ''],
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
        $sheet->getStyle('A4:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(\App\Support\ExcelDropdown::HEADER_FILL_COLOR);

        foreach (range(5, 28) as $row) {
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
        }

        $sheet->getStyle('A30')->getFont()->setBold(true);
        $sheet->mergeCells('A30:C30');

        foreach (range(31, 39) as $row) {
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
