<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_report_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_report_id')
                  ->constrained('service_reports')
                  ->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->enum('field_type', ['text', 'number', 'date', 'boolean', 'list']);
            $table->text('field_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_report_custom_fields');
    }
};
