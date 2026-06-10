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
        Schema::table('services', function (Blueprint $table) {

            // Folio único SVC-2026-0001
            $table->string('service_number', 20)
                  ->unique()
                  ->nullable()
                  ->after('id');

            // Quién creó el servicio en el panel
            $table->foreignId('created_by_user_id')
                  ->nullable()
                  ->after('customer_id')
                  ->constrained('users')
                  ->nullOnDelete();

            // Si viene de una cotización aceptada
            $table->foreignId('from_quote_id')
                  ->nullable()
                  ->after('created_by_user_id')
                  ->constrained('quotes')
                  ->nullOnDelete();

            // Fecha y hora programada del servicio
            $table->date('service_date')
                  ->nullable()
                  ->after('from_quote_id');

            $table->time('service_time')
                  ->nullable()
                  ->after('service_date');

            // Duración estimada en horas
            $table->decimal('estimated_duration', 4, 1)
                  ->nullable()
                  ->after('service_time');

            // Prioridad
            $table->enum('priority', ['normal', 'high', 'urgent'])
                  ->default('normal')
                  ->after('estimated_duration');

            // Dirección donde se realiza el servicio
            $table->string('address', 255)
                  ->nullable()
                  ->after('priority');

            $table->string('reference', 255)
                  ->nullable()
                  ->after('address');

            // Ubicación/planta
            $table->string('location', 200)
                  ->nullable()
                  ->after('reference');

            // Semana del servicio
            $table->tinyInteger('week_number')
                  ->unsigned()
                  ->nullable()
                  ->after('location');

            // Para retomar el formulario multi-etapa
            $table->tinyInteger('current_step')
                  ->unsigned()
                  ->default(1)
                  ->after('week_number');

            // Cambiar el enum de status para que coincida con el módulo
            // scheduled|in_progress|completed|cancelled
            // El valor anterior era: pending
            $table->enum('status', [
                'draft',
                'scheduled',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('services', function (Blueprint $table) {
            // Revertir enum status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending')
                  ->change();

            // Eliminar foreign keys primero
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['from_quote_id']);

            // Eliminar columnas
            $table->dropColumn([
                'service_number',
                'created_by_user_id',
                'from_quote_id',
                'service_date',
                'service_time',
                'estimated_duration',
                'priority',
                'address',
                'reference',
                'location',
                'week_number',
                'current_step',
            ]);
        });
    }
};

