<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_report_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label', 100);
            $table->enum('form_behavior', ['measurements', 'activity', 'custom']);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Siembra los 8 tipos actualmente hardcodeados, preservando exactamente
        // sus `key` originales (los reportes ya guardados en BD almacenan estos
        // strings tal cual en service_reports.service_type).
        //
        // Mapeo form_behavior verificado contra ServiceReport::usesActivityForm()/
        // usesMeasurementsForm()/usesCustomForm() antes de sembrar:
        //   - chemical_analysis      => measurements
        //   - custom                 => custom
        //   - todos los demás (6)    => activity
        DB::table('service_report_types')->insert([
            ['key' => 'chemical_analysis',      'label' => 'Análisis Químico',         'form_behavior' => 'measurements', 'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'maintenance_preventive', 'label' => 'Mantenimiento Preventivo', 'form_behavior' => 'activity',     'sort_order' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'maintenance_corrective', 'label' => 'Mantenimiento Correctivo', 'form_behavior' => 'activity',     'sort_order' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'inspection',             'label' => 'Inspección',               'form_behavior' => 'activity',     'sort_order' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cleaning',               'label' => 'Limpieza',                 'form_behavior' => 'activity',     'sort_order' => 5, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'calibration',            'label' => 'Calibración',              'form_behavior' => 'activity',     'sort_order' => 6, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'activity_report',        'label' => 'Reporte de Actividades',   'form_behavior' => 'activity',     'sort_order' => 7, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'custom',                 'label' => 'Personalizado',            'form_behavior' => 'custom',       'sort_order' => 8, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_report_types');
    }
};
