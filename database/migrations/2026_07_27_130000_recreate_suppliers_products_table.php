<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La tabla original nunca llegó a usarse (0 filas en producción) — se
        // recrea con id propio en vez de intentar un ALTER delicado sobre la
        // llave primaria compuesta.
        Schema::dropIfExists('suppliers_products');

        Schema::create('suppliers_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['supplier_id', 'product_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers_products');

        Schema::create('suppliers_products', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('cost', 12, 2)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->primary(['supplier_id', 'product_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
