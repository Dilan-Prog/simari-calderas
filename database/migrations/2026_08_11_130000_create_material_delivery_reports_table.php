<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_delivery_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 20)->unique();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('delivery_date');
            $table->string('delivery_location', 200)->nullable();
            $table->text('observations')->nullable();
            $table->enum('status', ['draft', 'signed'])->default('draft');
            $table->longText('signature_data')->nullable();
            $table->string('received_by_name', 150)->nullable();
            $table->string('received_by_position', 100)->nullable();
            $table->string('received_by_phone', 30)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->tinyInteger('current_step')->unsigned()->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_delivery_reports');
    }
};
