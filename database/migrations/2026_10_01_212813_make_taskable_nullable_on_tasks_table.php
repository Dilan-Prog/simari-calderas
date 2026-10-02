<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Permite tareas manuales sueltas (creadas desde el panel admin, sin
        // ligarlas a una cotización/negocio) -- las creadas por N8N/automatizaciones
        // siguen mandando ambos campos (Api\Crm\TaskController::store() los
        // sigue exigiendo 'required', esto solo relaja la columna en BD).
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('taskable_type')->nullable()->change();
            $table->unsignedBigInteger('taskable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('taskable_type')->nullable(false)->change();
            $table->unsignedBigInteger('taskable_id')->nullable(false)->change();
        });
    }
};
