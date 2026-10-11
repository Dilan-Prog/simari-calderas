<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_conversion_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('legacy_conversion_id')->nullable()->constrained('google_conversions')->nullOnDelete();
            $table->foreignId('parent_upload_id')->nullable()->constrained('google_conversion_uploads')->nullOnDelete();

            $table->string('action', 40);                      // cotizado | venta_ganada | (nombre legado)
            $table->string('operation', 15)->default('upload'); // upload | restatement | retraction
            $table->string('order_id', 120)->index();           // id del negocio + acción
            // Clave anti-duplicados: solo las subidas "upload" la llevan (los ajustes la dejan null).
            $table->string('dedupe_key', 160)->nullable()->unique();

            $table->string('identifier_type', 20)->default('none'); // gclid|gbraid|wbraid|user_data|none
            $table->string('identifier')->nullable();               // valor de gclid/gbraid/wbraid
            $table->json('user_data')->nullable();                  // {email_sha256, phone_sha256}

            $table->dateTime('conversion_date_time');               // UTC
            $table->string('timezone', 40)->default('America/Mexico_City');
            $table->decimal('conversion_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('MXN');
            $table->dateTime('click_at')->nullable();
            $table->dateTime('gclid_expires_at')->nullable();

            $table->string('status', 20)->default('pendiente'); // pendiente|enviada|fallida|omitida|sin_atribucion
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('response')->nullable();
            $table->dateTime('next_attempt_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('alerted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_conversion_uploads');
    }
};
