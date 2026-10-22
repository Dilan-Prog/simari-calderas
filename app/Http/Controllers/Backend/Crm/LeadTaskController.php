<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Crea una tarea del sistema ligada a un lead, desde la bandeja de leads ("Proxima tarea"). */
class LeadTaskController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'nullable|in:' . implode(',', array_keys(Task::TYPES)),
            'priority'    => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_at'      => 'nullable|date',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $task = Task::create([
            'taskable_type' => Lead::class,
            'taskable_id'   => $lead->id,
            'status'        => 'open',
        ] + $data);
        $task->logEvent('created', 'Tarea creada desde la bandeja de leads', $request->user()?->id);

        $lead->forceFill(['last_activity_at' => now()])->save();

        return redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id])->with('success', 'Tarea creada: ' . $task->title . '.');
    }
}
