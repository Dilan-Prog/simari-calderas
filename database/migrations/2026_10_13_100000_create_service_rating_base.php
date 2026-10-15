<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base común de "Califica este servicio":
 *  - service_page_id en cotizaciones, servicios técnicos y reportes (el "Servicio ID": el servicio web dado de alta).
 *  - survey_cards: tarjetas de calificación (1 a 5 estrellas) configurables.
 *  - survey_questions: variables de la encuesta de satisfacción configurables (Limpieza, Ordenado…).
 *  - service_ratings: una calificación por reporte de servicio firmado.
 *  - service_rating_answers: respuesta (1-5) a cada variable de la encuesta.
 *  - service_page_reviews: enlace opcional a la calificación de origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['quotes', 'services', 'service_reports'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedBigInteger('service_page_id')->nullable();
                $t->foreign('service_page_id', $table . '_service_page_fk')->references('id')->on('service_pages')->nullOnDelete();
                $t->index('service_page_id', $table . '_service_page_idx');
            });
        }

        Schema::create('survey_cards', function (Blueprint $t) {
            $t->id();
            $t->string('label', 60);
            $t->string('emoji', 16)->nullable();
            $t->unsignedTinyInteger('stars');            // 1..5 que representa la tarjeta
            $t->string('color', 9)->default('#f97316');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('survey_questions', function (Blueprint $t) {
            $t->id();
            $t->string('label', 120);                   // "Limpieza", "Resolvió mi problema"…
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('service_ratings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('service_report_id')->unique();
            $t->unsignedBigInteger('service_page_id')->nullable()->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->string('customer_name', 255);            // snapshot: viene del reporte/servicio, no editable por el cliente
            $t->unsignedTinyInteger('rating')->nullable(); // 1..5
            $t->unsignedBigInteger('survey_card_id')->nullable();
            $t->string('card_label', 60)->nullable();    // snapshot del texto de la tarjeta elegida
            $t->text('comment')->nullable();
            $t->string('status', 20)->default('pending'); // pending | submitted | skipped
            $t->timestamp('submitted_at')->nullable();
            $t->unsignedBigInteger('service_page_review_id')->nullable()->index(); // reseña pública derivada (si se publica)
            $t->timestamps();

            $t->foreign('service_report_id')->references('id')->on('service_reports')->cascadeOnDelete();
            $t->foreign('service_page_id')->references('id')->on('service_pages')->nullOnDelete();
            $t->foreign('survey_card_id')->references('id')->on('survey_cards')->nullOnDelete();
        });

        Schema::create('service_rating_answers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('service_rating_id');
            $t->unsignedBigInteger('survey_question_id')->nullable();
            $t->string('question_label', 120);           // snapshot
            $t->unsignedTinyInteger('score');            // 1..5
            $t->timestamps();

            $t->foreign('service_rating_id')->references('id')->on('service_ratings')->cascadeOnDelete();
            $t->foreign('survey_question_id')->references('id')->on('survey_questions')->nullOnDelete();
        });

        Schema::table('service_page_reviews', function (Blueprint $t) {
            $t->unsignedBigInteger('service_rating_id')->nullable()->index();
            $t->string('source', 20)->default('manual'); // manual | client
        });
    }

    public function down(): void
    {
        Schema::table('service_page_reviews', fn (Blueprint $t) => $t->dropColumn(['service_rating_id', 'source']));
        Schema::dropIfExists('service_rating_answers');
        Schema::dropIfExists('service_ratings');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('survey_cards');
        foreach (['quotes', 'services', 'service_reports'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign($table . '_service_page_fk');
                $t->dropIndex($table . '_service_page_idx');
                $t->dropColumn('service_page_id');
            });
        }
    }
};
