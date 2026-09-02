<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gbraid (web-to-app click id de Google Ads) no se capturaba hasta ahora —
 * solo gclid/wbraid. Aditiva, mismo patrón first-touch/last-touch que las
 * columnas ya existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_visits', function (Blueprint $table) {
            $table->string('first_gbraid')->nullable()->after('first_wbraid');
            $table->string('gbraid')->nullable()->index()->after('wbraid');
        });
    }

    public function down(): void
    {
        Schema::table('ad_visits', function (Blueprint $table) {
            $table->dropColumn(['first_gbraid', 'gbraid']);
        });
    }
};
