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
        Schema::create('email_campaigns_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->unique();
            $table->integer('recipients_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('bounce_count')->default(0);
            $table->integer('unsubscribe_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->decimal('revenue_amount', 12, 2)->default(0);
            $table->timestamp('updated_at')->nullable();
 
            $table->foreign('campaign_id')->references('id')->on('email_campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns_stats');
    }
};
