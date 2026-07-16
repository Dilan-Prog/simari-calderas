<?php

namespace App\Exports;

use App\Exports\Sheets\ProductsTemplateDataSheet;
use App\Exports\Sheets\ProductsTemplateInstructionsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductsTemplateInstructionsSheet(),
            new ProductsTemplateDataSheet(),
        ];
    }
}
