<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

/**
 * Panel "Productos" del grupo Ventas: tabla de datos personalizada para el
 * equipo comercial. Independiente del CRUD de catálogo (admin.products.*,
 * grupo Ecommerce).
 *
 * POR AHORA con datos de ejemplo (maqueta del formato acordado). Cuando se
 * defina de dónde salen las cantidades por mes y las columnas Línea/Formato,
 * solo hay que reemplazar sampleRows() por la consulta real -- la vista
 * consume el mismo arreglo.
 */
class SalesProductController extends Controller
{
    public function index()
    {
        return view('admin.sales-products.index', [
            'rows' => $this->sampleRows(),
            'year' => 2026,
        ]);
    }

    /**
     * @return array<int, array{sku: string, description: string, line: string, format: string, months: array<int, int>}>
     *   months: [mes (1-12) => cantidad]
     */
    private function sampleRows(): array
    {
        $rows = [
            ['ALMC-0001', 'AquaTherm 100 FG', 'TRT', 'Garrafa 20 L', [687, 844, 695, 635, 448, 284]],
            ['ALMC-0003', 'Caldesim 100 FG', 'TRT', 'Garrafa 20 L', [229, 224, 149, 186, 149, 37]],
            ['ALMC-0029', 'Secuestrante de oxígeno AquaTherm OX-40', 'TRT', 'Cubeta 19 L', [64, 58, 71, 66, 52, 40]],
            ['ALMC-0030', 'Antiincrustante AquaTherm AI-20', 'TRT', 'Garrafa 20 L', [102, 97, 118, 124, 96, 71]],
            ['ALMC-0005', 'Empaque registro de mano ovalado neopreno 3-1/2" x 4-1/2"', 'EMP', 'Pieza', [40, 36, 52, 47, 38, 30]],
            ['ALMC-0006', 'Empaque 11×15 entrada de hombre Cleaver Brooks CB-700', 'EMP', 'Pieza', [6, 4, 7, 5, 6, 3]],
            ['ALMC-0007', 'Trenza de fibra de vidrio 3/4 pulg', 'EMP', 'Rollo 15 m', [33, 28, 41, 36, 30, 22]],
            ['ALMC-0036', 'Junta espirometálica 2" clase 300', 'EMP', 'Pieza', [18, 22, 15, 26, 20, 14]],
            ['ALMC-0018', 'Control de nivel McDonnell & Miller 150S', 'REF', 'Pieza', [3, 2, 4, 3, 2, 2]],
            ['ALMC-0021', 'Válvula de seguridad Kunkle 6010 1-1/2"', 'REF', 'Pieza', [8, 6, 9, 11, 7, 5]],
            ['ALMC-0024', 'Programador de flama Fireye MicroM', 'REF', 'Pieza', [2, 1, 3, 2, 2, 1]],
            ['ALMC-0025', 'Control de presión Honeywell L404F', 'REF', 'Pieza', [5, 4, 6, 7, 5, 3]],
            ['ALMC-0011', 'Quemador Riello RS 68/M BLU', 'QMD', 'Equipo', [1, 0, 2, 1, 1, 0]],
            ['ALMC-0012', 'Quemador Power Flame JR-30', 'QMD', 'Equipo', [0, 1, 1, 0, 1, 1]],
            ['ALMC-0004', 'Mantenimiento preventivo mayor — Cleaver Brooks 200 HP', 'SRV', 'Servicio', [4, 3, 5, 6, 4, 2]],
            ['ALMC-0033', 'Retrofit de quemador — diagnóstico en sitio', 'SRV', 'Servicio', [7, 9, 6, 8, 10, 5]],
        ];

        return array_map(fn (array $r) => [
            'sku'         => $r[0],
            'description' => $r[1],
            'line'        => $r[2],
            'format'      => $r[3],
            // Ejemplo solo para jul-dic; el resto del año en 0.
            'months'      => array_replace(array_fill(1, 12, 0), array_combine(range(7, 12), $r[4])),
        ], $rows);
    }
}
