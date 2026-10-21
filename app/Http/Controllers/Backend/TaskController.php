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

        // Historial: movimientos registrados + correo recibido + (si no hay registro) la creación derivada.
        $events = $task->events()->with(['actor', 'outboundEmail'])->get();
        $timeline = $events->map(function ($e) {
            $mail = $e->outboundEmail;

            return [
                'title' => $e->title,
                'actor' => $mail ? ($mail->from_email ?: 'Correo enviado') : ($e->actor?->full_name ?? 'Sistema'),
                'at'    => $mail?->sent_at ?? $e->created_at,
                'body'  => null,
                // Correo enviado al cliente (se puede abrir desde el historial).
                'email' => $mail ? [
                    'subject'     => $mail->subject,
                    'from'        => $mail->from_email,
                    'to'          => $mail->recipients ?: array_filter([$mail->to_email]),
                    'at'          => $mail->sent_at ?? $mail->created_at,
                    'body'        => $mail->body,
                    'attachments' => $mail->attachments ?: [],
                ] : null,
            ];
        });
        if (! $events->contains('type', 'created')) {
            $timeline->push(['title' => 'Tarea creada · ' . $origin, 'actor' => 'Sistema', 'at' => $task->created_at, 'body' => null]);
        }
        if ($email) {
            $timeline->push([
                'title' => 'Correo recibido' . ($email->subject ? ': ' . $email->subject : ''),
                'actor' => $email->from_name ?: $email->from_email,
                'at'    => $email->received_at ?? $email->created_at,
                'body'  => $email->excerpt,
            ]);
        }
        if ($task->closed_at && ! $events->contains(fn ($e) => $e->type === 'status' && str_contains($e->title, 'Cerrada'))) {
            $timeline->push(['title' => 'Tarea cerrada', 'actor' => 'Sistema', 'at' => $task->closed_at, 'body' => null]);
        }

        return view('admin.tasks.show', [
            'task'     => $task,
            'origin'   => $origin,
            'email'    => $email,
            'timeline' => $timeline->sortByDesc('at')->values()->all(),
            'notes'    => $task->notes()->with('author')->get(),
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
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'related_reference' => 'nullable|string|max:255',
            'type'              => 'nullable|in:' . implode(',', array_keys(Task::TYPES)),
            'priority'          => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_at'            => 'nullable|date',
            'status'            => 'nullable|in:' . implode(',', array_keys(Task::STATUSES)),
            'assigned_to'       => 'required|exists:users,id',
        ]);
        $data['status'] = $data['status'] ?? 'open';

        // Si la referencia es el folio de una cotización, la tarea queda ligada a ella.
        $ref = trim((string) ($data['related_reference'] ?? ''));
        if ($ref !== '' && ($quote = Quote::where('quote_number', $ref)->first())) {
            $data['taskable_type'] = Quote::class;
            $data['taskable_id'] = $quote->id;
        }

        $task = Task::create($data);
        $task->logEvent('created', 'Tarea creada', $request->user()?->id);

        return redirect()->route('admin.tasks.index')->with('success', 'Tarea creada.');
    }

    /**
     * Edición de una tarea. Todos los campos son opcionales en la petición (los botones
     * "Completar", "Reabrir" y "Reasignar" envían solo el suyo). Cada cambio real queda
     * en el historial de la tarea (task_events) con su valor anterior y nuevo, y además
     * en la bitácora del sistema (system_logs) por el trait LogsActivity del modelo.
     */
    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title'             => 'sometimes|required|string|max:255',
            'description'       => 'nullable|string',
            'related_reference' => 'nullable|string|max:255',
            'type'              => 'nullable|in:' . implode(',', array_keys(Task::TYPES)),
            'priority'          => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_at'            => 'nullable|date',
            'status'            => 'sometimes|required|in:' . implode(',', array_keys(Task::STATUSES)),
            'assigned_to'       => 'nullable|exists:users,id',
        ]);

        // La fecha llega como "YYYY-MM-DD HH:mm" (o solo "YYYY-MM-DD" desde la API). Si coincide con la
        // actual al minuto no cuenta como cambio: así se conservan los segundos de las tareas automáticas.
        if (array_key_exists('due_at', $data)) {
            $newDue = $data['due_at'] ? \Illuminate\Support\Carbon::parse($data['due_at']) : null;
            $dateOnly = $newDue && strlen(trim((string) $data['due_at'])) <= 10;
            $fmt = $dateOnly ? 'Y-m-d' : 'Y-m-d H:i';
            if (($newDue?->format($fmt)) === ($task->due_at?->format($fmt))) {
                unset($data['due_at']);
            } else {
                $data['due_at'] = $newDue;
            }
        }
        foreach (['description', 'related_reference', 'type', 'priority'] as $f) {
            if (array_key_exists($f, $data) && $data[$f] === '') {
                $data[$f] = null;
            }
        }

        // Registro de cuándo se cerró -- solo se fija la primera vez (mismo
        // criterio que Quote::accepted_at/rejected_at); si se reabre y se
        // vuelve a cerrar después, no se pisa la fecha original.
        if (($data['status'] ?? null) === 'closed' && ! $task->closed_at) {
            $data['closed_at'] = now();
        }

        $before = $task->only(['title', 'description', 'related_reference', 'type', 'priority', 'status', 'assigned_to']);
        $beforeDue = $task->due_at;

        $task->update($data);

        $uid = $request->user()?->id;
        $dash = fn ($v) => ($v === null || $v === '') ? '—' : $v;
        $label = fn (array $map, $v) => $v ? ($map[$v] ?? $v) : null;
        $changed = fn (string $f) => array_key_exists($f, $data) && ($before[$f] ?? null) != ($task->{$f});

        if ($changed('title')) {
            $task->logEvent('edited', 'Título cambiado: «' . $before['title'] . '» → «' . $task->title . '»', $uid);
        }
        if ($changed('related_reference')) {
            $task->logEvent('edited', 'Relacionado con cambiado: «' . $dash($before['related_reference']) . '» → «' . $dash($task->related_reference) . '»', $uid);
        }
        if ($changed('type')) {
            $task->logEvent('edited', 'Tipo cambiado: ' . $dash($label(Task::TYPES, $before['type'])) . ' → ' . $dash($label(Task::TYPES, $task->type)), $uid);
        }
        if ($changed('priority')) {
            $task->logEvent('edited', 'Prioridad cambiada: ' . $dash($label(Task::PRIORITIES, $before['priority'])) . ' → ' . $dash($label(Task::PRIORITIES, $task->priority)), $uid);
        }
        if (array_key_exists('due_at', $data)) {
            $task->logEvent('edited', 'Fecha límite cambiada: ' . ($beforeDue?->format('d/m/Y H:i') ?? '—') . ' → ' . ($task->due_at?->format('d/m/Y H:i') ?? '—'), $uid);
        }
        if ($changed('description')) {
            $task->logEvent('edited', 'Descripción editada', $uid);
        }
        if ($changed('status')) {
            $task->logEvent('status', 'Estatus actualizado a «' . Task::statusLabel($task->status) . '»', $uid);
        }
        if (array_key_exists('assigned_to', $data) && (int) $before['assigned_to'] !== (int) $task->assigned_to) {
            $task->logEvent('assigned', 'Responsable cambiado a ' . ($task->assignee?->full_name ?? 'Sin asignar'), $uid);
        }

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
        $task->logEvent('deleted', 'Tarea eliminada', $request->user()->id);
        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'Tarea eliminada.');
    }

    /** Nota interna de una tarea. */
    public function storeNote(Request $request, Task $task)
    {
        $data = $request->validate(['body' => 'required|string|max:2000']);

        $task->notes()->create(['body' => trim($data['body']), 'user_id' => $request->user()?->id]);
        $task->logEvent('note', 'Nota agregada', $request->user()?->id);

        return redirect(route('admin.tasks.show', $task) . '#notas')->with('success', 'Nota agregada.');
    }
}
