<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateInstructionsSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return $this->instructionRows();
    }

    public function instructionRows(): array
    {
        return [
            ['Cómo llenar la plantilla de productos', '', ''],
            ['Llena tus productos en la hoja "Productos", debajo de las 3 filas de ejemplo. Puedes borrar los ejemplos antes de subir el archivo, o dejarlos (se omitirán porque su SKU ya quedará registrado la primera vez que los importes).', '', ''],
            ['', '', ''],
            ['Columna', 'Obligatorio', 'Formato / valores permitidos'],
            ['Nombre', 'Sí', 'Texto libre.'],
            ['SKU', 'Sí', 'Código único del producto. No debe repetirse con uno ya existente.'],
            ['Modelo', 'No', 'Texto libre.'],
            ['SKU Proveedor', 'No', 'Texto libre. Código con el que el proveedor identifica este producto (uso interno, distinto del SKU propio).'],
            ['Categoría Principal', 'Sí', 'Selecciónala del menú desplegable. Debe ser una categoría de nivel 1 ya registrada en el sistema.'],
            ['Subcategoría', 'No', 'Selecciónala del menú desplegable (depende de la Categoría Principal elegida en la misma fila). Si la llenas, Categoría Principal es obligatoria.'],
            ['Categoría Hija', 'No', 'Selecciónala del menú desplegable (depende de la Subcategoría elegida en la misma fila). Si la llenas, Subcategoría también debe estar llena.'],
            ['Marca', 'Sí', 'Debe coincidir EXACTAMENTE con el nombre de una marca ya registrada en el sistema (no distingue mayúsculas/minúsculas).'],
            ['Descripción Corta', 'No', 'Texto libre, resumen breve.'],
            ['Descripción', 'No', 'Texto libre, descripción completa.'],
            ['Precio', 'Sí', 'Número, sin símbolo de moneda. Ej: 12500'],
            ['Precio Comparativo', 'No', 'Número. Precio "antes de descuento" a mostrar tachado.'],
            ['Costo', 'No', 'Número. Costo interno del producto.'],
            ['Stock', 'No', 'Número entero. Si se deja vacío, se guarda como 0.'],
            ['Unidad Stock', 'No', 'Selecciónala del menú desplegable: pieza, juego, kit, metro, kg o litro. Si se deja vacío, se usa "pieza".'],
            ['Moneda', 'No', 'Selecciónala del menú desplegable: MXN o USD. Si se deja vacío, se usa "MXN".'],
            ['Disponibilidad', 'No', 'Selecciónala del menú desplegable: Disponible, Agotado o Sobre Pedido. Si se deja vacío, se usa "Disponible".'],
            ['Activo', 'No', 'Si / No. Si se deja vacío, se considera "Si" (el producto se muestra en el catálogo).'],
            ['Destacado', 'No', 'Si / No. Si se deja vacío, se considera "No".'],
            ['Nuevo', 'No', 'Si / No. Si se deja vacío, se considera "No".'],
            ['Recomendado', 'No', 'Si / No. Si se deja vacío, se considera "No".'],
            ['Publicar Web', 'No', 'Si / No. Si se deja vacío, se considera "No" (el producto no se publica en el sitio web público hasta que lo marques explícitamente).'],
            ['Imagen URL', 'No', 'Un link (https://...) a una imagen ya publicada en internet. El sistema la descarga y la guarda como la imagen principal del producto. Debe apuntar directo a un archivo de imagen (jpg, png), no a una página web.'],
            ['Costo Envío', 'No', 'Número. Cargo fijo de envío para este producto (se cobra una sola vez, sin importar la cantidad). Vacío = envío estándar sin costo extra.'],
            ['Envío Gratis Desde', 'No', 'Número. Monto de subtotal del carrito (sin IVA) a partir del cual este producto deja de cobrar Costo Envío. Solo aplica si Costo Envío tiene un valor.'],
            ['Punto Reorden', 'No', 'Número entero. Nivel de stock en el que el sistema debe avisar para reabastecer. Vacío = sin alerta configurada.'],
            ['Mostrar Merchant Center', 'No', 'Si / No. Incluye o excluye el producto del feed de Google Merchant Center. Si se deja vacío, se considera "Si".'],
            ['', '', ''],
            ['Al importar:', '', ''],
            ['- Si un SKU ya existe en el sistema (o se repite dentro del mismo archivo), esa fila se omite y se te informa cuál fue, pero el resto del archivo sí se procesa.', '', ''],
            ['- Si una Categoría o Marca no coincide con ninguna existente, esa fila se reporta como error y no se crea el producto.', '', ''],
            ['- Si la Imagen URL no se puede descargar (link roto, tarda demasiado, o no es una imagen válida), el producto se crea igual sin esa imagen, y se te informa cuál falló.', '', ''],
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
        $sheet->getRowDimension(2)->setRowHeight(45);

        $sheet->getStyle('A4:C4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(\App\Support\ExcelDropdown::HEADER_FILL_COLOR);

        foreach (range(5, 31) as $row) {
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
        }

        $sheet->getStyle('A33')->getFont()->setBold(true);
        $sheet->mergeCells('A33:C33');

        foreach (range(34, 41) as $row) {
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
