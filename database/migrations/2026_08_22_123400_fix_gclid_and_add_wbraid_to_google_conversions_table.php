<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // gclid era NOT NULL -- no podía almacenar una conversión que solo
        // tenga wbraid (tráfico de app/iOS). wbraid no existía en absoluto.
        Schema::table('google_conversions', function (Blueprint $table) {
            $table->string('gclid')->nullable()->change();
            $table->string('wbraid')->nullable()->after('gclid');
        });
    }

    public function down(): void
    {
        Schema::table('google_conversions', function (Blueprint $table) {
            $table->dropColumn('wbraid');
            $table->string('gclid')->nullable(false)->change();
        });
    }
};
