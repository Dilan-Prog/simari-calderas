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
        Schema::table('email_sends', function (Blueprint $table) {
            $table->string('attach_source')->nullable()->after('customer_id');
            $table->foreignId('workflow_enrollment_id')->nullable()->after('workflow_step_id')
                ->constrained('workflow_enrollments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_sends', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workflow_enrollment_id');
            $table->dropColumn('attach_source');
        });
    }
};
