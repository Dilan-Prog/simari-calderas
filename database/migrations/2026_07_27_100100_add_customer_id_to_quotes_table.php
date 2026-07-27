<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Nullable: las cotizaciones existentes (guardadas solo con
            // datos de invitado) no tienen a qué cliente vincularse todavía
            // — se completan poco a poco vía la edición o el endpoint de
            // vinculación para las que ya no son editables (aceptadas).
            $table->foreignId('customer_id')->nullable()->after('created_by_user_id')
                ->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
