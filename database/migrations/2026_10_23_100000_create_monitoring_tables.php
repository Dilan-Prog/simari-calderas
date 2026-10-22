<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Equipos del cliente a monitorear (caldera, torre, chiller...).
        Schema::create('monitoring_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 30)->default('otro'); // caldera|torre_enfriamiento|chiller|suavizador|osmosis|otro
            $table->string('location', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_via', 10)->default('staff'); // staff|customer
            $table->timestamps();
        });

        // Programa de monitoreo de un cliente: N semanas a partir de start_date.
        Schema::create('monitoring_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 150);
            $table->date('start_date');
            $table->unsignedSmallInteger('weeks')->default(4);
            $table->string('status', 20)->default('active'); // active|paused|completed
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
        });

        // Elementos del programa: parámetros del agua a medir o químicos a dosificar.
        // start_week/end_week son las barras del diagrama de Gantt.
        Schema::create('monitoring_program_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('monitoring_programs')->cascadeOnDelete();
            $table->string('kind', 15); // parameter|chemical
            $table->string('name', 120);
            $table->string('unit', 20)->nullable();
            $table->decimal('limit_min', 12, 3)->nullable();
            $table->decimal('limit_max', 12, 3)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('planned_daily_dose', 12, 3)->nullable();
            $table->unsignedSmallInteger('start_week')->default(1);
            $table->unsignedSmallInteger('end_week')->default(1);
            $table->string('added_by', 10)->default('staff'); // staff|customer
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Registros diarios: lectura del agua (reading) o dosificación aplicada (dose).
        Schema::create('monitoring_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('monitoring_programs')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('monitoring_program_items')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('monitoring_equipment')->nullOnDelete();
            $table->string('entry_type', 10); // reading|dose
            $table->date('entry_date');
            $table->decimal('value', 14, 3);
            $table->boolean('in_range')->nullable(); // solo lecturas con rango definido
            $table->string('note', 255)->nullable();
            $table->foreignId('recorded_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['program_id', 'entry_date']);
            $table->index(['equipment_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_entries');
        Schema::dropIfExists('monitoring_program_items');
        Schema::dropIfExists('monitoring_programs');
        Schema::dropIfExists('monitoring_equipment');
    }
};
