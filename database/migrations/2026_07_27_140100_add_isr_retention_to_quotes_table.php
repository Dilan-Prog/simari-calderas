<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Only applies when the quote's customer is "persona moral" — a
            // moral entity paying a persona física for services must
            // withhold ISR from the invoice total (LISR Art. 106, typically
            // 10%). Editable per quote rather than a fixed global rate.
            $table->decimal('isr_retention_rate', 5, 2)->default(0)->after('tax_total');
            $table->decimal('isr_retention_total', 12, 2)->default(0)->after('isr_retention_rate');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['isr_retention_rate', 'isr_retention_total']);
        });
    }
};
