<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');

        $query = Task::with(['taskable', 'assignee', 'createdByWorkflow'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tasks = $query->paginate(20)->withQueryString();

        return view('admin.tasks.index', [
            'tasks'  => $tasks,
            'status' => $status,
        ]);
    }
}
