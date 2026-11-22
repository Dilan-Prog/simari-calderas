<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Servicio técnico (agenda) para la app móvil.
 *
 * Requiere cargadas: customer, serviceType, assignedTechnicians.
 *
 * @mixin \App\Models\TechnicalService
 */
class TechnicalServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customer    = $this->customer;
        $technicians = $this->assignedTechnicians->map(fn ($u) => [
            'id'   => $u->id,
            'name' => trim($u->full_name),
        ])->values();

        return [
            'id'                  => $this->id,
            // No hay columna "título": se arma como en el calendario del panel
            // (tipo de servicio), con el folio como respaldo.
            'title'               => $this->serviceType?->name ?: ($this->service_number ?: 'Servicio'),
            'service_number'      => $this->service_number,
            'status'              => $this->status,
            'status_label'        => $this->status_label,
            'priority'            => $this->priority ?: 'normal',
            'priority_label'      => $this->priority_label,
            'is_customer_request' => $this->is_customer_request,
            'scheduled_at'        => $this->scheduledAt(),
            'estimated_duration'  => $this->estimated_duration !== null ? (float) $this->estimated_duration : null,
            'customer'            => $customer ? [
                'id'      => $customer->id,
                'name'    => trim("{$customer->first_name} {$customer->last_name}") ?: null,
                'company' => $customer->company ?: null,
            ] : null,
            // Un servicio puede tener varios técnicos asignados: `technician` es
            // el primero (para listas compactas) y `technicians` la lista completa.
            'technician'          => $technicians->first(),
            'technicians'         => $technicians->all(),
            'address'             => $this->address ?: null,
            'reference'           => $this->reference ?: null,
            'description'         => $this->description ?: null,
        ];
    }

    /** service_date + service_time (zona horaria de la app) en ISO 8601. */
    protected function scheduledAt(): ?string
    {
        if (! $this->service_date) {
            return null;
        }

        $date = $this->service_date->format('Y-m-d');
        $time = $this->service_time ? substr((string) $this->service_time, 0, 8) : '00:00:00';

        return Carbon::parse("{$date} {$time}", config('app.timezone'))->toIso8601String();
    }
}
