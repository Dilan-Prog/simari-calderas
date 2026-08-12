<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('pipeline_id')->constrained('pipelines')->restrictOnDelete();
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages')->restrictOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency')->default('MXN');
            $table->date('expected_close_date')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('company_snapshot')->nullable();
            $table->string('contact_snapshot_name')->nullable();
            $table->string('contact_snapshot_email')->nullable();
            $table->string('contact_snapshot_phone')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('open');
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
