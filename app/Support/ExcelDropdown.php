<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelDropdown
{
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
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();

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
}
