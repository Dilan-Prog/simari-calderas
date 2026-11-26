<?php

namespace App\Http\Resources\Mobile;

use App\Models\ServiceRating;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Reporte de servicio — forma de DETALLE para la app móvil: los campos del
 * listado más todo lo que muestra la vista web admin.service-reports.show.
 *
 * Requiere cargadas: measurements, activity, customFields, assignedUser,
 * createdBy, customer, images, service, rating.
 *
 * @mixin \App\Models\ServiceReport
 */
class ServiceReportDetailResource extends ServiceReportResource
{
    /** Vigencia de la URL firmada del PDF. */
    public const PDF_URL_TTL_MINUTES = 30;

    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        // Datos de contacto del cliente que también muestra la vista web.
        if ($base['customer'] !== null) {
            $base['customer']['rfc']   = $this->customer_rfc ?: null;
            $base['customer']['phone'] = $this->customer_phone ?: null;
        }

        // Igual que la vista web: cada bloque solo aplica según el
        // form_behavior del tipo de reporte (mediciones / actividades / personalizado).
        $usesMeasurements = $this->usesMeasurementsForm();
        $usesActivity     = $this->usesActivityForm();
        $usesCustom       = $this->usesCustomForm();

        return array_merge($base, [
            'custom_service_type' => $this->custom_service_type ?: null,
            'form_behavior'       => $usesMeasurements ? 'measurements' : ($usesActivity ? 'activity' : ($usesCustom ? 'custom' : null)),
            'week_number'         => $this->week_number,
            'location'            => $this->location ?: null,
            'created_by'          => $this->createdBy ? [
                'id'   => $this->createdBy->id,
                'name' => trim($this->createdBy->full_name),
            ] : null,
            'technical_service'   => $this->service ? [
                'id'             => $this->service->id,
                'service_number' => $this->service->service_number,
            ] : null,
            'observations'        => $this->observations ?: null,
            'recommendations'     => $this->recommendations ?: null,
            'sampling'            => $this->include_sampling ? [
                'date'             => $this->samplingDate(),
                'analyst_name'     => $this->analyst_name ?: null,
                'analyst_position' => $this->analyst_position ?: null,
            ] : null,
            'measurements'        => $usesMeasurements
                ? $this->measurements->map(fn ($m) => [
                    'id'        => $m->id,
                    'parameter' => $m->parameter,
                    'unit'      => $m->unit ?: null,
                    'result'    => $m->result,
                    'limit_min' => $m->limit_min,
                    'limit_max' => $m->limit_max,
                    'in_range'  => $m->in_range,
                ])->values()->all()
                : [],
            'activity'            => $usesActivity && $this->activity ? [
                'content'         => $this->activity->content ?: null,
                'systems_checked' => array_values($this->activity->systems_checked ?? []),
            ] : null,
            'custom_fields'       => $usesCustom
                ? $this->customFields->map(fn ($f) => [
                    'id'    => $f->id,
                    'name'  => $f->field_name,
                    'type'  => $f->field_type,
                    'value' => $f->field_value,
                ])->values()->all()
                : [],
            'signature'           => $this->status === 'signed' && $this->signature_data ? [
                'name'      => $this->signature_name ?: null,
                'position'  => $this->signature_position ?: null,
                'phone'     => $this->signature_phone ?: null,
                'signed_at' => $this->signed_at?->toIso8601String(),
                // Imagen de la firma tal como se guarda (data URI base64).
                'image'     => $this->signature_data,
            ] : null,
            'rating'              => $this->ratingPayload(),
            // Datos de campo capturados en la app (null en reportes del panel / anteriores).
            'equipment'           => $this->fieldDataArray('equipment'),
            'readings'            => $this->fieldDataArray('readings'),
            'materials'           => array_values($this->fieldDataArray('materials') ?? []),
            'origin'              => $this->origin ?: null,
            'captured_at'         => $this->captured_at ? Carbon::parse($this->captured_at)->toIso8601String() : null,
            'images'              => $this->images->map(fn ($img) => [
                'id'      => $img->id,
                'url'     => $img->url,
                'caption' => filled($img->caption ?? null) ? $img->caption : null,
            ])->values()->all(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'pdf_url'             => self::pdfUrl($this->id),
        ]);
    }

    /**
     * URL firmada del PDF. La firma se calcula sobre la ruta RELATIVA
     * (/api/mobile/v1/reportes/{id}/pdf?expires=…) y la ruta valida con
     * `signed:relative`, así que no importa si detrás de un proxy/CDN Laravel
     * ve la petición como http o con otro host: no da 403 por eso. El dominio
     * se toma de APP_URL (https://equitermindustries.com.mx en producción).
     */
    public static function pdfUrl(int $reportId): string
    {
        $relative = URL::temporarySignedRoute(
            'mobile.v1.reportes.pdf',
            now()->addMinutes(self::PDF_URL_TTL_MINUTES),
            ['report' => $reportId],
            false
        );

        return rtrim((string) config('app.url'), '/') . $relative;
    }

    /** Columna JSON de datos de campo como arreglo (tolera que aún no esté casteada); null si está vacía. */
    protected function fieldDataArray(string $attribute): ?array
    {
        $value = $this->{$attribute};

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) && $value !== [] ? $value : null;
    }

    /** Fecha de muestreo (se captura en tres campos sueltos), en formato dd/mm/aaaa. */
    protected function samplingDate(): ?string
    {
        $parts = array_filter([
            $this->sampling_date_day,
            $this->sampling_date_month,
            $this->sampling_date_year,
        ], fn ($v) => $v !== null && $v !== '');

        return $parts ? implode('/', $parts) : null;
    }

    /** Calificación del cliente; solo si ya la envió (mismo criterio que la vista web). */
    protected function ratingPayload(): ?array
    {
        $rating = $this->rating;

        if (! $rating || $rating->status !== ServiceRating::STATUS_SUBMITTED || ! $rating->rating) {
            return null;
        }

        return [
            'rating'       => $rating->rating,
            'card_label'   => $rating->card_label ?: null,
            'comment'      => $rating->comment ?: null,
            'submitted_at' => ($rating->submitted_at ?? $rating->created_at)?->toIso8601String(),
        ];
    }
}
