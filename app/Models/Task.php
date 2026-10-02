<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory;

    /**
     * Mismo criterio que Quote::statusLabel()/SalesOrder::statusLabel() --
     * slug en inglés guardado en BD (compatible con 'open' que ya usan
     * scopeOpen()/el default de la API), etiqueta en español para la UI.
     */
    const STATUSES = [
        'open'        => 'Abierto',
        'pending'     => 'Pendiente',
        'in_progress' => 'En Proceso',
        'in_review'   => 'En Revisión',
        'closed'      => 'Cerrada',
    ];

    public static function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? $status;
    }

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

    /**
     * Datos del cliente de la cotización relacionada, para mostrar "de qué
     * cliente se trata" directo en el detalle de la tarea sin tener que
     * entrar a la cotización -- cae a los datos de invitado (guest_*) si la
     * cotización no tiene un Customer de cuenta ligado.
     */
    public function taskableCustomerInfo(): ?array
    {
        if (!$this->taskable instanceof \App\Models\Quote) {
            return null;
        }

        $quote = $this->taskable;
        $customer = $quote->customer;

        return [
            'name'  => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : $quote->guest_name,
            'phone' => $customer?->phone ?? $quote->guest_phone,
            'email' => $customer?->email ?? $quote->guest_email,
        ];
    }

    /**
     * Resumen para el botón "Vista rápida" del detalle de la tarea -- evita
     * tener que salir a /admin/cotizaciones/{id} solo para ver el estatus o
     * el total.
     */
    public function taskableQuickView(): ?array
    {
        if (!$this->taskable instanceof \App\Models\Quote) {
            return null;
        }

        $quote = $this->taskable;

        return [
            'quote_number' => $quote->quote_number,
            'status_label' => \App\Models\Quote::statusLabel($quote->status),
            'total'        => number_format((float) $quote->total, 2) . ' ' . $quote->currency,
            'valid_until'  => optional($quote->valid_until)->format('d/m/Y'),
            'sent_at'      => optional($quote->sent_at)->format('d/m/Y H:i'),
        ];
    }
}
