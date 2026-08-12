<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->cascadeOnDelete();
            $table->foreignId('email_sequence_step_id')->nullable()->constrained('email_sequence_steps')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('tracking_token')->unique();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('bounced_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sends');
    }
};
