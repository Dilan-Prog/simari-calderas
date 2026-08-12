<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_variables', function (Blueprint $table) {
            $table->id();
            // workflow_id nullable: null significa variable de scope GLOBAL, no ligada a un workflow específico.
            $table->foreignId('workflow_id')->nullable()->constrained('workflows')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('string'); // valores esperados: string/number/json
            $table->text('value')->nullable();
            $table->timestamps();

            // OJO: MySQL permite múltiples filas NULL en un índice único compuesto (NULL no se considera
            // igual a NULL), por lo que este índice NO impide tener varias variables globales
            // (workflow_id = null) con el mismo name. La unicidad de nombres entre variables globales
            // debe garantizarse a nivel de aplicación si se requiere.
            $table->unique(['workflow_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_variables');
    }
};
