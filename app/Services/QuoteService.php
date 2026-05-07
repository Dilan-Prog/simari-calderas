<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;
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
                (float) ($data['tax_rate'] ?? 16)
            );

            $quote = Quote::create([
                'quote_number'      => $this->generateQuoteNumber(),
                'created_by_user_id'=> $userId,
                'status'            => 'draft',
                'guest_name'        => $data['guest_name'],
                'guest_email'       => $data['guest_email'] ?? null,
                'guest_phone'       => $data['guest_phone'] ?? null,
                'guest_company'     => $data['guest_company'] ?? null,
                'guest_rfc'         => $data['guest_rfc'] ?? null,
                'currency'          => $data['currency'] ?? 'MXN',
                'subtotal'          => $totals['subtotal'],
                'discount_total'    => $totals['discount_total'],
                'tax_rate'          => $data['tax_rate'] ?? 16.00,
                'tax_total'         => $totals['tax_total'],
                'total'             => $totals['total'],
                'valid_until'       => $data['valid_until'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'terms_conditions'  => $data['terms_conditions'] ?? null,
            ]);

            foreach ($items as $index => $item) {
                QuoteItem::create([
                    'quote_id'         => $quote->id,
                    'product_id'       => $item['product_id'] ?? null,
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
                (float) ($data['tax_rate'] ?? 16)
            );

            $quote->update([
                'guest_name'       => $data['guest_name'],
                'guest_email'      => $data['guest_email'] ?? null,
                'guest_phone'      => $data['guest_phone'] ?? null,
                'guest_company'    => $data['guest_company'] ?? null,
                'guest_rfc'        => $data['guest_rfc'] ?? null,
                'currency'         => $data['currency'] ?? 'MXN',
                'subtotal'         => $totals['subtotal'],
                'discount_total'   => $totals['discount_total'],
                'tax_rate'         => $data['tax_rate'] ?? 16.00,
                'tax_total'        => $totals['tax_total'],
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

    public function calculateTotals(array $items, float $discountTotal, float $taxRate): array
    {
        $subtotal = array_sum(array_column($items, 'line_total'));
        $taxableBase = $subtotal - $discountTotal;
        $taxTotal = round($taxableBase * ($taxRate / 100), 2);
        $total = $taxableBase + $taxTotal;

        return [
            'subtotal'       => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total'      => $taxTotal,
            'total'          => round($total, 2),
        ];
    }

    public function convertToOrder(Quote $quote): void
    {
        // Placeholder para fase 2
    }
}
