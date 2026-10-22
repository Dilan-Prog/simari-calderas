<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sitio web del lead (contacto) y del cliente: se captura en la bandeja de leads y pasa al cliente al agregarlo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->string('contact_website', 255)->nullable()->after('contact_email');
        });
        Schema::table('customers', function (Blueprint $t) {
            $t->string('website', 255)->nullable()->after('company');
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('website'));
        Schema::table('leads', fn (Blueprint $t) => $t->dropColumn('contact_website'));
    }
};
