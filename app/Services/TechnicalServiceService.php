<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceLog;
use App\Models\ServiceMaterialPlanned;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TechnicalServiceService
{
    // Mapeo de etiquetas de duración a valores decimales
    private const DURATION_MAP = [
        '1 hora'       => 1.0,
        '2 horas'      => 2.0,
        '3 horas'      => 3.0,
        '4 horas'      => 4.0,
        'Medio día'    => 4.5,
        'Día completo' => 8.0,
    ];

    // ── Generación de folio ────────────────────────────────────────────────────

    public function generateServiceNumber(): string
    {
        $year = now()->year;

        $last = TechnicalService::where('service_number', 'like', "SVC-{$year}-%")
            ->orderByDesc('service_number')
            ->value('service_number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        }

        return sprintf('SVC-%d-%04d', $year, $seq);
    }

    // ── Alta inicial (Paso 1) ──────────────────────────────────────────────────

    public function store(array $data, int $userId): TechnicalService
    {
        return DB::transaction(function () use ($data, $userId) {
            $number = $this->generateServiceNumber();

            $service = TechnicalService::create([
                'service_number'     => $number,
                'service_code'       => $number,
                'created_by_user_id' => $userId,
                'customer_id'        => $data['customer_id'],
                'service_type_id'    => $data['service_type_id'],
                'service_date'       => $data['service_date'],
                'service_time'       => $data['service_time'] ?? null,
                'estimated_duration' => $this->parseDuration($data['estimated_duration'] ?? null),
                'priority'           => $data['priority'] ?? 'normal',
                'description'        => $data['description'] ?? null,
                'address'            => $data['address'] ?? null,
                'reference'          => $data['reference'] ?? null,
                'location'           => $data['location'] ?? null,
                'week_number'        => $data['week_number'] ?? null,
                'from_quote_id'      => $data['from_quote_id'] ?? null,
                'status'             => 'draft',
                'current_step'       => 1,
                'requested_at'       => now(),
            ]);

            $this->log($service, 'created', null, $userId);

            return $service;
        });
    }

    // ── Guardar etapa ──────────────────────────────────────────────────────────

    public function saveStep(TechnicalService $service, array $data, int $step): TechnicalService
    {
        return DB::transaction(function () use ($service, $data, $step) {
            match ($step) {
                1 => $this->applyStep1($service, $data),
                2 => $this->syncTechnicians($service, $data['technician_ids'] ?? []),
                3 => $this->saveMaterials($service, $data['materials'] ?? []),
                default => null,
            };

            if ($step >= $service->current_step) {
                $service->current_step = $step;
            }

            $service->save();

            return $service->fresh();
        });
    }

    private function applyStep1(TechnicalService $service, array $data): void
    {
        $service->fill([
            'customer_id'        => $data['customer_id'],
            'service_type_id'    => $data['service_type_id'],
            'service_date'       => $data['service_date'],
            'service_time'       => $data['service_time'] ?? null,
            'estimated_duration' => $this->parseDuration($data['estimated_duration'] ?? null),
            'priority'           => $data['priority'] ?? 'normal',
            'description'        => $data['description'] ?? null,
            'address'            => $data['address'] ?? null,
            'reference'          => $data['reference'] ?? null,
            'location'           => $data['location'] ?? null,
            'week_number'        => $data['week_number'] ?? null,
        ]);
    }

    // ── Técnicos ───────────────────────────────────────────────────────────────

    public function syncTechnicians(TechnicalService $service, array $userIds): void
    {
        // preserve role_verified for technicians that are being re-synced
        $existing = $service->assignedTechnicians()
            ->pluck('role_verified', 'user_id')
            ->toArray();

        $syncData = [];
        foreach (array_filter(array_unique(array_map('intval', $userIds))) as $uid) {
            $syncData[$uid] = ['role_verified' => $existing[$uid] ?? 0];
        }

        $service->assignedTechnicians()->sync($syncData);

        $this->log($service, 'technicians_updated', null, auth()->id());
    }

    // ── Materiales ─────────────────────────────────────────────────────────────

    public function saveMaterials(TechnicalService $service, array $materials): void
    {
        $service->plannedMaterials()->delete();

        $rows = array_values(array_filter(
            $materials,
            fn($row) => !empty($row['product_name'])
        ));

        foreach ($rows as $index => $row) {
            ServiceMaterialPlanned::create([
                'service_id'   => $service->id,
                'product_id'   => $row['product_id'] ?? null,
                'product_name' => $row['product_name'],
                'product_sku'  => $row['product_sku'] ?? null,
                'quantity'     => max(1, (int) ($row['quantity'] ?? 1)),
                'unit'         => in_array($row['unit'] ?? '', ['litros','kg','piezas','metros','galones','otro'])
                                    ? $row['unit']
                                    : 'piezas',
                'notes'        => $row['notes'] ?? null,
                'sort_order'   => $row['sort_order'] ?? $index,
            ]);
        }
    }

    // ── Status ─────────────────────────────────────────────────────────────────

    public function updateStatus(
        TechnicalService $service,
        string $status,
        ?string $comment,
        int $userId
    ): void {
        DB::transaction(function () use ($service, $status, $comment, $userId) {
            $service->update(['status' => $status]);
            $this->log($service, 'status_changed', $comment ?? "Estado cambiado a {$status}", $userId);
        });
    }

    // ── Fecha (drag & drop) ────────────────────────────────────────────────────

    public function updateDate(TechnicalService $service, string $newDate, int $userId): void
    {
        DB::transaction(function () use ($service, $newDate, $userId) {
            $old = $service->service_date ? $service->service_date->format('Y-m-d') : null;
            $service->update(['service_date' => $newDate]);
            $this->log(
                $service,
                'date_changed',
                "Fecha movida de {$old} a {$newDate}",
                $userId
            );
        });
    }

    // ── Eventos del calendario ─────────────────────────────────────────────────

    public function getCalendarEvents(
        int $month,
        int $year,
        ?int $technicianId,
        ?string $status
    ): array {
        $query = TechnicalService::query()
            ->with(['serviceType', 'assignedTechnicians', 'customer'])
            ->whereMonth('service_date', $month)
            ->whereYear('service_date', $year)
            ->whereNotIn('status', ['draft']);

        if ($technicianId) {
            $query->byTechnician($technicianId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query
            ->orderBy('service_date')
            ->orderBy('service_time')
            ->get()
            ->map(function (TechnicalService $service) {
                $customerName = $service->customer?->company
                    ?: trim(($service->customer?->first_name ?? '') . ' ' . ($service->customer?->last_name ?? ''));

                return [
                    'id'                  => $service->id,
                    'service_number'      => $service->service_number,
                    'service_type_label'  => $service->serviceType?->name ?? 'Servicio',
                    'customer_name'       => $customerName ?: '—',
                    'service_date'        => $service->service_date?->format('Y-m-d'),
                    'service_time'        => $service->service_time
                                                ? substr($service->service_time, 0, 5)
                                                : null,
                    'status'              => $service->status,
                    'status_label'        => $service->status_label,
                    'priority'            => $service->priority,
                    'show_url'            => route('admin.technical-services.show', $service),
                    'assigned_technicians'=> $service->assignedTechnicians->map(fn(User $u) => [
                        'id'   => $u->id,
                        'name' => trim("{$u->first_name} {$u->last_name}"),
                    ])->toArray(),
                ];
            })
            ->toArray();
    }

    // ── Búsquedas AJAX ─────────────────────────────────────────────────────────

    public function searchTechnicians(string $query): Collection
    {
        return User::where('status', 'active')
            ->whereHas('role', fn($q) => $q->where('name_role', 'like', '%cnico%'))
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'position')
            ->orderBy('first_name')
            ->take(10)
            ->get()
            ->map(fn(User $u) => [
                'id'       => $u->id,
                'name'     => trim("{$u->first_name} {$u->last_name}"),
                'role'     => $u->position ?? '',
                'initials' => strtoupper(
                    substr($u->first_name, 0, 1) . substr($u->last_name, 0, 1)
                ),
            ]);
    }

    // ── Catálogos ──────────────────────────────────────────────────────────────

    public function getServiceTypes(): array
    {
        return ServiceType::active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getTechnicians(): Collection
    {
        return User::where('status', 'active')
            ->whereHas('role', fn($q) => $q->where('name_role', 'like', '%cnico%'))
            ->orderBy('first_name')
            ->get();
    }

    // ── Helpers privados ───────────────────────────────────────────────────────

    private function parseDuration(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return self::DURATION_MAP[$value] ?? null;
    }

    private function log(
        TechnicalService $service,
        string $action,
        ?string $comment,
        ?int $userId
    ): void {
        ServiceLog::create([
            'service_id'          => $service->id,
            'action'              => $action,
            'comment'             => $comment,
            'performed_by_user_id'=> $userId,
        ]);
    }
}
