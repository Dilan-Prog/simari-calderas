<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->boolean('requires_invoice')->default(false)->after('notes');
            $table->string('rfc', 13)->nullable()->after('requires_invoice');
            $table->string('uso_cfdi', 10)->nullable()->after('rfc');
            $table->string('razon_social', 150)->nullable()->after('uso_cfdi');
            $table->string('regimen_fiscal', 10)->nullable()->after('razon_social');
            $table->string('cp_fiscal', 10)->nullable()->after('regimen_fiscal');
            $table->string('tax_certificate_path', 255)->nullable()->after('cp_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn([
                'requires_invoice', 'rfc', 'uso_cfdi', 'razon_social', 'regimen_fiscal', 'cp_fiscal', 'tax_certificate_path',
            ]);
        });
    }
};
