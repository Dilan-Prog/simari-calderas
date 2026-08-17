<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'taskable_type',
        'taskable_id',
        'assigned_to',
        'title',
        'description',
        'due_at',
        'status',
        'created_by_workflow_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByWorkflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'created_by_workflow_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Etiqueta legible del registro relacionado (taskable), para listar
     * tareas sin que la vista tenga que conocer cada tipo de modelo posible.
     */
    public function taskableLabel(): ?string
    {
        if (!$this->taskable) {
            return null;
        }

        return match ($this->taskable_type) {
            \App\Models\Quote::class => 'Cotización ' . $this->taskable->quote_number,
            \App\Models\Deal::class  => 'Negocio ' . $this->taskable->name,
            \App\Models\Cart::class  => 'Carrito #' . $this->taskable->id,
            default                  => class_basename($this->taskable_type) . ' #' . $this->taskable->getKey(),
        };
    }

    /**
     * URL al registro relacionado en el admin, si existe una ruta conocida
     * para ese tipo -- null si no hay a dónde enlazar (o si el registro
     * relacionado fue borrado).
     */
    public function taskableUrl(): ?string
    {
        if (!$this->taskable) {
            return null;
        }

        return match ($this->taskable_type) {
            \App\Models\Quote::class => route('admin.quotes.show', $this->taskable_id),
            \App\Models\Deal::class  => route('admin.deals.show', $this->taskable_id),
            default                  => null,
        };
    }
}
