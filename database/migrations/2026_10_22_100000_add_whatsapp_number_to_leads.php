<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Número de WhatsApp propio del lead (puede ser distinto del teléfono de contacto).
 * Si va vacío se usa el teléfono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->string('contact_whatsapp', 40)->nullable()->after('phone_normalized');
            $t->string('whatsapp_normalized', 20)->nullable()->index()->after('contact_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->dropIndex(['whatsapp_normalized']);
            $t->dropColumn(['contact_whatsapp', 'whatsapp_normalized']);
        });
    }
};
