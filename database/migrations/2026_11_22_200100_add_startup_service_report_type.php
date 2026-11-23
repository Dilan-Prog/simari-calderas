<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de reporte «Arranque» (key `startup`), que la app móvil ofrece en el
 * paso «Trabajo».
 *
 * Además: service_reports.service_type seguía siendo el ENUM original de 8
 * valores (2026_05_27_000001), aunque desde 2026_08_11_160000 los tipos viven
 * en service_report_types y el panel deja crear tipos nuevos. Con MySQL en
 * modo estricto, guardar un reporte con cualquier key que no esté en el ENUM
 * (incluida `startup`) fallaba con "Data truncated". Se convierte a
 * VARCHAR(60), el mismo largo que service_report_types.key.
 */
return new class extends Migration
{
    private const LEGACY_ENUM = [
        'chemical_analysis', 'maintenance_preventive', 'maintenance_corrective', 'inspection',
        'cleaning', 'calibration', 'activity_report', 'custom',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE service_reports MODIFY service_type VARCHAR(60) NOT NULL');
        }

        // Idempotente: si ya existe (p. ej. un admin la creó desde el panel), se respeta.
        if (! DB::table('service_report_types')->where('key', 'startup')->exists()) {
            DB::table('service_report_types')->insert([
                'key'           => 'startup',
                'label'         => 'Arranque',
                'form_behavior' => 'activity',
                'is_active'     => true,
                'sort_order'    => ((int) DB::table('service_report_types')->max('sort_order')) + 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('service_report_types')->where('key', 'startup')->delete();

        // Solo se regresa al ENUM si ningún reporte usa un valor fuera de él
        // (si no, el ALTER truncaría datos).
        if (DB::getDriverName() === 'mysql'
            && ! DB::table('service_reports')->whereNotIn('service_type', self::LEGACY_ENUM)->exists()) {
            $values = implode(',', array_map(fn ($v) => "'{$v}'", self::LEGACY_ENUM));
            DB::statement("ALTER TABLE service_reports MODIFY service_type ENUM({$values}) NOT NULL");
        }
    }
};
