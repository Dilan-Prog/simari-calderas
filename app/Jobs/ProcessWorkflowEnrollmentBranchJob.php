<?php

namespace App\Jobs;

use App\Models\WorkflowEnrollmentBranch;
use App\Services\WorkflowEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Equivalente de ProcessWorkflowEnrollmentJob para una rama de un
 * 'parallel' (WorkflowEnrollmentBranch) en estado 'waiting'.
 */
class ProcessWorkflowEnrollmentBranchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly WorkflowEnrollmentBranch $branch) {}

    public function handle(): void
    {
        if ($this->branch->status !== 'waiting') {
            return;
        }

        app(WorkflowEngineService::class)->resumeWaitingBranch($this->branch);
    }
}
