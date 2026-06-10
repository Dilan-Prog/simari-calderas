<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guardamos los datos existentes
        $existing = DB::table('service_materials_planned')->get();

        // Eliminamos la tabla actual
        Schema::drop('service_materials_planned');

        // Recreamos con estructura completa
        Schema::create('service_materials_planned', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            // Nullable para permitir líneas libres
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // Snapshot del nombre
            $table->string('product_name', 180)->nullable();

            // SKU
            $table->string('product_sku', 80)->nullable();

            // Cantidad
            $table->integer('quantity')->default(1);

            // Unidad de medida
            $table->enum('unit', [
                'litros',
                'kg',
                'piezas',
                'metros',
                'galones',
                'otro'
            ])->default('piezas');

            // Notas por línea
            $table->text('notes')->nullable();

            // Orden
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        // Restauramos los datos existentes
        foreach ($existing as $row) {
            DB::table('service_materials_planned')->insert([
                'service_id' => $row->service_id,
                'product_id' => $row->product_id,
                'product_name' => null,
                'product_sku'  => null,
                'quantity'     => $row->quantity,
                'unit'         => 'piezas',
                'notes'        => null,
                'sort_order'   => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::drop('service_materials_planned');

        Schema::create('service_materials_planned', function (Blueprint $table) {
            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->integer('quantity');
            $table->primary(['service_id', 'product_id']);
        });
    }
};
