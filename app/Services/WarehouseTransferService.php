<?php

namespace App\Services;

use App\Models\Products;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

/**
 * Traspasos entre almacenes.
 *
 * Decisión sobre `products.stock` (global): se usan DOS movimientos ligados
 * por InventoryService::applyMovement() — `salida` en origen al enviar y
 * `entrada` en destino al recibir (ambos con reference_type =
 * WarehouseTransfer::class). Como applyMovement() también ajusta el stock
 * global, mientras la mercancía va en tránsito `products.stock` baja (la
 * mercancía en camino no está disponible para venta) y al recibir completo
 * vuelve a su valor original: el neto global de un traspaso completo es 0.
 * En una recepción parcial la diferencia queda fuera del stock (faltante) y
 * debe justificarse en la nota de recepción. Cancelar en tránsito revierte la
 * salida con una `entrada` en el origen.
 */
class WarehouseTransferService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /** @param array<int,array{product_id:int,quantity:int}> $lines */
    public function create(int $fromId, int $toId, array $lines, ?string $notes, ?int $userId): WarehouseTransfer
    {
        $this->assertWarehouses($fromId, $toId);
        $lines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($fromId, $toId, $lines, $notes, $userId) {
            $transfer = WarehouseTransfer::create([
                'number' => WarehouseTransfer::generateNumber(),
                'from_warehouse_id' => $fromId,
                'to_warehouse_id' => $toId,
                'status' => 'borrador',
                'notes' => $notes,
                'requested_at' => now(),
                'requested_by_user_id' => $userId,
            ]);
            $transfer->lines()->createMany($lines);

            return $transfer;
        });
    }

    public function updateDraft(WarehouseTransfer $transfer, int $fromId, int $toId, array $lines, ?string $notes): WarehouseTransfer
    {
        $this->assertWarehouses($fromId, $toId);
        $lines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($transfer, $fromId, $toId, $lines, $notes) {
            $transfer = WarehouseTransfer::lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status !== 'borrador') {
                throw new \DomainException('Solo se puede editar un traspaso en borrador.');
            }
            $transfer->update(['from_warehouse_id' => $fromId, 'to_warehouse_id' => $toId, 'notes' => $notes]);
            $transfer->lines()->delete();
            $transfer->lines()->createMany($lines);

            return $transfer;
        });
    }

    /** Envía: valida saldo en origen y genera las salidas. Idempotente si ya está en tránsito. */
    public function ship(WarehouseTransfer $transfer, ?int $userId): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $transfer = WarehouseTransfer::lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status === 'en_transito') {
                return $transfer;
            }
            if ($transfer->status !== 'borrador') {
                throw new \DomainException('Solo se puede enviar un traspaso en borrador.');
            }

            $transfer->load('lines.product');
            if ($transfer->lines->isEmpty()) {
                throw new \DomainException('El traspaso no tiene productos.');
            }

            foreach ($transfer->lines as $line) {
                $available = (int) (WarehouseProductStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $line->product_id)->lockForUpdate()->value('quantity') ?? 0);
                if ($available < $line->quantity) {
                    throw new \DomainException(
                        "Saldo insuficiente en origen para \"{$line->product->name}\": disponible {$available}, solicitado {$line->quantity}."
                    );
                }
            }

            foreach ($transfer->lines as $line) {
                $this->inventory->applyMovement(
                    $transfer->from_warehouse_id, $line->product_id, 'salida', $line->quantity,
                    WarehouseTransfer::class, $transfer->id, "Traspaso {$transfer->number}: salida a tránsito"
                );
            }

            $transfer->update(['status' => 'en_transito', 'shipped_at' => now(), 'shipped_by_user_id' => $userId]);

            return $transfer;
        });
    }

    /**
     * Recibe. $received = [line_id => cantidad recibida]; las líneas omitidas se
     * reciben completas. Si hay diferencias, la nota de recepción es obligatoria.
     */
    public function receive(WarehouseTransfer $transfer, array $received, ?string $receiptNotes, ?int $userId): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer, $received, $receiptNotes, $userId) {
            $transfer = WarehouseTransfer::lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status === 'recibido') {
                return $transfer;
            }
            if ($transfer->status !== 'en_transito') {
                throw new \DomainException('Solo se puede recibir un traspaso en tránsito.');
            }

            $transfer->load('lines');
            $qtys = [];
            $hasDiff = false;
            foreach ($transfer->lines as $line) {
                $qty = array_key_exists($line->id, $received) && $received[$line->id] !== null && $received[$line->id] !== ''
                    ? (int) $received[$line->id] : $line->quantity;
                if ($qty < 0 || $qty > $line->quantity) {
                    throw new \DomainException("La cantidad recibida debe estar entre 0 y {$line->quantity}.");
                }
                $qtys[$line->id] = $qty;
                $hasDiff = $hasDiff || $qty !== $line->quantity;
            }
            if ($hasDiff && trim((string) $receiptNotes) === '') {
                throw new \DomainException('Captura una nota que explique las diferencias de recepción.');
            }

            foreach ($transfer->lines as $line) {
                $qty = $qtys[$line->id];
                $line->update(['received_quantity' => $qty]);
                if ($qty > 0) {
                    $this->inventory->applyMovement(
                        $transfer->to_warehouse_id, $line->product_id, 'entrada', $qty,
                        WarehouseTransfer::class, $transfer->id,
                        "Traspaso {$transfer->number}: recepción" . ($qty !== $line->quantity ? " parcial ({$qty} de {$line->quantity})" : '')
                    );
                }
            }

            $transfer->update([
                'status' => 'recibido', 'received_at' => now(), 'received_by_user_id' => $userId,
                'receipt_notes' => $receiptNotes,
            ]);

            return $transfer;
        });
    }

    /** Cancela. En borrador solo cambia el estatus; en tránsito revierte la salida en el origen. */
    public function cancel(WarehouseTransfer $transfer, ?int $userId): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $transfer = WarehouseTransfer::lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status === 'cancelado') {
                return $transfer;
            }
            if (!in_array($transfer->status, ['borrador', 'en_transito'], true)) {
                throw new \DomainException('Un traspaso recibido ya no se puede cancelar.');
            }

            if ($transfer->status === 'en_transito') {
                foreach ($transfer->lines as $line) {
                    $this->inventory->applyMovement(
                        $transfer->from_warehouse_id, $line->product_id, 'entrada', $line->quantity,
                        WarehouseTransfer::class, $transfer->id, "Traspaso {$transfer->number}: reversa por cancelación"
                    );
                }
            }

            $transfer->update(['status' => 'cancelado', 'cancelled_at' => now(), 'cancelled_by_user_id' => $userId]);

            return $transfer;
        });
    }

    private function assertWarehouses(int $fromId, int $toId): void
    {
        if ($fromId === $toId) {
            throw new \DomainException('El almacén de origen y el de destino deben ser distintos.');
        }
        if (Warehouse::whereIn('id', [$fromId, $toId])->count() !== 2) {
            throw new \DomainException('Almacén no válido.');
        }
    }

    /** Une productos repetidos, valida cantidades positivas y existencia del producto. */
    private function normalizeLines(array $lines): array
    {
        $merged = [];
        foreach ($lines as $l) {
            $pid = (int) ($l['product_id'] ?? 0);
            $qty = (int) ($l['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $merged[$pid] = ($merged[$pid] ?? 0) + $qty;
        }
        if (!$merged) {
            throw new \DomainException('Agrega al menos un producto con cantidad mayor a cero.');
        }
        if (Products::whereIn('id', array_keys($merged))->count() !== count($merged)) {
            throw new \DomainException('Algún producto no existe.');
        }

        return collect($merged)->map(fn ($q, $p) => ['product_id' => $p, 'quantity' => $q])->values()->all();
    }
}
