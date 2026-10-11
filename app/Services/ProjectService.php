<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Services\Erp\DocumentFlow;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /** Crea el proyecto con folio y sus fases. */
    public function create(array $data, array $phases = [], ?int $userId = null): Project
    {
        return DB::transaction(function () use ($data, $phases, $userId) {
            $project = Project::create(array_merge($this->projectAttributes($data), [
                'project_number'     => Project::generateProjectNumber(),
                'status'             => $data['status'] ?? 'levantamiento',
                'created_by_user_id' => $userId,
            ]));

            $this->syncPhases($project, $phases);

            return $project;
        });
    }

    public function update(Project $project, array $data, ?array $phases = null): Project
    {
        return DB::transaction(function () use ($project, $data, $phases) {
            $attrs = $this->projectAttributes($data);
            if (!empty($data['status'])) {
                $attrs['status'] = $data['status'];
            }
            $project->update($attrs);

            if ($phases !== null) {
                $this->syncPhases($project, $phases);
            }

            return $project->fresh();
        });
    }

    public function changeStatus(Project $project, string $status): Project
    {
        $project->update(['status' => $status]);

        return $project;
    }

    private function projectAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'description', 'customer_id', 'company', 'manager_user_id',
            'estimated_duration_days', 'estimated_time', 'start_date', 'due_date', 'notes',
        ]));
    }

    /**
     * Reemplazo ordenado de las fases (orden = posición en el arreglo).
     * Cada fase: name, description, requirements, status, progress, start_date, end_date, assignees[].
     */
    public function syncPhases(Project $project, array $phases): void
    {
        $project->phases()->get()->each->delete();

        foreach (array_values($phases) as $i => $p) {
            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $status = $p['status'] ?? 'pendiente';
            if (!isset(ProjectPhase::STATUSES[$status])) {
                $status = 'pendiente';
            }
            $progress = max(0, min(100, (int) ($p['progress'] ?? 0)));
            if ($status === 'completada') {
                $progress = 100;
            }

            $phase = $project->phases()->create([
                'name'         => $name,
                'description'  => $p['description'] ?? null,
                'requirements' => $p['requirements'] ?? null,
                'sort_order'   => $i,
                'status'       => $status,
                'progress'     => $progress,
                'start_date'   => $p['start_date'] ?? null ?: null,
                'end_date'     => $p['end_date'] ?? null ?: null,
            ]);

            $userIds = collect($p['assignees'] ?? [])->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
            if ($userIds) {
                $phase->assignees()->sync($userIds);
            }
        }
    }

    /**
     * Avance global (0-100) ponderado por la duración de cada fase en días
     * (fases sin fechas pesan 1 día).
     */
    public function progress(Project $project): int
    {
        $phases = $project->relationLoaded('phases') ? $project->phases : $project->phases()->get();
        if ($phases->isEmpty()) {
            return 0;
        }

        $totalWeight = 0;
        $done = 0.0;
        foreach ($phases as $ph) {
            $w = ($ph->start_date && $ph->end_date) ? max(1, $ph->start_date->diffInDays($ph->end_date) + 1) : 1;
            $totalWeight += $w;
            $done += $w * ($ph->status === 'completada' ? 100 : (int) $ph->progress);
        }

        return (int) round($done / max(1, $totalWeight));
    }

    /** Asocia (o desasocia con null) un documento existente al proyecto. */
    public function attach(Project $project, $document): void
    {
        $document->forceFill(['project_id' => $project->id])->save();
        $this->syncDocumentFlow($project);
    }

    public function detach(Project $project, $document): void
    {
        if ((int) $document->project_id !== (int) $project->id) {
            return;
        }
        $document->forceFill(['project_id' => null])->save();
        $this->unlinkFlow($project, $document);
    }

    /** Enlaza en el flujo del documento: cotización→proyecto, proyecto→pedidos, proyecto→servicios (y cotización→pedido). */
    public function syncDocumentFlow(Project $project): void
    {
        foreach ($project->quotes()->get() as $quote) {
            DocumentFlow::link($quote, $project);
        }
        foreach ($project->salesOrders()->with('quote')->get() as $order) {
            DocumentFlow::link($project, $order);
            if ($order->quote) {
                DocumentFlow::link($order->quote, $order);
            }
        }
        foreach ($project->technicalServices()->get() as $service) {
            DocumentFlow::link($project, $service);
        }
    }

    private function unlinkFlow(Project $project, $document): void
    {
        $dt = DocumentFlow::typeOf($document);
        if (!$dt || !$document->getKey()) {
            return;
        }
        \App\Models\DocumentLink::where(function ($q) use ($dt, $document) {
            $q->where('from_type', 'project')->where('to_type', $dt)->where('to_id', $document->getKey());
        })->orWhere(function ($q) use ($dt, $document) {
            $q->where('to_type', 'project')->where('from_type', $dt)->where('from_id', $document->getKey());
        })->get()->filter(fn ($l) => (int) ($l->from_type === 'project' ? $l->from_id : $l->to_id) === (int) $project->id)
            ->each->delete();
    }
}
