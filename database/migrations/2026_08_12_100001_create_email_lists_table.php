<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Algunos entornos (ej. producción) tienen una tabla `email_lists` huérfana
        // preexistente, de un schema legacy no relacionado con este módulo CRM y sin
        // migración que la respalde. En vez de asumir que no existe (lo que rompe el
        // deploy con "table already exists") o borrarla a ciegas (riesgo de perder
        // datos que no hemos podido inspeccionar), la renombramos a un backup con
        // timestamp si su schema no coincide con el nuevo, y creamos la tabla limpia.
        if (Schema::hasTable('email_lists')) {
            $columns = Schema::getColumnListing('email_lists');
            $hasNewSchema = in_array('type', $columns) && in_array('filter_definition', $columns);

            if (!$hasNewSchema) {
                Schema::rename('email_lists', 'email_lists_legacy_backup_' . date('YmdHis'));
            }
        }

        if (!Schema::hasTable('email_lists')) {
            Schema::create('email_lists', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->json('filter_definition')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_lists');
    }
};
