<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_report_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_report_id')
                  ->constrained('service_reports')
                  ->cascadeOnDelete();
            $table->longText('content');
            $table->json('systems_checked')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_report_activities');
    }
};
