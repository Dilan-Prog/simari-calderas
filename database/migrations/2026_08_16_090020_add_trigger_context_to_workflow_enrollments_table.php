<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_enrollments', function (Blueprint $table) {
            // Guarda, solo cuando aplica, {"previous": {"<field>": <valor original>}, "actor_user_id": <id o null>}
            // -- capturado en el momento de INSCRIBIR (no en el momento de ejecutar la acción), porque
            // para cuando una acción corre (puede ser tras un paso 'wait', minutos/horas después en un
            // job en cola), $model->getOriginal() ya no refleja el cambio que originó la inscripción.
            $table->json('trigger_context')->nullable()->after('enrollable_id');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_enrollments', function (Blueprint $table) {
            $table->dropColumn('trigger_context');
        });
    }
};
