<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code', 30);
            $table->string('name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->unique(['warehouse_id', 'code']);
        });

        // Opcional: ubicación del saldo. Los saldos existentes quedan sin ubicación (null).
        Schema::table('warehouse_product_stock', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('product_id');
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_product_stock', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
        Schema::dropIfExists('warehouse_locations');
    }
};
