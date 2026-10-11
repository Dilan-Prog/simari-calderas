<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->default('manual'); // whatsapp|formulario|llamada|correo|manual
            $table->string('status', 20)->default('nuevo');  // nuevo|contactado|calificado|descartado|convertido

            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 40)->nullable();      // como llegó
            $table->string('phone_normalized', 20)->nullable()->index(); // E.164 (+52...)
            $table->string('contact_email')->nullable()->index();  // en minúsculas

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('whatsapp_conversation_id')->nullable()->constrained('whatsapp_conversations')->nullOnDelete();
            $table->unsignedBigInteger('pool_calculator_lead_id')->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Atribución (último clic en columnas para filtrar; primer y último clic completos en JSON)
            $table->uuid('visitor_uuid')->nullable()->index();
            $table->string('gclid')->nullable();
            $table->string('gbraid')->nullable();
            $table->string('wbraid')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->dateTime('click_at')->nullable();   // fecha del último clic con identificador
            $table->json('first_click')->nullable();
            $table->json('last_click')->nullable();

            $table->dateTime('first_contact_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'source']);
        });

        Schema::create('lead_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('direction', 10)->default('entrante'); // entrante|saliente
            $table->string('status', 15)->default('contestada');  // contestada|perdida
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->dateTime('started_at');
            $table->string('external_id', 120)->nullable()->unique();
            $table->string('tracking_number', 40)->nullable(); // campo de registro (call tracking externo)
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_calls');
        Schema::dropIfExists('leads');
    }
};
