<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 20)->unique();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name', 200);
            $table->string('customer_company', 200)->nullable();
            $table->string('customer_rfc', 20)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->enum('service_type', [
                'chemical_analysis',
                'maintenance_preventive',
                'maintenance_corrective',
                'inspection',
                'cleaning',
                'calibration',
                'activity_report',
                'custom',
            ]);
            $table->string('custom_service_type', 100)->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'signed'])->default('draft');
            $table->date('service_date');
            $table->unsignedSmallInteger('week_number')->nullable();
            $table->string('location', 200)->nullable();
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();
            $table->tinyInteger('sampling_date_day')->unsigned()->nullable();
            $table->tinyInteger('sampling_date_month')->unsigned()->nullable();
            $table->smallInteger('sampling_date_year')->unsigned()->nullable();
            $table->string('analyst_name', 150)->nullable();
            $table->string('analyst_position', 100)->nullable();
            $table->boolean('include_sampling')->default(false);
            $table->longText('signature_data')->nullable();
            $table->string('signature_name', 150)->nullable();
            $table->string('signature_position', 100)->nullable();
            $table->string('signature_phone', 30)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->tinyInteger('current_step')->unsigned()->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
