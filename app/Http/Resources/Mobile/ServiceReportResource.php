<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceReportType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reporte de servicio — forma de LISTADO para la app móvil.
 * ServiceReportDetailResource extiende esta forma con el detalle completo.
 *
 * @mixin \App\Models\ServiceReport
 */
class ServiceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'folio'            => $this->report_number,
            'status'           => $this->status,
            'status_label'     => $this->status_label,
            'service_date'     => $this->service_date?->format('Y-m-d'),
            'customer'         => $this->customerPayload(),
            'technician'       => $this->assignedUser ? [
                'id'   => $this->assignedUser->id,
                'name' => trim($this->assignedUser->full_name),
            ] : null,
            'service_type'     => $this->serviceTypeLabel($request),
            'service_type_key' => $this->service_type,
            'is_signed'        => $this->status === 'signed',
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Cliente del reporte. Igual que el panel, se prefieren los datos capturados
     * en el reporte (snapshot customer_name / customer_company) y se completa
     * con el cliente vinculado si existe.
     */
    protected function customerPayload(): ?array
    {
        $customer = $this->relationLoaded('customer') ? $this->customer : null;

        if (! $this->customer_id && ! $this->customer_name && ! $this->customer_company) {
            return null;
        }

        $linkedName = $customer ? trim("{$customer->first_name} {$customer->last_name}") : null;

        return [
            'id'      => $this->customer_id,
            'name'    => $this->customer_name ?: ($linkedName ?: null),
            'company' => $this->customer_company ?: ($customer?->company ?: null),
        ];
    }

    /**
     * Label del tipo de servicio. El mapa key => label de ServiceReportType se
     * cachea en los atributos de la request para no repetir la consulta por
     * cada fila del listado (el accessor service_type_label hace una por fila).
     */
    protected function serviceTypeLabel(Request $request): ?string
    {
        if ($this->service_type === null || $this->service_type === '') {
            return null;
        }

        $labels = $request->attributes->get('mobile.report_type_labels');
        if ($labels === null) {
            $labels = ServiceReportType::query()->pluck('label', 'key')->all();
            $request->attributes->set('mobile.report_type_labels', $labels);
        }

        return $labels[$this->service_type] ?? $this->service_type;
    }
}
