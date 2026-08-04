<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function generateQuoteNumber(): string
    {
        $year = now()->year;
        $last = Quote::where('quote_number', 'like', "COT-{$year}-%")
            ->orderByDesc('quote_number')
            ->value('quote_number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        }

        return sprintf('COT-%d-%04d', $year, $seq);
    }

    public function store(array $data, int $userId): Quote
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'];
            $totals = $this->calculateTotals(
                $items,
                (float) ($data['discount_total'] ?? 0),
                (float) ($data['tax_rate'] ?? 16),
                (float) ($data['isr_retention_rate'] ?? 0)
            );

            $quote = Quote::create([
                'quote_number'      => $this->generateQuoteNumber(),
                'created_by_user_id'=> $userId,
                'customer_id'       => $data['customer_id'],
                'status'            => 'draft',
                'guest_name'        => $data['guest_name'],
                'guest_email'       => $data['guest_email'] ?? null,
                'guest_phone'       => $data['guest_phone'] ?? null,
                'guest_company'     => $data['guest_company'] ?? null,
                'guest_rfc'         => $data['guest_rfc'] ?? null,
                'currency'          => $data['currency'] ?? 'MXN',
                // Se guarda literal lo que trae el request — campo editable
                // de verdad en cada guardado, no un snapshot que se congela.
                'exchange_rate'     => $data['exchange_rate'] ?? null,
                'subtotal'          => $totals['subtotal'],
                'discount_total'    => $totals['discount_total'],
                'tax_rate'          => $data['tax_rate'] ?? 16.00,
                'tax_total'         => $totals['tax_total'],
                'isr_retention_rate'  => $data['isr_retention_rate'] ?? 0,
                'isr_retention_total' => $totals['isr_retention_total'],
                'total'             => $totals['total'],
                'valid_until'       => $data['valid_until'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'terms_conditions'  => $data['terms_conditions'] ?? null,
            ]);

            foreach ($items as $index => $item) {
                QuoteItem::create([
                    'quote_id'         => $quote->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'service_page_id'  => $item['service_page_id'] ?? null,
                    'product_name'     => $item['product_name'],
                    'product_sku'      => $item['product_sku'] ?? null,
                    'quantity'         => (int) $item['quantity'],
                    'unit_price'       => (float) $item['unit_price'],
                    'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                    'tax_percent'      => (float) ($item['tax_percent'] ?? $data['tax_rate'] ?? 16),
                    'line_total'       => (float) $item['line_total'],
                    'notes'            => $item['notes'] ?? null,
                    'sort_order'       => $index,
                ]);
            }

            return $quote;
        });
    }

    public function update(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data) {
            $items = $data['items'];
            $totals = $this->calculateTotals(
                $items,
                (float) ($data['discount_total'] ?? 0),
                (float) ($data['tax_rate'] ?? 16),
                (float) ($data['isr_retention_rate'] ?? 0)
            );

            $quote->update([
                'customer_id'      => $data['customer_id'] ?? $quote->customer_id,
                'guest_name'       => $data['guest_name'],
                'guest_email'      => $data['guest_email'] ?? null,
                'guest_phone'      => $data['guest_phone'] ?? null,
                'guest_company'    => $data['guest_company'] ?? null,
                'guest_rfc'        => $data['guest_rfc'] ?? null,
                'currency'         => $data['currency'] ?? 'MXN',
                // Igual que en store(): se guarda literal lo que trae el
                // request en ese momento, sin recalcular ni comparar contra
                // el valor anterior.
                'exchange_rate'    => $data['exchange_rate'] ?? null,
                'subtotal'         => $totals['subtotal'],
                'discount_total'   => $totals['discount_total'],
                'tax_rate'         => $data['tax_rate'] ?? 16.00,
                'tax_total'        => $totals['tax_total'],
                'isr_retention_rate'  => $data['isr_retention_rate'] ?? 0,
                'isr_retention_total' => $totals['isr_retention_total'],
                'total'            => $totals['total'],
                'valid_until'      => $data['valid_until'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
            ]);

            $quote->items()->delete();

            foreach ($items as $index => $item) {
                QuoteItem::create([
                    'quote_id'         => $quote->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'service_page_id'  => $item['service_page_id'] ?? null,
                    'product_name'     => $item['product_name'],
                    'product_sku'      => $item['product_sku'] ?? null,
                    'quantity'         => (int) $item['quantity'],
                    'unit_price'       => (float) $item['unit_price'],
                    'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                    'tax_percent'      => (float) ($item['tax_percent'] ?? $data['tax_rate'] ?? 16),
                    'line_total'       => (float) $item['line_total'],
                    'notes'            => $item['notes'] ?? null,
                    'sort_order'       => $index,
                ]);
            }

            return $quote->fresh('items');
        });
    }

    /**
     * isr_retention_rate only applies when the quote's customer is "persona
     * moral" (enforced client-side by disabling the field for persona
     * física) — a moral entity paying a persona física for services must
     * withhold ISR from the total. Calculated on the same taxable base as
     * IVA (subtotal - discount), then subtracted from the total, matching
     * how a real Mexican invoice with retention is paid out.
     */
    public function calculateTotals(array $items, float $discountTotal, float $taxRate, float $isrRetentionRate = 0): array
    {
        $subtotal = array_sum(array_column($items, 'line_total'));
        $taxableBase = $subtotal - $discountTotal;
        $taxTotal = round($taxableBase * ($taxRate / 100), 2);
        $isrRetentionTotal = round($taxableBase * ($isrRetentionRate / 100), 2);
        $total = $taxableBase + $taxTotal - $isrRetentionTotal;

        return [
            'subtotal'            => round($subtotal, 2),
            'discount_total'      => round($discountTotal, 2),
            'tax_total'           => $taxTotal,
            'isr_retention_total' => $isrRetentionTotal,
            'total'               => round($total, 2),
        ];
    }

    /**
     * Al aceptar una cotización: las líneas con product_id (bien físico)
     * generan automáticamente un Pedido. Las líneas de servicio (o libres,
     * sin product_id ni service_page_id) no generan nada aquí — siguen el
     * botón manual "Generar Servicio" de siempre, sin cambios.
     */
    public function processAcceptance(Quote $quote): ?SalesOrder
    {
        $quote->loadMissing('items.product');
        $productItems = $quote->items->filter(fn ($i) => $i->product_id !== null);

        if ($productItems->isEmpty()) {
            return null;
        }

        return app(SalesOrderService::class)->createFromQuoteItems($quote, $productItems);
    }
}
