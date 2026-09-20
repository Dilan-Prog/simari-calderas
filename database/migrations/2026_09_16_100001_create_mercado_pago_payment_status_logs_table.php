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
        Schema::create('mercado_pago_payment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercado_pago_payment_id')->constrained('mercado_pago_payments')->cascadeOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->string('source', 20); // 'checkout'|'api_confirm'|'webhook'|'admin'
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_payment_status_logs');
    }
};
