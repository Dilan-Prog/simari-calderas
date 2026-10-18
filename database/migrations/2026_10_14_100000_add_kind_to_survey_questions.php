<?php

use App\Services\SatisfactionSurveyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Califica este servicio" con chips: las variables pasan de filas de 5 estrellas a
 * chips seleccionables. `kind` = positive (lo mejor, nivel >= 4) | negative (a mejorar, nivel < 4).
 * Idempotente: solo agrega lo que falta (por nombre y tipo), sin pisar lo editado por el dueño.
 */
return new class extends Migration
{
    private const POSITIVE = ['Puntualidad', 'Trato del técnico', 'Rapidez', 'Orden y limpieza', 'Explicación clara', 'Calidad del trabajo'];
    private const NEGATIVE = ['Llegó tarde', 'Trabajo incompleto', 'Poca comunicación', 'Área sucia', 'Tiempo de espera', 'La falla persiste'];

    public function up(): void
    {
        if (Schema::hasTable('survey_questions') && !Schema::hasColumn('survey_questions', 'kind')) {
            Schema::table('survey_questions', fn (Blueprint $t) => $t->string('kind', 12)->default('positive')->after('label'));
        }
        if (Schema::hasTable('service_rating_answers') && !Schema::hasColumn('service_rating_answers', 'kind')) {
            Schema::table('service_rating_answers', fn (Blueprint $t) => $t->string('kind', 12)->default('positive')->after('question_label'));
        }

        if (Schema::hasTable('survey_questions')) {
            $now = now();
            $next = (int) DB::table('survey_questions')->max('sort_order');

            foreach (['positive' => self::POSITIVE, 'negative' => self::NEGATIVE] as $kind => $labels) {
                $existing = DB::table('survey_questions')->where('kind', $kind)->pluck('label')
                    ->map(fn ($l) => mb_strtolower(trim($l)))->all();

                foreach ($labels as $label) {
                    if (in_array(mb_strtolower($label), $existing, true)) {
                        continue;
                    }
                    DB::table('survey_questions')->insert([
                        'label' => $label, 'kind' => $kind, 'is_active' => true, 'sort_order' => ++$next,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasTable('settings')) {
            $now = now();
            foreach (SatisfactionSurveyService::DEFAULT_SETTINGS as $key => [$value, $type]) {
                if (!DB::table('settings')->where('key', $key)->exists()) {
                    DB::table('settings')->insert([
                        'key' => $key, 'value' => $value, 'type' => $type, 'group_name' => 'survey',
                        'is_public' => false, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_rating_answers', 'kind')) {
            Schema::table('service_rating_answers', fn (Blueprint $t) => $t->dropColumn('kind'));
        }
        if (Schema::hasColumn('survey_questions', 'kind')) {
            Schema::table('survey_questions', fn (Blueprint $t) => $t->dropColumn('kind'));
        }
    }
};
