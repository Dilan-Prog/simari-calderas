<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelDropdown
{
    // Colores de marca de Equiterm Industries (mismos que el PDF de
    // cotizaciones y las variables CSS del admin) — centralizados aquí para
    // que el encabezado y el relleno de celdas con dropdown se mantengan
    // consistentes en todas las hojas de import/export sin repetir hex.
    public const HEADER_FILL_COLOR = '1A1A1A';
    public const ACCENT_COLOR = 'FF6213';
    public const DROPDOWN_CELL_FILL_COLOR = 'FFEEDD';

    /**
     * Aplica un dropdown nativo de Excel (data validation tipo lista) a un
     * rango de celdas de una sola columna.
     *
     * Nota importante: PhpSpreadsheet invierte el flag showDropDown al
     * escribir el XLSX (ver Writer\Xlsx\Worksheet::writeDataValidations,
     * ~línea 806: `writeAttribute('showDropDown', (!$dv->getShowDropDown() ? '1' : '0'))`).
     * Por eso aquí se deja showDropDown en su valor por defecto (false):
     * así el atributo `showDropDown` queda en "1" en el XML final, que es
     * lo que hace que la flechita del dropdown se muestre en Excel.
     * Llamar a setShowDropDown(true) produciría el efecto contrario.
     */
    public static function applyListDropdown(
        Worksheet $sheet,
        string $column,
        int $firstRow,
        int $lastRow,
        array $options,
        string $promptTitle = 'Selecciona un valor',
        string $errorTitle = 'Valor no válido',
        ?string $errorMessage = null
    ): void {
        $optionsList = implode(',', $options);
        $optionsText = implode(', ', $options);

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $cell = $sheet->getCell("{$column}{$row}");

            // Relleno tenue (tono claro del naranja de marca) para que las
            // celdas con dropdown se distingan a simple vista de las de
            // texto libre, ya que la flechita nativa de Excel solo aparece
            // cuando la celda está seleccionada, no de forma permanente.
            $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(self::DROPDOWN_CELL_FILL_COLOR);

            $validation = $cell->getDataValidation();

            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(false);
            $validation->setPromptTitle($promptTitle);
            $validation->setPrompt('Elige una opción de la lista: ' . $optionsText);
            $validation->setErrorTitle($errorTitle);
            $validation->setError($errorMessage ?? ('El valor debe ser uno de: ' . $optionsText));
            $validation->setFormula1('"' . $optionsList . '"');
        }
    }

    /**
     * Resalta en naranja de marca el texto del encabezado (fila 1) de las
     * columnas indicadas, para que quede claro desde el título de la
     * columna que ese campo es un dropdown — refuerzo visual además del
     * relleno tenue que ya llevan las celdas de datos.
     *
     * @param  string[]  $columns
     */
    public static function applyDropdownColumnHeaderAccent(Worksheet $sheet, array $columns): void
    {
        foreach ($columns as $column) {
            $sheet->getStyle("{$column}1")->getFont()->getColor()->setRGB(self::ACCENT_COLOR);
        }
    }
}
