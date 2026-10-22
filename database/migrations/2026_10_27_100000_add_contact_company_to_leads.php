<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Empresa del lead (contacto): se captura en la bandeja de leads y pasa al cliente al agregarlo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->string('contact_company', 150)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $t) => $t->dropColumn('contact_company'));
    }
};
