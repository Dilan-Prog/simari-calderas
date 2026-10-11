<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->string('type', 20)->default('pedidos'); // pedidos | material | otro
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->string('time_window', 60)->nullable();
            $table->unsignedBigInteger('driver_user_id')->nullable();
            $table->unsignedBigInteger('carrier_id')->nullable();
            $table->string('status', 20)->default('programada');
            $table->text('notes')->nullable();
            $table->text('incident_note')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signed_by_name', 160)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_date');
            $table->index('driver_user_id');
            $table->index('carrier_id');
        });

        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained('pickups')->cascadeOnDelete();
            $table->string('description', 255);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 30)->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pickup_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained('pickups')->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('caption', 255)->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pickup_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained('pickups')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_status_logs');
        Schema::dropIfExists('pickup_evidences');
        Schema::dropIfExists('pickup_items');
        Schema::dropIfExists('pickups');
    }
};
