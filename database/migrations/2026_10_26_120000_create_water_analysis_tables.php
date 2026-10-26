<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reporte de análisis químico del agua (portal del cliente).
 * - water_analysis_parameters: catálogo de parámetros con sus valores mínimo/máximo (los fija el personal).
 * - water_analysis_reports: un registro por cliente y por día.
 * - water_analysis_results: el valor medido de cada parámetro en ese registro.
 * Los 5 parámetros base se insertan aquí mismo (producción no corre seeders).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('water_analysis_parameters')) {
            Schema::create('water_analysis_parameters', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 120);
                $table->string('unit', 30)->nullable();
                $table->unsignedTinyInteger('decimals')->default(2);
                $table->decimal('min_value', 12, 3)->nullable();
                $table->decimal('max_value', 12, 3)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('water_analysis_reports')) {
            Schema::create('water_analysis_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->date('report_date');
                $table->text('notes')->nullable();
                // Usuario del equipo del portal que lo capturó (sin FK: la tabla de miembros es de otro módulo).
                $table->unsignedBigInteger('recorded_by_member_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['customer_id', 'report_date'], 'water_reports_customer_date_unique');
            });
        }

        if (! Schema::hasTable('water_analysis_results')) {
            Schema::create('water_analysis_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('water_analysis_reports')->cascadeOnDelete();
                $table->foreignId('parameter_id')->constrained('water_analysis_parameters');
                $table->decimal('value', 12, 3);
                $table->timestamps();

                $table->unique(['report_id', 'parameter_id'], 'water_results_report_param_unique');
            });
        }

        $now = now();
        $defaults = [
            ['code' => 'hardness',     'name' => 'Dureza',                              'unit' => 'ppm CaCO₃', 'decimals' => 1, 'sort_order' => 1],
            ['code' => 'ph',           'name' => 'pH',                                  'unit' => null,        'decimals' => 2, 'sort_order' => 2],
            ['code' => 'alkalinity',   'name' => 'Alcalinidad',                         'unit' => 'ppm CaCO₃', 'decimals' => 1, 'sort_order' => 3],
            ['code' => 'conductivity', 'name' => 'Conductividad',                       'unit' => 'µS/cm',     'decimals' => 1, 'sort_order' => 4],
            ['code' => 'tds',          'name' => 'Sólidos Totales Disueltos (STD/TDS)', 'unit' => 'ppm',       'decimals' => 1, 'sort_order' => 5],
        ];

        foreach ($defaults as $row) {
            // Idempotente: si ya existe (y el personal ya editó sus límites) no se toca.
            if (! DB::table('water_analysis_parameters')->where('code', $row['code'])->exists()) {
                DB::table('water_analysis_parameters')->insert($row + [
                    'min_value' => null,
                    'max_value' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('water_analysis_results');
        Schema::dropIfExists('water_analysis_reports');
        Schema::dropIfExists('water_analysis_parameters');
    }
};
