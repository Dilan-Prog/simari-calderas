<?php

namespace App\Support;

use App\Models\Category;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds cascading (3-level) Excel dropdowns for the product category
 * hierarchy: Categoría Principal -> Subcategoría -> Categoría Hija.
 *
 * Implemented with the classic Excel INDIRECT(named_range) pattern:
 *   - A hidden "CategoriasAux" worksheet holds one column per parent
 *     category, listing that parent's children.
 *   - Each such column is registered as a global Named Range whose name is
 *     a sanitized version of the parent category's name.
 *   - The dependent dropdown's DataValidation formula uses INDIRECT() with
 *     a nested SUBSTITUTE() expression that rebuilds the exact same
 *     sanitized name from the text typed/selected in the parent cell.
 */
class CategoryCascadeDropdownBuilder
{
    private const REPLACEMENTS = [
        ' ' => '_', '/' => '_', '-' => '_', '.' => '_', ',' => '_',
        '(' => '', ')' => '',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'ñ' => 'n', 'Ñ' => 'N',
    ];

    public static function sanitizeForExcelName(string $name): string
    {
        $clean = strtr(trim($name), self::REPLACEMENTS);
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', $clean) ?? '';

        return 'CAT_' . $clean;
    }

    /**
     * Builds the nested SUBSTITUTE(...) expression (without the surrounding
     * INDIRECT()) that reconstructs the sanitized named-range name from the
     * text contained in $cellRef, mirroring self::REPLACEMENTS exactly.
     */
    public static function buildSubstituteFormula(string $cellRef): string
    {
        $expression = $cellRef;

        foreach (self::REPLACEMENTS as $search => $replace) {
            $expression = sprintf(
                'SUBSTITUTE(%s,%s,%s)',
                $expression,
                self::excelQuote($search),
                self::excelQuote($replace)
            );
        }

        return '"CAT_"&' . $expression;
    }

    private static function excelQuote(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    public static function apply(
        Worksheet $sheet,
        string $principalCol,
        string $subCol,
        string $childCol,
        int $firstRow,
        int $lastRow
    ): void {
        $spreadsheet = $sheet->getParent();
        if ($spreadsheet === null) {
            throw new \RuntimeException('El worksheet no tiene un Spreadsheet padre asociado.');
        }

        // 1. Load the full category tree, grouped by parent_id (0 = root).
        $categories = Category::select('id', 'name', 'parent_id')->get();
        $byParent = [];
        foreach ($categories as $category) {
            $parentKey = $category->parent_id ?? 0;
            $byParent[$parentKey][] = $category;
        }

        $level1 = $byParent[0] ?? [];

        // 2. Detect unsafe/collisioned categories.
        $unsafeCategoryIds = [];

        // 2a. Level 1 categories grouped by sanitized name.
        $level1Groups = [];
        foreach ($level1 as $category) {
            $level1Groups[self::sanitizeForExcelName($category->name)][] = $category->id;
        }

        // 2b. Level 2 categories that have children, grouped by sanitized name.
        $level2WithChildren = [];
        foreach ($level1 as $parent) {
            $children = $byParent[$parent->id] ?? [];
            foreach ($children as $level2Category) {
                if (!empty($byParent[$level2Category->id])) {
                    $level2WithChildren[] = $level2Category;
                }
            }
        }

        $level2Groups = [];
        foreach ($level2WithChildren as $category) {
            $level2Groups[self::sanitizeForExcelName($category->name)][] = $category->id;
        }

        foreach ([$level1Groups, $level2Groups] as $groups) {
            foreach ($groups as $ids) {
                if (count(array_unique($ids)) > 1) {
                    foreach ($ids as $id) {
                        $unsafeCategoryIds[$id] = true;
                    }
                }
            }
        }

        // 2c. Names that, after only strtr(), still contain characters the
        // Excel-side SUBSTITUTE() reconstruction cannot safely handle.
        $namesToCheck = array_merge($level1, $level2WithChildren);
        foreach ($namesToCheck as $category) {
            $afterStrtr = strtr(trim($category->name), self::REPLACEMENTS);
            if (preg_match('/[^A-Za-z0-9_]/', $afterStrtr) === 1) {
                $unsafeCategoryIds[$category->id] = true;
            }
        }

        // 3. Get or create the hidden auxiliary sheet.
        $aux = $spreadsheet->getSheetByName('CategoriasAux');
        if ($aux === null) {
            $aux = $spreadsheet->createSheet();
            $aux->setTitle('CategoriasAux');
            $aux->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        }

        // 4 & 5. Write columns and register named ranges.

        // Column A: all level 1 category names.
        $aux->setCellValue('A1', 'Categoría');
        $row = 2;
        foreach ($level1 as $category) {
            $aux->setCellValue('A' . $row, $category->name);
            $row++;
        }
        $level1LastRow = max(1, $row - 1);

        $nextColumnIndex = 2; // Column B onward.

        // One column per non-unsafe level 1 category, listing its level 2 children.
        foreach ($level1 as $parent) {
            if (isset($unsafeCategoryIds[$parent->id])) {
                continue;
            }

            $children = $byParent[$parent->id] ?? [];
            $columnLetter = Coordinate::stringFromColumnIndex($nextColumnIndex);
            $nextColumnIndex++;

            $rangeName = self::sanitizeForExcelName($parent->name);
            $aux->setCellValue($columnLetter . '1', $rangeName);

            $childRow = 2;
            foreach ($children as $child) {
                $aux->setCellValue($columnLetter . $childRow, $child->name);
                $childRow++;
            }
            $lastDataRow = 1 + count($children);

            $range = "CategoriasAux!\${$columnLetter}\$2:\${$columnLetter}\${$lastDataRow}";
            $spreadsheet->addNamedRange(new NamedRange($rangeName, $aux, $range));
        }

        // One column per non-unsafe level 2 category with children, listing its level 3 children.
        foreach ($level2WithChildren as $level2Category) {
            if (isset($unsafeCategoryIds[$level2Category->id])) {
                continue;
            }

            $children = $byParent[$level2Category->id] ?? [];
            $columnLetter = Coordinate::stringFromColumnIndex($nextColumnIndex);
            $nextColumnIndex++;

            $rangeName = self::sanitizeForExcelName($level2Category->name);
            $aux->setCellValue($columnLetter . '1', $rangeName);

            $childRow = 2;
            foreach ($children as $child) {
                $aux->setCellValue($columnLetter . $childRow, $child->name);
                $childRow++;
            }
            $lastDataRow = 1 + count($children);

            $range = "CategoriasAux!\${$columnLetter}\$2:\${$columnLetter}\${$lastDataRow}";
            $spreadsheet->addNamedRange(new NamedRange($rangeName, $aux, $range));
        }

        // 6. Apply data validation to the data sheet.
        for ($rowNum = $firstRow; $rowNum <= $lastRow; $rowNum++) {
            self::applyListValidation(
                $sheet,
                "{$principalCol}{$rowNum}",
                "CategoriasAux!\$A\$2:\$A\${$level1LastRow}",
                'Categoría Principal',
                'Seleccione una categoría principal de la lista.'
            );

            $subFormula = 'INDIRECT(' . self::buildSubstituteFormula("{$principalCol}{$rowNum}") . ')';
            self::applyListValidation(
                $sheet,
                "{$subCol}{$rowNum}",
                $subFormula,
                'Subcategoría',
                'Seleccione una subcategoría válida para la categoría principal elegida.'
            );

            $childFormula = 'INDIRECT(' . self::buildSubstituteFormula("{$subCol}{$rowNum}") . ')';
            self::applyListValidation(
                $sheet,
                "{$childCol}{$rowNum}",
                $childFormula,
                'Categoría Hija',
                'Seleccione una categoría hija válida para la subcategoría elegida.'
            );
        }
    }

    private static function applyListValidation(
        Worksheet $sheet,
        string $cellCoordinate,
        string $formula1,
        string $errorTitle,
        string $errorMessage
    ): void {
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle($errorTitle);
        $validation->setError($errorMessage);
        $validation->setFormula1($formula1);
    }
}
