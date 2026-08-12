<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->string('branch_key', 20)->nullable()->after('parent_step_id');
            $table->index(['parent_step_id', 'branch_key', 'order'], 'workflow_steps_branch_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropIndex('workflow_steps_branch_order_idx');
            $table->dropColumn('branch_key');
        });
    }
};
