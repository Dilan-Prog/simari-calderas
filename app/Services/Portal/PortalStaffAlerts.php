<?php

namespace App\Services\Portal;

use App\Mail\PortalStaffAlertMail;
use App\Models\Customer;
use App\Models\CustomerPortalUser;
use App\Models\CustomerPurchaseOrder;
use App\Models\Task;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Avisos al personal por eventos del portal de clientes: cada uno crea una tarea ligada al cliente
 * (con aviso en la campana del panel vía Task::created) y envía un correo a config('portal.alert_email').
 *
 *  - purchaseOrderUploaded(): el cliente subió una orden de compra (una alerta por subida).
 *  - outOfRangeAnalysis():    el registro diario de análisis químico tiene valores fuera de rango
 *                             (una alerta por cliente y fecha; guardar otra vez el mismo día no duplica).
 *
 * Nunca lanza: un fallo (BD, transporte de correo) se reporta con report() y la acción del cliente sigue.
 */
class PortalStaffAlerts
{
    /** Tope del PDF adjunto al correo (el portal ya limita la subida a 10 MB). */
    public const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024;

    public function purchaseOrderUploaded(CustomerPurchaseOrder $po): void
    {
        try {
            $po->loadMissing(['customer', 'quote']);
            $customer = $po->customer;
            if (! $customer) {
                return;
            }

            $who      = $this->customerLabel($customer);
            $folio    = $po->po_number ? trim($po->po_number) : null;
            $ficha    = route('admin.clients.information', $customer->id) . '#hist-po';
            $download = route('admin.customer-purchase-orders.download', $po->id);
            $quote    = $po->quote;
            $quoteTxt = $quote
                ? $quote->quote_number . ' (' . number_format((float) $quote->total, 2) . ' ' . $quote->currency . ')'
                : null;
            $uploader = $po->uploaded_by_member_id
                ? CustomerPortalUser::where('customer_id', $customer->id)->find($po->uploaded_by_member_id)?->full_name
                : null;

            $details = array_filter([
                'Cliente'              => trim($customer->first_name . ' ' . $customer->last_name),
                'Empresa'              => $customer->company,
                'Correo del cliente'   => $customer->email,
                'Teléfono'             => $customer->phone,
                'Folio de la OC'       => $folio,
                'Fecha de la OC'       => $po->po_date?->format('d/m/Y'),
                'Cotización ligada'    => $quoteTxt,
                'Notas del cliente'    => $po->notes,
                'Archivo'              => $po->original_name . ' (' . $po->sizeLabel() . ')',
                'Subido por'           => $uploader,
                'Recibida'             => now()->format('d/m/Y H:i'),
            ], fn ($v) => $v !== null && $v !== '');

            $title = Str::limit('Orden de compra recibida: ' . $who . ($folio ? ' ' . $folio : ''), 250, '');

            $this->createTask(
                $customer,
                $title,
                "El cliente subió una orden de compra en el portal. Confirma la recepción al cliente por correo.\n\n"
                . $this->plain($details)
                . "\n\nFicha del cliente: {$ficha}\nDescargar PDF: {$download}",
                'correo',
                'media',
                null
            );

            $this->sendMail(new PortalStaffAlertMail(
                '[Portal Equiterm] Nueva orden de compra de ' . $who . ($folio ? ' (' . $folio . ')' : ''),
                'Orden de compra recibida',
                $who . ' subió una orden de compra en el portal de clientes. Confirma la recepción al cliente por correo.',
                $details,
                ['Ver ficha del cliente' => $ficha, 'Descargar PDF' => $download],
                [],
                $this->attachmentFor($po)
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Si el registro tiene valores bajos/altos contra los límites vigentes y aún no hay alerta de ese
     * cliente y fecha, crea tarea + correo. Devuelve true solo cuando se generó una alerta nueva.
     */
    public function outOfRangeAnalysis(WaterAnalysisReport $report): bool
    {
        try {
            $items = $this->outOfRangeItems($report);
            if ($items === []) {
                return false;
            }

            $customer = $report->customer;
            if (! $customer) {
                return false;
            }

            $date = $report->report_date->toDateString();
            $ref  = "water-analysis:{$customer->id}:{$date}";

            // Una alerta por cliente y fecha (incluso si la tarea ya se cerró o eliminó).
            if (Task::withTrashed()->where('related_reference', $ref)->exists()) {
                return false;
            }

            $who      = $this->customerLabel($customer);
            $dateTxt  = $report->report_date->format('d/m/Y');
            $ficha    = route('admin.clients.information', $customer->id) . '#water-analysis';
            $lines    = array_map(fn (array $i) => $i['text'], $items);

            $title = Str::limit("Lectura fuera de rango: {$who} ({$dateTxt})", 250, '');

            $task = $this->createTask(
                $customer,
                $title,
                "El cliente registró una lectura de análisis químico del agua fuera de rango el {$dateTxt}:\n\n- "
                . implode("\n- ", $lines)
                . ($report->notes ? "\n\nObservaciones del cliente: {$report->notes}" : '')
                . "\n\nFicha del cliente: {$ficha}",
                null,
                'alta',
                $ref
            );
            if (! $task) {
                return false;
            }

            $this->sendMail(new PortalStaffAlertMail(
                "[Portal Equiterm] Lectura fuera de rango: {$who} ({$dateTxt})",
                'Lectura de agua fuera de rango',
                "{$who} registró una lectura del análisis químico del agua del {$dateTxt} con valores fuera de rango.",
                array_filter([
                    'Cliente'            => trim($customer->first_name . ' ' . $customer->last_name),
                    'Empresa'            => $customer->company,
                    'Correo del cliente' => $customer->email,
                    'Teléfono'           => $customer->phone,
                    'Fecha de la lectura' => $dateTxt,
                    'Observaciones'      => $report->notes,
                ], fn ($v) => $v !== null && $v !== ''),
                ['Ver ficha del cliente' => $ficha],
                array_merge(['Valores fuera de rango:'], array_map(fn ($l) => '- ' . $l, $lines))
            ));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Resultados del registro que están bajos/altos contra los límites vigentes de parámetros activos.
     *
     * @return array<int, array{code: string, status: string, text: string}>
     */
    public function outOfRangeItems(WaterAnalysisReport $report): array
    {
        $report->loadMissing('results.parameter');

        $items = [];
        foreach ($report->results as $result) {
            $p = $result->parameter;
            if (! $p || ! $p->is_active) {
                continue;
            }
            $status = $p->statusFor($result->value);
            if (! in_array($status, [WaterAnalysisParameter::STATUS_LOW, WaterAnalysisParameter::STATUS_HIGH], true)) {
                continue;
            }

            $unit = $p->unit ? ' ' . $p->unit : '';
            $min  = $p->min_value !== null ? $p->formatValue($p->min_value) : null;
            $max  = $p->max_value !== null ? $p->formatValue($p->max_value) : null;
            $range = match (true) {
                $min !== null && $max !== null => "{$min} a {$max}{$unit}",
                $min !== null                  => "mínimo {$min}{$unit}",
                default                        => "máximo {$max}{$unit}",
            };

            $items[] = [
                'code'   => $p->code,
                'status' => $status,
                'text'   => "{$p->name}: {$p->formatValue($result->value)}{$unit} ("
                    . ($status === WaterAnalysisParameter::STATUS_LOW ? 'bajo' : 'alto') . "; rango permitido {$range})",
            ];
        }

        return $items;
    }

    // ── Internos ────────────────────────────────────────────────────────────

    /** Tarea del sistema ligada al cliente; null si no se pudo crear (ya reportado). */
    private function createTask(Customer $customer, string $title, string $description, ?string $type, string $priority, ?string $reference): ?Task
    {
        try {
            $task = Task::create([
                'taskable_type'     => Customer::class,
                'taskable_id'       => $customer->id,
                'assigned_to'       => null,
                'title'             => $title,
                'description'       => $description,
                'due_at'            => now()->addDay(),
                'status'            => 'open',
                'type'              => $type,
                'priority'          => $priority,
                'related_reference' => $reference,
            ]);

            // Task::created solo avisa al panel cuando no hay sesión; las peticiones del portal van autenticadas
            // en el guard `customer` (el middleware lo vuelve el guard por defecto), así que se avisa aquí.
            // Es idempotente: la clave 'created' evita duplicar si el hook del modelo ya avisó.
            app(NotificationService::class)->notifyTask($task, 'Tarea nueva', $task->title, null, 'created');

            return $task;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Envía el aviso sin dejar que un fallo del transporte de correo interrumpa al cliente. */
    private function sendMail(PortalStaffAlertMail $mail): void
    {
        try {
            $to = array_values(array_filter(array_map('trim', explode(',', (string) config('portal.alert_email')))));
            if ($to === []) {
                return;
            }

            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{disk: string, path: string, name: string, mime: string}|null */
    private function attachmentFor(CustomerPurchaseOrder $po): ?array
    {
        try {
            $disk = Storage::disk(CustomerPurchaseOrder::DISK);
            if (! $po->file_path || ! $disk->exists($po->file_path) || $disk->size($po->file_path) > self::MAX_ATTACHMENT_BYTES) {
                return null;
            }

            return [
                'disk' => CustomerPurchaseOrder::DISK,
                'path' => $po->file_path,
                'name' => $po->original_name ?: 'orden-de-compra.pdf',
                'mime' => 'application/pdf',
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function customerLabel(Customer $customer): string
    {
        $company = trim((string) $customer->company);

        return $company !== '' ? $company : trim($customer->first_name . ' ' . $customer->last_name);
    }

    /** @param array<string, string> $rows */
    private function plain(array $rows): string
    {
        $out = [];
        foreach ($rows as $label => $value) {
            $out[] = "{$label}: {$value}";
        }

        return implode("\n", $out);
    }
}
