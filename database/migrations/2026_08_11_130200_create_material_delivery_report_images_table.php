<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_delivery_report_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_delivery_report_id');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Nombre corto explícito: el autogenerado por Laravel
            // ("material_delivery_report_images_material_delivery_report_id_foreign")
            // excede el límite de 64 caracteres de MySQL para identificadores.
            $table->foreign('material_delivery_report_id', 'mdr_images_report_id_fk')
                ->references('id')->on('material_delivery_reports')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_delivery_report_images');
    }
};
