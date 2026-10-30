<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo de terminos y condiciones de cotizacion (varias opciones que se marcan al cotizar).
 * quotes.terms_selection guarda {"term_ids":[..],"custom":".."}; quotes.terms_conditions sigue siendo el
 * texto final compuesto que usa el PDF. El Setting 'erp.quote_terms_default' NO se borra: se migra a un termino.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_terms', function (Blueprint $t) {
            $t->id();
            $t->string('title', 150);
            $t->text('body');
            $t->string('applies_to', 10)->default('both'); // product | service | both
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::table('quotes', function (Blueprint $t) {
            $t->json('terms_selection')->nullable()->after('terms_conditions');
        });

        $this->backfill();
    }

    /** Pasa el texto unico existente a un termino "Terminos generales" (idempotente). */
    public function backfill(): void
    {
        if (DB::table('quote_terms')->exists()) {
            return;
        }
        $text = trim((string) DB::table('settings')->where('key', 'erp.quote_terms_default')->value('value'));
        if ($text === '') {
            return;
        }
        DB::table('quote_terms')->insert([
            'title' => 'Términos generales', 'body' => $text, 'applies_to' => 'both',
            'is_default' => true, 'is_active' => true, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('quotes', fn (Blueprint $t) => $t->dropColumn('terms_selection'));
        Schema::dropIfExists('quote_terms');
    }
};
