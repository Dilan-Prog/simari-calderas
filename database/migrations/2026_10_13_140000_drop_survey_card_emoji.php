<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Las tarjetas de la encuesta ya no llevan emoji (plataforma profesional): solo texto, estrellas y color. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('survey_cards', 'emoji')) {
            Schema::table('survey_cards', fn (Blueprint $t) => $t->dropColumn('emoji'));
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('survey_cards', 'emoji')) {
            Schema::table('survey_cards', fn (Blueprint $t) => $t->string('emoji', 16)->nullable()->after('label'));
        }
    }
};
