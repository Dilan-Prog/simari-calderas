<?php

namespace App\Services;

use App\Models\Pickup;
use App\Models\PickupEvidence;
use App\Models\PickupStatusLog;
use App\Models\SalesOrder;
use App\Services\Erp\DocumentFlow;
use App\Support\PickupStatus;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Recolecciones: folio REC-AAAA-NNNN, bitácora de estatus, evidencia
 * (fotos) y firma (base64, mismo patrón que Entrega de Material).
 */
class PickupService
{
    use ImageUploadTrait;

    public function generateNumber(): string
    {
        $year = now()->year;
        $last = Pickup::where('number', 'like', "REC-{$year}-%")->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('REC-%d-%04d', $year, $seq);
    }

    public function create(array $data, array $items, ?int $userId): Pickup
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $pickup = Pickup::create(array_merge($data, [
                'number' => $this->generateNumber(),
                'status' => 'programada',
                'created_by_user_id' => $userId,
            ]));

            $this->syncItems($pickup, $items);
            $this->log($pickup, null, 'programada', 'Recolección programada', $userId);

            return $pickup;
        });
    }

    public function update(Pickup $pickup, array $data, array $items): Pickup
    {
        return DB::transaction(function () use ($pickup, $data, $items) {
            $pickup->update($data);
            $this->syncItems($pickup, $items);

            return $pickup;
        });
    }

    /** Reemplaza las partidas y enlaza en el Document Flow los pedidos referenciados. */
    private function syncItems(Pickup $pickup, array $items): void
    {
        $pickup->items()->delete();
        foreach ($items as $item) {
            if (blank($item['description'] ?? null)) {
                continue;
            }
            $pickup->items()->create([
                'description' => $item['description'],
                'product_id' => $item['product_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? null,
                'sales_order_id' => $item['sales_order_id'] ?? null,
            ]);
            if (! empty($item['sales_order_id']) && ($order = SalesOrder::find($item['sales_order_id']))) {
                DocumentFlow::link($order, $pickup);
            }
        }
    }

    public function changeStatus(Pickup $pickup, string $to, ?string $note, ?int $userId): Pickup
    {
        if (! in_array($to, PickupStatus::allowedTransitions($pickup->status), true)) {
            throw new InvalidArgumentException("Transición no permitida: {$pickup->status} → {$to}.");
        }
        if ($to === 'incidencia' && blank($note)) {
            throw new InvalidArgumentException('Describe la incidencia en la nota.');
        }

        return DB::transaction(function () use ($pickup, $to, $note, $userId) {
            $from = $pickup->status;
            $pickup->update([
                'status' => $to,
                'incident_note' => $to === 'incidencia' ? $note : $pickup->incident_note,
            ]);
            $this->log($pickup, $from, $to, $note, $userId);

            return $pickup;
        });
    }

    public function addEvidence(Pickup $pickup, UploadedFile $file, ?string $caption, ?int $userId): ?PickupEvidence
    {
        $paths = $this->uploadImages([$file], "pickups/{$pickup->id}", width: 1600, quality: 88);
        if (! $paths) {
            return null;
        }

        return $pickup->evidences()->create([
            'path' => $paths[0],
            'caption' => $caption,
            'uploaded_by_user_id' => $userId,
        ]);
    }

    public function saveSignature(Pickup $pickup, string $dataUrl, ?string $signedBy): void
    {
        if (! preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $dataUrl) || strlen($dataUrl) > 1_500_000) {
            throw new InvalidArgumentException('Firma inválida.');
        }

        $pickup->update([
            'signature_data' => $dataUrl,
            'signed_by_name' => $signedBy,
            'signed_at' => now(),
        ]);
    }

    private function log(Pickup $pickup, ?string $from, string $to, ?string $note, ?int $userId): void
    {
        PickupStatusLog::create([
            'pickup_id' => $pickup->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'changed_by_user_id' => $userId,
        ]);
    }
}
