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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('service_type_id');
            $table->timestamp('requested_at')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
 
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
