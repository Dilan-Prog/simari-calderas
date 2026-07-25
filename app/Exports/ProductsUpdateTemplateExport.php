<?php

namespace App\Exports;

use App\Exports\Sheets\ProductsUpdateTemplateDataSheet;
use App\Exports\Sheets\ProductsUpdateTemplateInstructionsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsUpdateTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductsUpdateTemplateInstructionsSheet(),
            new ProductsUpdateTemplateDataSheet(),
        ];
    }
}
