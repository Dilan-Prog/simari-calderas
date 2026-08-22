<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FK a ad_visits.visitor_uuid (columna única, no la PK) -- Laravel/
        // MySQL soportan FK contra una unique key distinta de la PK.
        Schema::table('carts', function (Blueprint $table) {
            $table->uuid('visitor_uuid')->nullable()->after('session_id');
            $table->foreign('visitor_uuid')->references('visitor_uuid')->on('ad_visits')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('visitor_uuid')->nullable()->after('id');
            $table->foreign('visitor_uuid')->references('visitor_uuid')->on('ad_visits')->nullOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('visitor_uuid')->nullable()->after('customer_id');
            // exact (viene de customers.visitor_uuid) | matched_by_contact
            // (coincidencia de email/teléfono contra un carrito ya
            // identificado) | null (sin match).
            $table->string('visitor_uuid_confidence')->nullable()->after('visitor_uuid');
            $table->foreign('visitor_uuid')->references('visitor_uuid')->on('ad_visits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['visitor_uuid']);
            $table->dropColumn('visitor_uuid');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['visitor_uuid']);
            $table->dropColumn('visitor_uuid');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['visitor_uuid']);
            $table->dropColumn(['visitor_uuid', 'visitor_uuid_confidence']);
        });
    }
};
