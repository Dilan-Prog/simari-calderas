<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_report_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_report_id')
                  ->constrained('service_reports')
                  ->cascadeOnDelete();
            $table->string('parameter', 100);
            $table->string('unit', 50)->nullable();
            $table->string('result', 100)->nullable();
            $table->decimal('limit_min', 10, 4)->nullable();
            $table->decimal('limit_max', 10, 4)->nullable();
            $table->boolean('in_range')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_report_measurements');
    }
};
