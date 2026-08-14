<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWorkflowEnrollmentBranchJob;
use App\Jobs\ProcessWorkflowEnrollmentJob;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentBranch;
use Illuminate\Console\Command;

class TickWaitingWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflows:tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha jobs para reanudar las inscripciones de workflow en estado waiting cuyo resume_at ya venció';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enrollments = WorkflowEnrollment::where('status', 'waiting')
            ->where('resume_at', '<=', now())
            ->get();

        foreach ($enrollments as $enrollment) {
            ProcessWorkflowEnrollmentJob::dispatch($enrollment);
        }

        $branches = WorkflowEnrollmentBranch::where('status', 'waiting')
            ->where('resume_at', '<=', now())
            ->get();

        foreach ($branches as $branch) {
            ProcessWorkflowEnrollmentBranchJob::dispatch($branch);
        }

        return self::SUCCESS;
    }
}
