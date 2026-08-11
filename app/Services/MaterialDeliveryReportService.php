<?php

namespace App\Services;

use App\Models\MaterialDeliveryReport;
use App\Models\MaterialDeliveryReportImage;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Mirror de ServiceReportService pero para entregas de material. Documento
 * PURAMENTE informativo: a propósito NUNCA escribe en
 * SalesOrderItem.quantity_delivered ni llama a InventoryService/
 * applyMovement(). Ese acumulado operativo (el que descuenta stock) sigue
 * siendo exclusivo de SalesOrderService::registerDelivery(), disparado desde
 * la pantalla de Pedidos existente. Este reporte y ese flujo son dos ledgers
 * independientes por diseño — el operador captura la cantidad entregada dos
 * veces (una ahí, para que el sistema descuente inventario; otra aquí, solo
 * para dejar evidencia firmada del evento).
 */
class MaterialDeliveryReportService
{
    use ImageUploadTrait;

    public function generateReportNumber(): string
    {
        $year = now()->year;
        $last = MaterialDeliveryReport::where('report_number', 'like', "REM-{$year}-%")
            ->orderByDesc('report_number')
            ->value('report_number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        }

        return sprintf('REM-%d-%04d', $year, $seq);
    }

    public function store(array $data, int $userId): MaterialDeliveryReport
    {
        return DB::transaction(function () use ($data, $userId) {
            // customer_id se deriva SIEMPRE del pedido real, nunca del array
            // $data directamente — así no se puede falsear desde el request.
            $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);

            return MaterialDeliveryReport::create([
                'report_number'      => $this->generateReportNumber(),
                'created_by_user_id' => $userId,
                'sales_order_id'     => $salesOrder->id,
                'customer_id'        => $salesOrder->customer_id,
                'status'             => 'draft',
                'delivery_date'      => $data['delivery_date'] ?? now()->toDateString(),
                'delivery_location'  => $data['delivery_location'] ?? null,
                'current_step'       => 1,
            ]);
        });
    }

    public function updateStep(MaterialDeliveryReport $report, array $data, int $step): MaterialDeliveryReport
    {
        return DB::transaction(function () use ($report, $data, $step) {
            $this->applyStepData($report, $data, $step);

            // Advance current_step only when moving forward
            if ($step >= $report->current_step) {
                $report->current_step = $step;
            }

            $report->save();

            return $report->fresh();
        });
    }

    private function applyStepData(MaterialDeliveryReport $report, array $data, int $step): void
    {
        match($step) {
            1 => $report->fill([
                    'delivery_date'     => $data['delivery_date'],
                    'delivery_location' => $data['delivery_location'] ?? null,
                ]),
            2 => $this->saveDeliveryItems($report, $data['items'] ?? []),
            3 => $report->fill([
                    'observations' => $data['observations'] ?? null,
                ]),
            4 => $this->saveImages($report, $data['images'] ?? []),
            default => null,
        };
    }

    /**
     * Patrón delete+recreate (igual que ServiceReportService::saveMeasurements()).
     * Por cada línea se snapshotea product_name/product_sku/unit desde el
     * SalesOrderItem real (no del request) y se valida que la cantidad
     * capturada para ESTE evento no exceda el pendiente actual del renglón.
     * Este es solo un guardarraíl suave contra captura absurda — NO evita
     * doble-conteo entre reportes simultáneos, porque este módulo no
     * descuenta quantity_delivered (son ledgers independientes por diseño,
     * ver docblock de la clase).
     */
    public function saveDeliveryItems(MaterialDeliveryReport $report, array $items): void
    {
        $report->items()->delete();

        foreach ($items as $index => $row) {
            $quantity = (int) ($row['quantity_delivered_in_event'] ?? 0);

            // Una línea en 0 significa "no se entrega en este evento" — la
            // vista envía todas las líneas del pedido para mostrar contexto
            // (cant. pedida/pendiente), no solo las que se están entregando.
            if ($quantity <= 0) {
                continue;
            }

            $salesOrderItem = SalesOrderItem::findOrFail($row['sales_order_item_id']);

            if ($quantity > $salesOrderItem->pending) {
                throw new InvalidArgumentException(
                    "La cantidad capturada para \"{$salesOrderItem->product_name}\" ({$quantity}) excede el pendiente por entregar ({$salesOrderItem->pending})."
                );
            }

            $report->items()->create([
                'sales_order_item_id'         => $salesOrderItem->id,
                'product_name'                => $salesOrderItem->product_name,
                'product_sku'                 => $salesOrderItem->product_sku,
                'unit'                        => $salesOrderItem->unit,
                'quantity_delivered_in_event' => $quantity,
                'sort_order'                  => $index,
            ]);
        }
    }

    public function saveImages(MaterialDeliveryReport $report, array $files): int
    {
        $paths = $this->uploadImages($files, "material-delivery-reports/{$report->id}", width: 1800, quality: 92);

        $lastSort = $report->images()->max('sort_order') ?? -1;

        foreach ($paths as $i => $path) {
            MaterialDeliveryReportImage::create([
                'material_delivery_report_id' => $report->id,
                'path'                        => $path,
                'sort_order'                  => $lastSort + $i + 1,
            ]);
        }

        return count($files) - count($paths);
    }

    public function deleteReportImage(MaterialDeliveryReportImage $image): void
    {
        $this->deleteImage($image->path);
        $image->delete();
    }

    public function saveSignature(MaterialDeliveryReport $report, array $signatureData): void
    {
        $report->update([
            'signature_data'        => $signatureData['signature_data'],
            'received_by_name'      => $signatureData['received_by_name'],
            'received_by_position'  => $signatureData['received_by_position'] ?? null,
            'received_by_phone'     => $signatureData['received_by_phone'] ?? null,
            'status'                => 'signed',
            'signed_at'             => now(),
            'current_step'          => 6,
        ]);
    }
}
