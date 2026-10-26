<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un lead puede tener varios negocios: deals.lead_id liga cada negocio a su lead.
 * leads.deal_id se CONSERVA como "negocio principal / mas reciente" (se actualiza al crear uno nuevo) para no romper
 * reportes, convertToDeal, markConvertedByDeal ni el KPI / estado del lead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $t) {
            $t->foreignId('lead_id')->nullable()->after('customer_id')->constrained('leads')->nullOnDelete();
        });

        $this->backfill();
    }

    /** Backfill: el negocio que cada lead ya tenia en leads.deal_id (idempotente). */
    public function backfill(): void
    {
        DB::statement('UPDATE deals SET lead_id = (SELECT MAX(leads.id) FROM leads WHERE leads.deal_id = deals.id) WHERE lead_id IS NULL AND EXISTS (SELECT 1 FROM leads WHERE leads.deal_id = deals.id)');
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $t) {
            $t->dropConstrainedForeignId('lead_id');
        });
    }
};
