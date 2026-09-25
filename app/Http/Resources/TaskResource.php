<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 3 (API N8N) — CRM. Expone los campos de $fillable de Task
 * (app/Models/Task.php) tal cual, incluido taskable_type como FQCN crudo
 * (mismo valor que ya devuelve Task::taskable_type en BD y que usa
 * Task::taskableUrl()/taskableLabel() -- no hay morph map en el proyecto).
 */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'taskable_type'          => $this->taskable_type,
            'taskable_id'            => $this->taskable_id,
            'assigned_to'            => $this->assigned_to,
            'title'                  => $this->title,
            'description'            => $this->description,
            'due_at'                 => $this->due_at,
            'status'                 => $this->status,
            'created_by_workflow_id' => $this->created_by_workflow_id,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
