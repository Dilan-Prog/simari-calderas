<?php

namespace App\Http\Controllers\Backend;

use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use Illuminate\Http\Request;

class WorkflowExecutionController
{
    public function index(Request $request)
    {
        $enrollments = WorkflowEnrollment::with(['workflow', 'logs'])
            ->when($request->filled('workflow_id'), fn ($q) => $q->where('workflow_id', $request->workflow_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('enrolled_at')
            ->paginate(20)
            ->withQueryString();

        $enrollments->getCollection()->transform(function ($enrollment) {
            $enrollment->has_failure = $enrollment->logs->contains('result', 'failed');
            return $enrollment;
        });

        if ($request->wantsJson()) {
            return response()->json($enrollments);
        }

        $workflowsForFilter = Workflow::orderBy('name')->get(['id', 'name']);

        return view('admin.workflow-executions.index', compact('enrollments', 'workflowsForFilter'));
    }
}
