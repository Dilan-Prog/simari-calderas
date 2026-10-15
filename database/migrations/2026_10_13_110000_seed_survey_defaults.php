<?php

use App\Services\SatisfactionSurveyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos por defecto de "Califica este servicio". Idempotente: solo inserta
 * lo que falta (tablas vacías / claves de Setting inexistentes).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('survey_cards') || !Schema::hasTable('survey_questions')) {
            return;
        }

        $now = now();

        if (DB::table('survey_cards')->count() === 0) {
            $cards = [
                ['Malo', '😞', 1, '#ef4444'],
                ['Regular', '😐', 2, '#f97316'],
                ['Bueno', '🙂', 3, '#eab308'],
                ['Muy bueno', '😀', 4, '#84cc16'],
                ['Excelente', '🤩', 5, '#22c55e'],
            ];
            foreach ($cards as $i => [$label, $emoji, $stars, $color]) {
                DB::table('survey_cards')->insert([
                    'label' => $label, 'emoji' => $emoji, 'stars' => $stars, 'color' => $color,
                    'is_active' => true, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('survey_questions')->count() === 0) {
            foreach (['Limpieza', 'Ordenado', 'Resolvió mi problema', 'Tiempo estimado', 'Calidad-precio'] as $i => $label) {
                DB::table('survey_questions')->insert([
                    'label' => $label, 'is_active' => true, 'sort_order' => $i + 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach (SatisfactionSurveyService::DEFAULT_SETTINGS as $key => [$value, $type]) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key, 'value' => $value, 'type' => $type, 'group_name' => 'survey',
                    'is_public' => false, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Los datos por defecto son configuración del negocio: no se borran al revertir.
    }
};
