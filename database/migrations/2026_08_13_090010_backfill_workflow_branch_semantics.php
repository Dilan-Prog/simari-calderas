<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workflow_steps')
            ->where('step_type', 'condition')
            ->get()
            ->each(function ($condition) {
                $children = DB::table('workflow_steps')
                    ->where('parent_step_id', $condition->id)
                    ->orderBy('order')
                    ->get();

                $yesChild = $children->first(function ($child) {
                    return ! empty($child->branch_condition);
                });

                if ($yesChild) {
                    DB::table('workflow_steps')
                        ->where('id', $condition->id)
                        ->update(['branch_condition' => $yesChild->branch_condition]);
                } else {
                    $yesChild = $children->first();
                }

                foreach ($children as $child) {
                    DB::table('workflow_steps')
                        ->where('id', $child->id)
                        ->update([
                            'branch_key' => $yesChild && $child->id === $yesChild->id ? 'yes' : 'no',
                            'branch_condition' => null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Best-effort: the previous per-child branch_condition state cannot be
        // reliably reconstructed (it was collapsed onto the parent condition
        // step during up()), so we simply clear branch_key on all steps.
        DB::table('workflow_steps')->update(['branch_key' => null]);
    }
};
