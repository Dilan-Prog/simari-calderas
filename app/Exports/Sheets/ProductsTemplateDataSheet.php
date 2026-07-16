<?php

namespace App\Exports\Sheets;

use App\Models\Brand;
use App\Models\Category;
use App\Services\ProductSkuGenerator;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateDataSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        return [
            'Nombre', 'SKU', 'Modelo', 'Categoría', 'Marca',
            'Descripción Corta', 'Descripción', 'Precio', 'Precio Comparativo',
            'Costo', 'Stock', 'Unidad Stock', 'Moneda', 'Disponibilidad',
            'Activo', 'Destacado', 'Nuevo', 'Recomendado', 'Publicar Web', 'Imagen URL',
        ];
    }

    public function array(): array
    {
        $skus = ProductSkuGenerator::nextBatch(3);

        // Categorías/marcas reales tomadas dinámicamente de la BD (nunca
        // hardcodeadas) para que la plantilla nunca referencie algo
        // inexistente. Se prioriza el dominio del negocio si hay coincidencia.
        $categories = $this->pickDomainNames(Category::class, ['caldera', 'solar', 'calentador'], 3);
        $brands = $this->pickDomainNames(Brand::class, ['simari'], 3);

        return [
            [
                'Caldera Industrial 500L', $skus[0], 'CI-500',
                $categories[0], $brands[0],
                'Caldera industrial de alta eficiencia',
                'Caldera de vapor industrial de 500 litros, ideal para procesos productivos continuos.',
                45000, 52000, 32000, 8, 'pieza', 'MXN', 'available',
                'Si', 'Si', 'No', 'Si', 'Si', '',
            ],
            [
                'Calentador Solar 200L', $skus[1], 'CS-200',
                $categories[1], $brands[1],
                'Sistema de calentamiento solar residencial',
                'Calentador solar de tubos evacuados, capacidad 200 litros, incluye kit de instalación.',
                12500, 14000, 8900, 15, 'pieza', 'MXN', 'available',
                'Si', 'No', 'Si', 'No', 'Si', '',
            ],
            [
                'Caldereta a Gas 100L', $skus[2], 'CG-100',
                $categories[2], $brands[2],
                'Caldereta compacta para uso comercial',
                'Caldereta a gas natural/LP de 100 litros, encendido electrónico, panel digital.',
                9800, '', 6500, 20, 'pieza', 'MXN', 'sobre_pedido',
                'Si', 'No', 'No', 'No', 'No', '',
            ],
        ];
    }

    /**
     * $count nombres de categoría/marca activos, priorizando coincidencias
     * de dominio (ej. "caldera", "solar") y rellenando con las primeras
     * activas si no hay suficientes coincidencias. Nunca devuelve un nombre
     * que no exista realmente en la BD.
     *
     * @param  class-string  $modelClass
     */
    private function pickDomainNames(string $modelClass, array $likeTerms, int $count): array
    {
        $names = [];
        foreach ($likeTerms as $term) {
            $match = $modelClass::where('is_active', true)
                ->where('name', 'like', "%{$term}%")
                ->value('name');
            if ($match && !in_array($match, $names, true)) {
                $names[] = $match;
            }
        }

        if (count($names) < $count) {
            $fallback = $modelClass::where('is_active', true)->pluck('name')->toArray();
            foreach ($fallback as $f) {
                if (count($names) >= $count) {
                    break;
                }
                if (!in_array($f, $names, true)) {
                    $names[] = $f;
                }
            }
        }

        // Si hay menos de $count registros activos en total, repite el
        // último para no dejar celdas vacías en la plantilla.
        while (count($names) < $count && count($names) > 0) {
            $names[] = end($names);
        }

        return $names;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F3B57'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28, 'B' => 14, 'C' => 12, 'D' => 20, 'E' => 16,
            'F' => 30, 'G' => 45, 'H' => 12, 'I' => 14, 'J' => 12,
            'K' => 10, 'L' => 14, 'M' => 10, 'N' => 16,
            'O' => 10, 'P' => 12, 'Q' => 10, 'R' => 14, 'S' => 14, 'T' => 40,
        ];
    }
}
