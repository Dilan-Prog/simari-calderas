<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');

        $query = Task::with([
            'taskable' => fn (MorphTo $morphTo) => $morphTo->morphWith([Quote::class => ['customer']]),
            'assignee',
            'createdByWorkflow',
        ])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tasks = $query->paginate(20)->withQueryString();

        return view('admin.tasks.index', [
            'tasks'  => $tasks,
            'status' => $status,
            'users'  => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Tareas manuales creadas desde el panel -- a diferencia de
     * Api\Crm\TaskController::store() (usado por N8N), aquí taskable_type/id
     * son opcionales: una tarea manual no necesita estar ligada a un
     * registro (ver migración make_taskable_nullable_on_tasks_table).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at'      => 'nullable|date',
            'status'      => 'required|in:' . implode(',', array_keys(Task::STATUSES)),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        Task::create($data);

        return redirect()->route('admin.tasks.index')->with('success', 'Tarea creada.');
    }

    /**
     * A petición del usuario: título y fecha límite se fijan al crear la
     * tarea y no se pueden cambiar después -- solo descripción/estado/
     * asignación son editables. Se ignoran aquí aunque el form los mande
     * (defensa real en el controller, no solo el campo "readonly" del HTML).
     */
    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'description' => 'nullable|string',
            'status'      => 'required|in:' . implode(',', array_keys(Task::STATUSES)),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task->update($data);

        return redirect()->route('admin.tasks.index')->with('success', 'Tarea actualizada.');
    }
}
