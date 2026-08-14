<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowEditSnapshot;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

/**
 * Motor de deshacer/rehacer para el árbol de WorkflowStep de un Workflow.
 *
 * Cada snapshot guarda el estado (steps + campos del workflow) TAL COMO
 * ESTABA justo ANTES de que se aplique la acción que lo generó -- ya sea
 * una edición normal (capture(), llamado antes de aplicar el cambio) o el
 * primer undo() de una racha (que además captura el estado EN VIVO justo
 * antes de retroceder, para que redo() pueda volver a él).
 *
 * workflow.current_snapshot_id es NULL cuando estamos "en vivo" (en la
 * punta de la historia, editando normalmente); apunta a un snapshot cuando
 * estamos posicionados en algún punto intermedio del historial tras un
 * undo()/redo(). undo() retrocede al snapshot inmediatamente anterior;
 * redo() avanza al inmediatamente posterior. Cualquier edición normal
 * (capture()) hecha desde un punto intermedio descarta todo lo que quedó
 * por delante (incluida la marca de posición actual) y vuelve a "en vivo".
 */
class WorkflowSnapshotService
{
    private const MAX_SNAPSHOTS_PER_WORKFLOW = 50;

    /**
     * Arma el payload (steps + campos del workflow) que representa el
     * estado EN VIVO actual, listo para guardarse en un snapshot.
     */
    private function buildSnapshotPayload(Workflow $workflow): array
    {
        $stepsSnapshot = $workflow->allSteps()->get([
            'id', 'parent_step_id', 'branch_key', 'joins_into_step_id', 'order', 'step_type',
            'action_type', 'action_config', 'branch_condition',
            'position_x', 'position_y',
        ])->toArray();

        $workflowSnapshot = $workflow->only([
            'name', 'type', 'enrollment_trigger', 'reenrollment_allowed',
        ]);

        return [$stepsSnapshot, $workflowSnapshot];
    }

    /**
     * Inserta un snapshot crudo con el estado en vivo actual, sin tocar
     * current_snapshot_id ni podar la rama de "rehacer". Uso interno de
     * capture() y de undo() (para poder volver con redo() al estado del
     * que se está deshaciendo).
     */
    private function snapshotLiveState(Workflow $workflow): WorkflowEditSnapshot
    {
        [$stepsSnapshot, $workflowSnapshot] = $this->buildSnapshotPayload($workflow);

        $snapshot = WorkflowEditSnapshot::create([
            'workflow_id'        => $workflow->id,
            'steps_snapshot'     => $stepsSnapshot,
            'workflow_snapshot'  => $workflowSnapshot,
            'created_by'         => auth()->id(),
        ]);

        // Poda: conserva solo los últimos 50 snapshots de este workflow.
        $total = WorkflowEditSnapshot::where('workflow_id', $workflow->id)->count();
        if ($total > self::MAX_SNAPSHOTS_PER_WORKFLOW) {
            $idsToKeep = WorkflowEditSnapshot::where('workflow_id', $workflow->id)
                ->orderByDesc('id')
                ->limit(self::MAX_SNAPSHOTS_PER_WORKFLOW)
                ->pluck('id');

            WorkflowEditSnapshot::where('workflow_id', $workflow->id)
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }

        return $snapshot;
    }

    /**
     * Captura el estado ACTUAL del workflow (antes de aplicar la acción que
     * está por venir) como un nuevo snapshot, descartando cualquier rama de
     * "rehacer" -- incluida la posición actual -- que hubiera quedado por
     * delante, y dejando al workflow "en vivo" (current_snapshot_id = null)
     * para que el próximo undo() sepa que debe volver a capturar el estado
     * en vivo antes de retroceder.
     */
    public function capture(Workflow $workflow): WorkflowEditSnapshot
    {
        return DB::transaction(function () use ($workflow) {
            // 1. Descarta la rama de "rehacer": snapshots más nuevos que la
            // posición actual, y la posición misma (ya no es un destino de
            // redo válido: esta nueva edición reemplaza esa rama alterna).
            if ($workflow->current_snapshot_id !== null) {
                WorkflowEditSnapshot::where('workflow_id', $workflow->id)
                    ->where('id', '>=', $workflow->current_snapshot_id)
                    ->delete();
            }

            // 2. Captura el estado actual (antes de la acción que está por venir).
            $snapshot = $this->snapshotLiveState($workflow);

            // 3. Volvemos a "en vivo": este snapshot es historial para
            // undo(), no una posición en la que estemos parados.
            $workflow->current_snapshot_id = null;
            $workflow->save();

            return $snapshot;
        });
    }

    /**
     * Retrocede al snapshot inmediatamente anterior. Si estábamos "en vivo"
     * (current_snapshot_id null), primero captura el estado en vivo actual
     * (sin podar nada) para que redo() pueda volver a él después. No-op si
     * no hay ningún snapshot al que retroceder.
     */
    public function undo(Workflow $workflow): array
    {
        if ($workflow->current_snapshot_id !== null) {
            $target = WorkflowEditSnapshot::where('workflow_id', $workflow->id)
                ->where('id', '<', $workflow->current_snapshot_id)
                ->orderByDesc('id')
                ->first();
        } else {
            $target = WorkflowEditSnapshot::where('workflow_id', $workflow->id)
                ->orderByDesc('id')
                ->first();

            if ($target) {
                $this->snapshotLiveState($workflow);
            }
        }

        if (!$target) {
            return $this->currentState($workflow);
        }

        DB::transaction(function () use ($workflow, $target) {
            $this->reconcileSteps($workflow, $target->steps_snapshot);

            $workflow->fill($target->workflow_snapshot);
            $workflow->current_snapshot_id = $target->id;
            $workflow->save();
        });

        return $this->currentState($workflow);
    }

    /**
     * Avanza al snapshot inmediatamente posterior al actual. No-op si ya
     * estamos "en vivo" (nada por delante) o no hay snapshot actual.
     */
    public function redo(Workflow $workflow): array
    {
        if ($workflow->current_snapshot_id === null) {
            return $this->currentState($workflow);
        }

        $target = WorkflowEditSnapshot::where('workflow_id', $workflow->id)
            ->where('id', '>', $workflow->current_snapshot_id)
            ->orderBy('id')
            ->first();

        if (!$target) {
            return $this->currentState($workflow);
        }

        // Si no hay ningún snapshot más nuevo que el destino, este es el
        // último punto de la historia -- redo() nos devuelve al "en vivo"
        // real (current_snapshot_id = null), no a una posición fija. Este es
        // el mismo snapshot que undo() capturó del estado en vivo antes de
        // retroceder por primera vez, así que aplicar su contenido y volver
        // a null preserva el invariante documentado arriba de la clase.
        $isNewest = !WorkflowEditSnapshot::where('workflow_id', $workflow->id)
            ->where('id', '>', $target->id)
            ->exists();

        DB::transaction(function () use ($workflow, $target, $isNewest) {
            $this->reconcileSteps($workflow, $target->steps_snapshot);

            $workflow->fill($target->workflow_snapshot);
            $workflow->current_snapshot_id = $isNewest ? null : $target->id;
            $workflow->save();
        });

        return $this->currentState($workflow);
    }

    /**
     * Motor de diff: hace que los WorkflowStep en BD coincidan exactamente
     * con $stepsSnapshot (mismos ids, mismos campos). Borra los pasos que ya
     * no deberían existir, actualiza los que siguen existiendo y reinserta
     * (preservando su id original) los que fueron borrados por una acción
     * posterior al snapshot. Idempotente y transaccional por sí sola.
     */
    public function reconcileSteps(Workflow $workflow, array $stepsSnapshot): void
    {
        DB::transaction(function () use ($workflow, $stepsSnapshot) {
            $targetIds = collect($stepsSnapshot)
                ->pluck('id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->all();

            $currentIds = WorkflowStep::where('workflow_id', $workflow->id)->pluck('id')->all();

            // 3. Borra los pasos que no existían en el snapshot (creados después).
            $idsToDelete = array_diff($currentIds, $targetIds);
            if (!empty($idsToDelete)) {
                WorkflowStep::whereIn('id', $idsToDelete)->delete();
            }

            // 4. Orden topológico multi-pasada (mismo patrón que
            // WorkflowController::duplicate()): primero los que no dependen de
            // nadie más en el propio snapshot, luego sus hijos, etc. -- así no
            // importa el orden del array de entrada.
            $ordered = $this->topologicalSort($stepsSnapshot);

            // 5. Aplica cada fila: actualiza si el id sigue vivo, re-inserta si
            // fue borrado por una acción posterior a este snapshot.
            //
            // joins_into_step_id es una FK a workflow_steps.id que puede
            // apuntar "hacia adelante" (el último step de una rama de
            // 'parallel' apunta a su 'join', que es HERMANO suyo -- no un
            // ancestro -- así que el orden topológico por parent_step_id no
            // garantiza que el join ya exista cuando se inserta/actualiza la
            // rama). Para no violar la FK, esta columna se aplica siempre en
            // null durante este primer pase y se parchea en un segundo pase
            // (paso 6) una vez que todas las filas del snapshot existen.
            $existingIds = WorkflowStep::where('workflow_id', $workflow->id)->pluck('id')->flip();

            foreach ($ordered as $row) {
                $id = (int) $row['id'];

                $fields = [
                    'parent_step_id'   => $row['parent_step_id'] ?? null,
                    'branch_key'       => $row['branch_key'] ?? null,
                    'joins_into_step_id' => null,
                    'order'            => $row['order'],
                    'step_type'        => $row['step_type'],
                    'action_type'      => $row['action_type'] ?? null,
                    'action_config'    => $row['action_config'] ?? null,
                    'branch_condition' => $row['branch_condition'] ?? null,
                    'position_x'       => $row['position_x'] ?? null,
                    'position_y'       => $row['position_y'] ?? null,
                ];

                if ($existingIds->has($id)) {
                    WorkflowStep::where('id', $id)->update($fields);
                } else {
                    DB::table('workflow_steps')->insert(array_merge($fields, [
                        'id'               => $id,
                        'workflow_id'      => $workflow->id,
                        'action_config'    => $fields['action_config'] !== null
                            ? json_encode($fields['action_config'])
                            : null,
                        'branch_condition' => $fields['branch_condition'] !== null
                            ? json_encode($fields['branch_condition'])
                            : null,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]));
                    $existingIds->put($id, true);
                }
            }

            // 6. Segundo pase: ahora que todas las filas del snapshot existen,
            // aplica joins_into_step_id (puede referenciar a cualquier otra
            // fila del mismo snapshot sin importar el orden de inserción).
            foreach ($ordered as $row) {
                $joinsInto = $row['joins_into_step_id'] ?? null;

                if ($joinsInto !== null) {
                    WorkflowStep::where('id', (int) $row['id'])->update([
                        'joins_into_step_id' => (int) $joinsInto,
                    ]);
                }
            }
        });
    }

    public function canUndo(Workflow $workflow): bool
    {
        if ($workflow->current_snapshot_id === null) {
            // En vivo: se puede deshacer si hay algún snapshot histórico al
            // que retroceder.
            return WorkflowEditSnapshot::where('workflow_id', $workflow->id)->exists();
        }

        return WorkflowEditSnapshot::where('workflow_id', $workflow->id)
            ->where('id', '<', $workflow->current_snapshot_id)
            ->exists();
    }

    public function canRedo(Workflow $workflow): bool
    {
        if ($workflow->current_snapshot_id === null) {
            // En vivo: no hay nada "por delante" a lo que avanzar.
            return false;
        }

        return WorkflowEditSnapshot::where('workflow_id', $workflow->id)
            ->where('id', '>', $workflow->current_snapshot_id)
            ->exists();
    }

    /**
     * Ordena las filas de un snapshot para que los padres siempre se
     * procesen antes que sus hijos, sin importar el orden del array de
     * entrada (mismo algoritmo multi-pasada que
     * WorkflowController::duplicate()).
     */
    private function topologicalSort(array $stepsSnapshot): array
    {
        $pending = collect($stepsSnapshot)->keyBy('id');
        $resolved = [];
        $resolvedIds = [];

        while ($pending->isNotEmpty()) {
            $progressed = false;

            foreach ($pending as $id => $row) {
                $parentId = $row['parent_step_id'] ?? null;
                $canResolve = $parentId === null || array_key_exists($parentId, $resolvedIds);

                if (!$canResolve) {
                    continue;
                }

                $resolved[] = $row;
                $resolvedIds[$id] = true;
                $pending->forget($id);
                $progressed = true;
            }

            if (!$progressed) {
                // Dato corrupto (ciclo o padre huérfano no incluido en el
                // snapshot) -- evita loop infinito, agrega lo que queda tal cual.
                foreach ($pending as $row) {
                    $resolved[] = $row;
                }
                break;
            }
        }

        return $resolved;
    }

    /**
     * Forma de retorno común para undo()/redo(): estado resultante del
     * workflow tras la operación.
     */
    private function currentState(Workflow $workflow): array
    {
        $workflow->refresh();

        return [
            'workflow'  => $workflow->only(['id', 'name', 'type', 'enrollment_trigger', 'reenrollment_allowed']),
            'steps'     => $workflow->allSteps()->get(),
            'can_undo'  => $this->canUndo($workflow),
            'can_redo'  => $this->canRedo($workflow),
        ];
    }
}
