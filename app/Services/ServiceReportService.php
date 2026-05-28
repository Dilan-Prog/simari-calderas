<?php

namespace App\Services;

use App\Models\ServiceReport;
use App\Models\ServiceReportActivity;
use App\Models\ServiceReportCustomField;
use App\Models\ServiceReportMeasurement;
use Illuminate\Support\Facades\DB;

class ServiceReportService
{
    public function generateReportNumber(): string
    {
        $year = now()->year;
        $last = ServiceReport::where('report_number', 'like', "RPT-{$year}-%")
            ->orderByDesc('report_number')
            ->value('report_number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        }

        return sprintf('RPT-%d-%04d', $year, $seq);
    }

    public function store(array $data, int $userId): ServiceReport
    {
        return DB::transaction(function () use ($data, $userId) {
            return ServiceReport::create([
                'report_number'      => $this->generateReportNumber(),
                'created_by_user_id' => $userId,
                'assigned_user_id'   => $data['assigned_user_id'],
                'customer_id'        => $data['customer_id'] ?? null,
                'customer_name'      => $data['customer_name'],
                'customer_company'   => $data['customer_company'] ?? null,
                'customer_rfc'       => $data['customer_rfc'] ?? null,
                'customer_phone'     => $data['customer_phone'] ?? null,
                'service_type'       => $data['service_type'],
                'custom_service_type'=> $data['custom_service_type'] ?? null,
                'status'             => 'draft',
                'service_date'       => $data['service_date'],
                'week_number'        => $data['week_number'] ?? null,
                'location'           => $data['location'] ?? null,
                'current_step'       => 1,
            ]);
        });
    }

    public function updateStep(ServiceReport $report, array $data, int $step): ServiceReport
    {
        return DB::transaction(function () use ($report, $data, $step) {
            $this->applyStepData($report, $data, $step);

            // Advance current_step only when moving forward
            if ($step >= $report->current_step) {
                $report->current_step = $step;
                // Mark in_progress once we leave step 1
                if ($step >= 1 && $report->status === 'draft' && $step > 1) {
                    $report->status = 'in_progress';
                }
            }

            $report->save();

            return $report->fresh();
        });
    }

    private function applyStepData(ServiceReport $report, array $data, int $step): void
    {
        match($step) {
            1 => $report->fill([
                    'assigned_user_id'   => $data['assigned_user_id'],
                    'customer_id'        => $data['customer_id'] ?? null,
                    'customer_name'      => $data['customer_name'],
                    'customer_company'   => $data['customer_company'] ?? null,
                    'customer_rfc'       => $data['customer_rfc'] ?? null,
                    'customer_phone'     => $data['customer_phone'] ?? null,
                    'service_type'       => $data['service_type'],
                    'custom_service_type'=> $data['custom_service_type'] ?? null,
                    'service_date'       => $data['service_date'],
                    'week_number'        => $data['week_number'] ?? null,
                    'location'           => $data['location'] ?? null,
                ]),
            2 => $this->applyStep2($report, $data),
            3 => $report->fill([
                    'observations'        => $data['observations'] ?? null,
                    'recommendations'     => $data['recommendations'] ?? null,
                    'include_sampling'    => (bool) ($data['include_sampling'] ?? false),
                    'sampling_date_day'   => $data['sampling_date_day'] ?? null,
                    'sampling_date_month' => $data['sampling_date_month'] ?? null,
                    'sampling_date_year'  => $data['sampling_date_year'] ?? null,
                    'analyst_name'        => $data['analyst_name'] ?? null,
                    'analyst_position'    => $data['analyst_position'] ?? null,
                ]),
            default => null,
        };
    }

    private function applyStep2(ServiceReport $report, array $data): void
    {
        if ($report->usesMeasurementsForm()) {
            $this->saveMeasurements($report, $data['measurements'] ?? []);
        } elseif ($report->usesActivityForm()) {
            $this->saveActivity($report, [
                'content'         => $data['activity_content'] ?? '',
                'systems_checked' => $data['systems_checked'] ?? [],
            ]);
        } elseif ($report->usesCustomForm()) {
            $this->saveCustomFields($report, $data['custom_fields'] ?? []);
        }
    }

    public function saveMeasurements(ServiceReport $report, array $measurements): void
    {
        $report->measurements()->delete();

        foreach ($measurements as $index => $row) {
            $report->measurements()->create([
                'parameter'  => $row['parameter'],
                'unit'       => $row['unit'] ?? null,
                'result'     => $row['result'] ?? null,
                'limit_min'  => isset($row['limit_min']) && $row['limit_min'] !== '' ? (float) $row['limit_min'] : null,
                'limit_max'  => isset($row['limit_max']) && $row['limit_max'] !== '' ? (float) $row['limit_max'] : null,
                'in_range'   => $this->calculateInRange(
                                    $row['result'] ?? null,
                                    isset($row['limit_min']) && $row['limit_min'] !== '' ? (float) $row['limit_min'] : null,
                                    isset($row['limit_max']) && $row['limit_max'] !== '' ? (float) $row['limit_max'] : null
                                ),
                'sort_order' => $index,
            ]);
        }
    }

    public function saveActivity(ServiceReport $report, array $data): void
    {
        $report->activity()->delete();

        ServiceReportActivity::create([
            'service_report_id' => $report->id,
            'content'           => $data['content'],
            'systems_checked'   => $data['systems_checked'] ?? null,
        ]);
    }

    public function saveCustomFields(ServiceReport $report, array $fields): void
    {
        $report->customFields()->delete();

        foreach ($fields as $index => $field) {
            ServiceReportCustomField::create([
                'service_report_id' => $report->id,
                'field_name'        => $field['field_name'],
                'field_type'        => $field['field_type'],
                'field_value'       => $field['field_value'] ?? null,
                'sort_order'        => $index,
            ]);
        }
    }

    public function saveSignature(ServiceReport $report, array $signatureData): void
    {
        $report->update([
            'signature_data'     => $signatureData['signature_data'],
            'signature_name'     => $signatureData['signature_name'],
            'signature_position' => $signatureData['signature_position'],
            'signature_phone'    => $signatureData['signature_phone'] ?? null,
            'status'             => 'signed',
            'signed_at'          => now(),
            'current_step'       => 6,
        ]);
    }

    public function calculateInRange(?string $result, ?float $min, ?float $max): ?bool
    {
        if ($result === null || $result === '') {
            return null;
        }

        $numeric = filter_var($result, FILTER_VALIDATE_FLOAT);
        if ($numeric === false) {
            return null;
        }

        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $numeric < $min) {
            return false;
        }

        if ($max !== null && $numeric > $max) {
            return false;
        }

        return true;
    }

    public function getServiceTypes(): array
    {
        return [
            'chemical_analysis'      => 'Análisis Químico',
            'maintenance_preventive' => 'Mantenimiento Preventivo',
            'maintenance_corrective' => 'Mantenimiento Correctivo',
            'inspection'             => 'Inspección',
            'cleaning'               => 'Limpieza',
            'calibration'            => 'Calibración',
            'activity_report'        => 'Reporte de Actividades',
            'custom'                 => 'Personalizado',
        ];
    }

    public function getSystemsChecked(): array
    {
        return [
            'torres_enfriamiento'  => 'Torres de Enfriamiento',
            'sistema_agua_fria'    => 'Sistema Agua Fría',
            'sistema_agua_caliente'=> 'Sistema Agua Caliente',
            'calderas'             => 'Calderas',
            'osmosis_inversa'      => 'Ósmosis Inversa',
            'sistema_potable'      => 'Sistema Agua Potable',
            'sistema_residual'     => 'Sistema Agua Residual',
            'sistema_incendio'     => 'Sistema Contra Incendio',
            'cisterna'             => 'Cisterna / Tinaco',
            'equipo_bombeo'        => 'Equipo de Bombeo',
            'filtros'              => 'Filtros',
            'dosificadores'        => 'Dosificadores',
        ];
    }
}
