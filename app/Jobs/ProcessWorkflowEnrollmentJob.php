<?php

namespace App\Jobs;

use App\Models\WorkflowEnrollment;
use App\Services\WorkflowEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWorkflowEnrollmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly WorkflowEnrollment $enrollment) {}

    public function handle(): void
    {
        $engine = app(WorkflowEngineService::class);

        if ($this->enrollment->status === 'waiting') {
            $engine->resumeWaiting($this->enrollment);

            return;
        }

        if ($this->enrollment->status === 'active') {
            $engine->processStep($this->enrollment);
        }
    }
}
