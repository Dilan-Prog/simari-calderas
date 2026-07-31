<?php

namespace App\Exports;

use App\Exports\Sheets\SupplierProductsTemplateDataSheet;
use App\Exports\Sheets\SupplierProductsTemplateInstructionsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SupplierProductsTemplateExport implements WithMultipleSheets
{
    public function __construct(private ?string $forcedSupplierName = null)
    {
    }

    public function sheets(): array
    {
        return [
            new SupplierProductsTemplateInstructionsSheet(),
            new SupplierProductsTemplateDataSheet($this->forcedSupplierName),
        ];
    }
}
