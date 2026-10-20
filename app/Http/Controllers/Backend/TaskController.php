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
        // Vistas: open (default) | mine | overdue | closed | all. `status=all|open` es el parámetro
        // anterior (enlaces viejos); se respeta.
        $view = $request->get('view', $request->get('status', 'open'));
        $view = in_array($view, ['open', 'mine', 'overdue', 'closed', 'all'], true) ? $view : 'open';
        $q = trim((string) $request->get('q', ''));
        $assignedTo = (string) $request->get('assigned_to', '');
        $userId = $request->user()?->id;

        $query = Task::with([
            'taskable' => fn (MorphTo $morphTo) => $morphTo->morphWith([Quote::class => ['customer', 'items.product']]),
            'assignee',
            'createdByWorkflow',
            'deletedBy',
        ])->latest();

        // "Todas" también incluye las eliminadas (borrado lógico) -- se
        // quedan visibles ahí marcadas "Eliminada por {nombre}" en vez de
        // desaparecer. Cualquier otra vista las excluye (el scope global
        // de SoftDeletes ya hace eso solo).
        match ($view) {
            'all'     => $query->withTrashed(),
            'mine'    => $query->where('assigned_to', $userId)->where('status', '!=', 'closed'),
            'overdue' => $query->whereNotNull('due_at')->where('due_at', '<', now())->where('status', '!=', 'closed'),
            'closed'  => $query->where('status', 'closed'),
            default   => $query->where('status', 'open'),
        };

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
        }
        if ($assignedTo === 'sin') {
            $query->whereNull('assigned_to');
        } elseif (ctype_digit($assignedTo)) {
            $query->where('assigned_to', (int) $assignedTo);
        }

        $tasks = $query->paginate(20)->withQueryString();

        $counts = [
            'open'    => Task::where('status', 'open')->count(),
            'mine'    => Task::where('assigned_to', $userId)->where('status', '!=', 'closed')->count(),
            'overdue' => Task::whereNotNull('due_at')->where('due_at', '<', now())->where('status', '!=', 'closed')->count(),
            'closed'  => Task::where('status', 'closed')->count(),
            'all'     => Task::withTrashed()->count(),
        ];

        // Tareas creadas por el flujo de correos entrantes (n8n): se rotulan "Correo N8N" en Origen.
        $emailTaskIds = \App\Models\LeadInboundEmail::whereIn('task_id', $tasks->pluck('id'))
            ->pluck('task_id')->map(fn ($id) => (int) $id)->all();

        return view('admin.tasks.index', [
            'emailTaskIds' => $emailTaskIds,
            'tasks'  => $tasks,
            'view'   => $view,
            'counts' => $counts,
            'q'      => $q,
            'assignedTo' => $assignedTo,
            'users'  => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    /** Página de detalle (Object Page): datos, relacionado e historial. */
    public function show(Task $task)
    {
        $task->load([
            'taskable' => fn (MorphTo $morphTo) => $morphTo->morphWith([Quote::class => ['customer', 'items.product']]),
            'assignee',
            'createdByWorkflow',
            'deletedBy',
        ]);

        $email = \App\Models\LeadInboundEmail::where('task_id', $task->id)->first();
        $origin = $task->createdByWorkflow?->name ?? ($email ? 'Correo N8N' : 'Manual');

        $timeline = collect([['type' => 'tarea', 'title' => 'Tarea creada · ' . $origin, 'at' => $task->created_at, 'body' => null]]);
        if ($email) {
            $timeline->push([
                'type'  => 'formulario',
                'title' => 'Correo recibido' . ($email->subject ? ': ' . $email->subject : ''),
                'at'    => $email->received_at ?? $email->created_at,
                'body'  => $email->excerpt,
            ]);
        }
        if ($task->closed_at) {
            $timeline->push(['type' => 'tarea', 'title' => 'Tarea cerrada', 'at' => $task->closed_at, 'body' => null]);
        }
        if ($task->trashed()) {
            $timeline->push(['type' => 'tarea', 'title' => 'Eliminada por ' . ($task->deletedBy?->full_name ?? 'usuario eliminado'), 'at' => $task->deleted_at, 'body' => null]);
        }

        return view('admin.tasks.show', [
            'task'     => $task,
            'origin'   => $origin,
            'email'    => $email,
            'timeline' => $timeline->sortBy('at')->values()->all(),
            'lead'     => $task->taskable instanceof \App\Models\Lead ? $task->taskable : null,
            'users'    => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
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

        // Registro de cuándo se cerró -- solo se fija la primera vez (mismo
        // criterio que Quote::accepted_at/rejected_at); si se reabre y se
        // vuelve a cerrar después, no se pisa la fecha original.
        if ($data['status'] === 'closed' && !$task->closed_at) {
            $data['closed_at'] = now();
        }

        $task->update($data);

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Tarea actualizada.');
    }

    /**
     * Borrado lógico únicamente -- nunca se destruye la fila. Se registra
     * quién la eliminó para poder mostrar "Eliminada por {nombre}" en la
     * lista en vez de que la tarea simplemente desaparezca.
     */
    public function destroy(Request $request, Task $task)
    {
        $task->update(['deleted_by' => $request->user()->id]);
        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'Tarea eliminada.');
    }
}
