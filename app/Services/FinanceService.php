<?php

namespace App\Services;

use App\Models\DocumentLink;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\PaymentApplication;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\SalesOrder;
use App\Services\Erp\DocumentFlow;
use App\Support\UploadPath;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finanzas internas: cuentas por cobrar / por pagar, pagos y su aplicación,
 * vencimientos, antigüedad de saldos y estado de cuenta. Sin contabilidad
 * general ni timbrado CFDI.
 *
 * Convención de moneda: exchange_rate = MXN por 1 unidad de la moneda del
 * documento/pago (1 para MXN). Una aplicación guarda el monto en la moneda del
 * documento (amount_applied) y lo que consume del pago en la moneda del pago
 * (amount_payment = amount_applied * tc_doc / tc_pago).
 */
class FinanceService
{
    public const TOLERANCE = 0.01;

    public const BUCKETS = ['current' => 'Vigente', 'd30' => '1-30', 'd60' => '31-60', 'd90' => '61-90', 'd90p' => '+90'];

    // ── Altas de documentos ────────────────────────────────────────────────

    /** Alta manual de una CXC. */
    public function createReceivable(array $data, ?int $userId = null): Receivable
    {
        $data = $this->normalizeDocumentData($data);

        return DB::transaction(function () use ($data, $userId) {
            $r = new Receivable($data);
            $r->number = Receivable::generateNumber();
            $r->created_by_user_id = $userId;
            $r->paid_amount = 0;
            $r->balance = $data['total'];
            $r->status = 'pendiente';
            $r->status = $r->computeStatus();
            $r->save();

            return $r;
        });
    }

    /** Genera la CXC de un pedido usando los totales de su cotización origen. */
    public function createReceivableFromSalesOrder(SalesOrder $order, ?int $creditDays = null, array $overrides = [], ?int $userId = null): Receivable
    {
        if ($order->status === 'cancelado') {
            throw new DomainException('El pedido está cancelado; no se puede generar una cuenta por cobrar.');
        }
        $exists = Receivable::where('source_type', 'sales_order')->where('source_id', $order->id)
            ->where('status', '!=', 'cancelada')->first();
        if ($exists) {
            throw new DomainException("El pedido ya tiene la cuenta por cobrar {$exists->number}.");
        }

        $quote = $order->quote;
        $base = [];
        if ($quote instanceof Quote) {
            $base = [
                'currency'      => $quote->currency ?: 'MXN',
                'exchange_rate' => $quote->exchange_rate ?: 1,
                'subtotal'      => (float) $quote->subtotal - (float) ($quote->discount_total ?? 0),
                'tax_total'     => (float) $quote->tax_total,
                'total'         => (float) $quote->total,
            ];
        }
        $data = array_merge($base, array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
        if (empty($data['total']) || (float) $data['total'] <= 0) {
            throw new DomainException('El pedido no tiene cotización origen con monto; captura la cuenta por cobrar manualmente.');
        }

        $data['customer_id'] = $order->customer_id;
        $data['source_type'] = 'sales_order';
        $data['source_id']   = $order->id;
        $data['credit_days'] = $creditDays ?? ($overrides['credit_days'] ?? 0);
        $data['notes']       = $overrides['notes'] ?? "Generada desde el pedido {$order->order_number}";

        $receivable = $this->createReceivable($data, $userId);
        DocumentFlow::link($order, $receivable);

        return $receivable;
    }

    /** Alta manual de una CXP. */
    public function createPayable(array $data, ?int $userId = null): Payable
    {
        $data = $this->normalizeDocumentData($data);

        return DB::transaction(function () use ($data, $userId) {
            $p = new Payable($data);
            $p->number = Payable::generateNumber();
            $p->created_by_user_id = $userId;
            $p->paid_amount = 0;
            $p->balance = $data['total'];
            $p->status = 'pendiente';
            $p->status = $p->computeStatus();
            $p->save();

            return $p;
        });
    }

    /** Genera la CXP de una orden de compra aceptada, enlazada a la OC. */
    public function createPayableFromPurchaseOrder(PurchaseOrder $po, ?int $creditDays = null, array $overrides = [], ?int $userId = null): Payable
    {
        if (!in_array($po->status, ['accepted', 'received'], true)) {
            throw new DomainException('Solo se puede generar una cuenta por pagar de una orden de compra aceptada o recibida.');
        }
        $exists = Payable::where('purchase_order_id', $po->id)->where('status', '!=', 'cancelada')->first();
        if ($exists) {
            throw new DomainException("La orden de compra ya tiene la cuenta por pagar {$exists->number}.");
        }
        if ((float) $po->total <= 0) {
            throw new DomainException('La orden de compra no tiene monto.');
        }

        if ($creditDays === null) {
            $creditDays = 0;
            if ($po->supplier && preg_match('/(\d{1,3})/', (string) $po->supplier->payment_terms, $m)) {
                $creditDays = (int) $m[1];
            }
        }

        $data = array_merge([
            'currency'      => $po->currency ?: 'MXN',
            'exchange_rate' => $po->exchange_rate ?: 1,
            'subtotal'      => (float) $po->subtotal - (float) ($po->discount_total ?? 0),
            'tax_total'     => (float) $po->tax_total,
            'total'         => (float) $po->total,
        ], array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
        $data['supplier_id']       = $po->supplier_id;
        $data['purchase_order_id'] = $po->id;
        $data['credit_days']       = $creditDays;
        $data['notes']             = $overrides['notes'] ?? "Generada desde la orden de compra {$po->po_number}";

        $payable = $this->createPayable($data, $userId);
        DocumentFlow::link($po, $payable);

        return $payable;
    }

    /**
     * Edita montos/fechas de una CXC o CXP y recalcula saldo y estatus.
     *
     * @param Receivable|Payable $doc
     */
    public function updateDocument($doc, array $data)
    {
        if ($doc->status === 'cancelada') {
            throw new DomainException('Un documento cancelado no se puede editar.');
        }
        $merged = array_merge($doc->only([
            'issue_date', 'due_date', 'credit_days', 'currency', 'exchange_rate', 'subtotal', 'tax_total', 'total',
        ]), array_filter($data, fn ($v, $k) => $v !== null && ($v !== '' || $k === 'notes'), ARRAY_FILTER_USE_BOTH));
        // Si solo cambian los días de crédito, el vencimiento se recalcula desde la emisión.
        if (empty($data['due_date']) && isset($data['credit_days'])) {
            unset($merged['due_date']);
        }
        // Si cambian subtotal/impuestos sin indicar total, el total se recalcula.
        if (empty($data['total']) && (isset($data['subtotal']) || isset($data['tax_total']))) {
            unset($merged['total']);
        }
        $extra = array_diff_key($data, array_flip(['issue_date', 'due_date', 'credit_days', 'currency', 'exchange_rate', 'subtotal', 'tax_total', 'total']));
        $data = array_merge($extra, $this->normalizeDocumentData($merged));

        if ($doc->applications()->exists() && $data['currency'] !== $doc->currency) {
            throw new DomainException('No se puede cambiar la moneda de un documento con pagos aplicados.');
        }
        if ($data['total'] + self::TOLERANCE < (float) $doc->paid_amount) {
            throw new DomainException('El total no puede ser menor a lo ya pagado.');
        }

        return DB::transaction(function () use ($doc, $data) {
            $doc->fill($data)->save();
            $doc->recalculate();

            return $doc->fresh();
        });
    }

    // ── Cancelación de documentos ──────────────────────────────────────────

    /** @param Receivable|Payable $doc */
    public function cancelDocument($doc)
    {
        if ($doc->applications()->exists()) {
            throw new DomainException('Primero desaplica los pagos de este documento.');
        }
        $doc->status = 'cancelada';
        $doc->balance = 0;
        $doc->save();

        return $doc;
    }

    // ── Pagos ──────────────────────────────────────────────────────────────

    /**
     * Registra un pago y, si se envían asignaciones, lo aplica.
     *
     * @param array $data         direction, customer_id|supplier_id, payment_date, amount, currency, exchange_rate, payment_method_id, reference, notes
     * @param array $allocations  [['id' => docId, 'amount' => monto en moneda del documento], ...]
     */
    public function registerPayment(array $data, ?UploadedFile $proof = null, array $allocations = [], ?int $userId = null): Payment
    {
        $direction = $data['direction'] ?? null;
        if (!in_array($direction, ['in', 'out'], true)) {
            throw new DomainException('Dirección de pago inválida.');
        }
        $partyKey = $direction === 'in' ? 'customer_id' : 'supplier_id';
        if (empty($data[$partyKey])) {
            throw new DomainException($direction === 'in' ? 'Selecciona un cliente.' : 'Selecciona un proveedor.');
        }
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new DomainException('El monto del pago debe ser mayor a cero.');
        }
        [$currency, $rate] = $this->currencyAndRate($data['currency'] ?? 'MXN', $data['exchange_rate'] ?? null);

        return DB::transaction(function () use ($data, $direction, $partyKey, $amount, $currency, $rate, $proof, $allocations, $userId) {
            $payment = new Payment([
                'direction'         => $direction,
                $partyKey           => $data[$partyKey],
                'payment_date'      => $data['payment_date'] ?? now()->toDateString(),
                'amount'            => $amount,
                'applied_amount'    => 0,
                'currency'          => $currency,
                'exchange_rate'     => $rate,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'reference'         => $data['reference'] ?? null,
                'status'            => 'pendiente',
                'notes'             => $data['notes'] ?? null,
            ]);
            $payment->number = Payment::generateNumber();
            $payment->created_by_user_id = $userId;
            if ($proof) {
                $payment->proof_path = $this->storeProof($proof);
            }
            $payment->save();

            if ($allocations) {
                $this->applyPayment($payment, $allocations);
            }

            return $payment->fresh();
        });
    }

    /**
     * Aplica (parcial o totalmente) un pago a uno o varios documentos abiertos.
     *
     * @param array $allocations [['id' => docId, 'amount' => monto en moneda del documento], ...]
     */
    public function applyPayment(Payment $payment, array $allocations): Payment
    {
        $allocations = array_values(array_filter($allocations, fn ($a) => round((float) ($a['amount'] ?? 0), 2) > 0));
        if (!$allocations) {
            throw new DomainException('Captura al menos un monto a aplicar.');
        }

        return DB::transaction(function () use ($payment, $allocations) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'cancelado') {
                throw new DomainException('El pago está cancelado.');
            }
            if ($payment->status === 'conciliado') {
                throw new DomainException('El pago está conciliado; quita la conciliación para modificar sus aplicaciones.');
            }

            $docClass = $payment->isIncoming() ? Receivable::class : Payable::class;
            $type     = $docClass::APPLICABLE_TYPE;
            $partyCol = $payment->isIncoming() ? 'customer_id' : 'supplier_id';
            $payRate  = (float) ($payment->exchange_rate ?: 1);
            $remaining = $payment->unappliedAmount();
            $touched  = [];

            // Un mismo documento solo una vez por llamada.
            $merged = [];
            foreach ($allocations as $a) {
                $merged[(int) $a['id']] = ($merged[(int) $a['id']] ?? 0) + round((float) $a['amount'], 2);
            }

            foreach ($merged as $docId => $amount) {
                $doc = $docClass::whereKey($docId)->lockForUpdate()->first();
                if (!$doc) {
                    throw new DomainException('Uno de los documentos ya no existe.');
                }
                if ((int) $doc->{$partyCol} !== (int) $payment->{$partyCol}) {
                    throw new DomainException("El documento {$doc->number} pertenece a otra contraparte.");
                }
                if (!$doc->isOpen()) {
                    throw new DomainException("El documento {$doc->number} no tiene saldo abierto.");
                }
                $balance = round((float) $doc->balance, 2);
                if ($amount > $balance + self::TOLERANCE) {
                    throw new DomainException("El monto aplicado a {$doc->number} excede su saldo (" . number_format($balance, 2) . " {$doc->currency}).");
                }
                $amount = min($amount, $balance);

                $consumed = round($amount * (float) ($doc->exchange_rate ?: 1) / $payRate, 2);
                if ($consumed > $remaining + self::TOLERANCE) {
                    throw new DomainException('Los montos aplicados exceden el monto disponible del pago (' . number_format($remaining, 2) . " {$payment->currency}).");
                }
                $consumed = min($consumed, $remaining);
                $remaining = round($remaining - $consumed, 2);

                $app = PaymentApplication::where(['payment_id' => $payment->id, 'applicable_type' => $type, 'applicable_id' => $doc->id])->first();
                if ($app) {
                    $app->amount_applied = round((float) $app->amount_applied + $amount, 2);
                    $app->amount_payment = round((float) $app->amount_payment + $consumed, 2);
                    $app->save();
                } else {
                    PaymentApplication::create([
                        'payment_id' => $payment->id, 'applicable_type' => $type, 'applicable_id' => $doc->id,
                        'amount_applied' => $amount, 'amount_payment' => $consumed,
                    ]);
                }
                $doc->recalculate();
                DocumentFlow::link($doc, $payment);
                $touched[] = $doc->id;
            }

            $this->refreshPayment($payment);

            return $payment->fresh();
        });
    }

    /** Distribución FIFO (vencimiento más antiguo primero) del saldo libre del pago. */
    public function fifoAllocations(Payment $payment): array
    {
        $docs = $this->openDocumentsFor($payment->direction, $payment->isIncoming() ? $payment->customer_id : $payment->supplier_id);
        $remaining = $payment->unappliedAmount();
        $payRate = (float) ($payment->exchange_rate ?: 1);
        $out = [];
        foreach ($docs as $doc) {
            if ($remaining <= 0.004) {
                break;
            }
            $balance = round((float) $doc->balance, 2);
            $docRate = (float) ($doc->exchange_rate ?: 1);
            $needInPayCurrency = round($balance * $docRate / $payRate, 2);
            if ($needInPayCurrency <= $remaining + self::TOLERANCE) {
                $amount = $balance;
                $remaining = round(max(0, $remaining - $needInPayCurrency), 2);
            } else {
                $amount = round($remaining * $payRate / $docRate, 2);
                $remaining = 0;
            }
            if ($amount > 0) {
                $out[] = ['id' => $doc->id, 'amount' => $amount];
            }
        }

        return $out;
    }

    /** Documentos abiertos de una contraparte, del vencimiento más antiguo al más nuevo. */
    public function openDocumentsFor(string $direction, ?int $partyId): Collection
    {
        if (!$partyId) {
            return collect();
        }
        $q = $direction === 'in'
            ? Receivable::where('customer_id', $partyId)
            : Payable::where('supplier_id', $partyId);

        return $q->open()->where('balance', '>', 0)->orderBy('due_date')->orderBy('id')->get();
    }

    /** Revierte una aplicación (el documento recupera su saldo). */
    public function unapply(PaymentApplication $application): Payment
    {
        return DB::transaction(function () use ($application) {
            $payment = Payment::whereKey($application->payment_id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'conciliado') {
                throw new DomainException('El pago está conciliado; quita la conciliación primero.');
            }
            $doc = $application->document();
            $type = $application->applicable_type;
            $application->delete();

            if ($doc) {
                $doc->recalculate();
                if (!PaymentApplication::where(['payment_id' => $payment->id, 'applicable_type' => $type, 'applicable_id' => $doc->id])->exists()) {
                    $this->unlink($doc, $payment);
                }
            }
            $this->refreshPayment($payment);

            return $payment->fresh();
        });
    }

    /** Cancela el pago y revierte todas sus aplicaciones. */
    public function cancelPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'conciliado') {
                throw new DomainException('El pago está conciliado; quita la conciliación antes de cancelarlo.');
            }
            if ($payment->status === 'cancelado') {
                return $payment;
            }
            foreach ($payment->applications()->get() as $app) {
                $doc = $app->document();
                $app->delete();
                if ($doc) {
                    $doc->recalculate();
                    $this->unlink($doc, $payment);
                }
            }
            $payment->applied_amount = 0;
            $payment->status = 'cancelado';
            $payment->save();

            return $payment->fresh();
        });
    }

    /** Conciliación simple: el pago aplicado se marca como conciliado. */
    public function reconcile(Payment $payment): Payment
    {
        $payment->refresh();
        if ($payment->status !== 'aplicado') {
            throw new DomainException('Solo se puede conciliar un pago aplicado.');
        }
        $payment->status = 'conciliado';
        $payment->reconciled_at = now();
        $payment->save();

        return $payment;
    }

    public function unreconcile(Payment $payment): Payment
    {
        $payment->refresh();
        if ($payment->status !== 'conciliado') {
            return $payment;
        }
        $payment->status = (float) $payment->applied_amount > 0 ? 'aplicado' : 'pendiente';
        $payment->reconciled_at = null;
        $payment->save();

        return $payment;
    }

    /** Elimina un pago sin aplicaciones (pendiente o cancelado). */
    public function deletePayment(Payment $payment): void
    {
        $payment->refresh();
        if ($payment->applications()->exists() || in_array($payment->status, ['aplicado', 'conciliado'], true)) {
            throw new DomainException('Cancela el pago antes de eliminarlo.');
        }
        DocumentFlow::unlinkAll($payment);
        $payment->delete();
    }

    private function refreshPayment(Payment $payment): void
    {
        $applied = round((float) $payment->applications()->sum('amount_payment'), 2);
        $payment->applied_amount = $applied;
        if (!in_array($payment->status, ['cancelado', 'conciliado'], true)) {
            $payment->status = $applied > 0 ? 'aplicado' : 'pendiente';
        }
        $payment->save();
    }

    private function unlink($doc, Payment $payment): void
    {
        $ft = DocumentFlow::typeOf($doc);
        $tt = DocumentFlow::typeOf($payment);
        if ($ft && $tt) {
            DocumentLink::where(['from_type' => $ft, 'from_id' => $doc->id, 'to_type' => $tt, 'to_id' => $payment->id])->delete();
        }
    }

    // ── Vencimientos ───────────────────────────────────────────────────────

    /** Marca como vencidos los documentos abiertos con fecha pasada. Devuelve cuántos cambió. */
    public function markOverdue(): int
    {
        $n = 0;
        foreach ([Receivable::class, Payable::class] as $class) {
            $class::whereIn('status', ['pendiente', 'parcial'])
                ->where('balance', '>', 0)
                ->whereDate('due_date', '<', now()->toDateString())
                ->get()
                ->each(function ($doc) use (&$n) {
                    $doc->status = 'vencida';
                    $doc->save();
                    $n++;
                });
        }

        return $n;
    }

    // ── Reportes ───────────────────────────────────────────────────────────

    /**
     * Antigüedad de saldos por contraparte (montos en MXN).
     *
     * @return array{rows: array<int, array>, totals: array<string,float>}
     */
    public function agingReport(string $kind, ?int $partyId = null): array
    {
        $isRec = $kind === 'receivable';
        $query = ($isRec ? Receivable::with('customer') : Payable::with('supplier'))->open()->where('balance', '>', 0);
        $partyCol = $isRec ? 'customer_id' : 'supplier_id';
        if ($partyId) {
            $query->where($partyCol, $partyId);
        }

        $zero = array_fill_keys(array_keys(self::BUCKETS), 0.0);
        $rows = [];
        $totals = $zero + ['total' => 0.0];

        foreach ($query->get() as $doc) {
            $pid = (int) $doc->{$partyCol};
            $rows[$pid] ??= ['party_id' => $pid, 'party' => $doc->partyName(), 'count' => 0, 'total' => 0.0] + $zero;
            $bucket = $doc->agingBucket();
            $amt = $doc->balanceMxn();
            $rows[$pid][$bucket] += $amt;
            $rows[$pid]['total'] += $amt;
            $rows[$pid]['count']++;
            $totals[$bucket] += $amt;
            $totals['total'] += $amt;
        }
        uasort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return ['rows' => array_values($rows), 'totals' => $totals];
    }

    /**
     * Estado de cuenta de una contraparte, agrupado por moneda.
     *
     * @return array<string, array{opening: float, lines: array, charges: float, credits: float, closing: float}>
     */
    public function statement(string $kind, int $partyId, ?string $from = null, ?string $to = null): array
    {
        $isRec = $kind === 'receivable';
        $docs = ($isRec ? Receivable::where('customer_id', $partyId) : Payable::where('supplier_id', $partyId))
            ->where('status', '!=', 'cancelada')->orderBy('issue_date')->orderBy('id')->get();
        $from = $from ? Carbon::parse($from)->startOfDay() : null;
        $to   = $to ? Carbon::parse($to)->endOfDay() : null;

        $moves = [];
        foreach ($docs as $doc) {
            $moves[] = [
                'date' => $doc->issue_date->copy(), 'order' => 0, 'currency' => $doc->currency,
                'type' => 'doc', 'number' => $doc->number, 'concept' => ($isRec ? 'Cuenta por cobrar' : 'Cuenta por pagar') . ' · vence ' . $doc->due_date->format('d/m/Y'),
                'charge' => (float) $doc->total, 'credit' => 0.0,
                'route' => $isRec ? 'admin.receivables.show' : 'admin.payables.show', 'id' => $doc->id,
            ];
            $apps = PaymentApplication::with('payment')
                ->where('applicable_type', $doc::APPLICABLE_TYPE)
                ->where('applicable_id', $doc->id)->get();
            foreach ($apps as $app) {
                $moves[] = [
                    'date' => $app->payment->payment_date->copy(), 'order' => 1, 'currency' => $doc->currency,
                    'type' => 'payment', 'number' => $app->payment->number, 'concept' => "Pago aplicado a {$doc->number}",
                    'charge' => 0.0, 'credit' => (float) $app->amount_applied,
                    'route' => 'admin.payments.show', 'id' => $app->payment_id,
                ];
            }
        }
        usort($moves, fn ($a, $b) => [$a['date']->timestamp, $a['order']] <=> [$b['date']->timestamp, $b['order']]);

        $out = [];
        foreach ($moves as $m) {
            $c = $m['currency'];
            $out[$c] ??= ['opening' => 0.0, 'lines' => [], 'charges' => 0.0, 'credits' => 0.0, 'closing' => 0.0, '_running' => 0.0];
            if ($to && $m['date']->gt($to)) {
                continue;
            }
            $delta = $m['charge'] - $m['credit'];
            if ($from && $m['date']->lt($from)) {
                $out[$c]['opening'] += $delta;
                $out[$c]['_running'] += $delta;
                continue;
            }
            $out[$c]['_running'] += $delta;
            $out[$c]['charges'] += $m['charge'];
            $out[$c]['credits'] += $m['credit'];
            $m['balance'] = round($out[$c]['_running'], 2);
            $out[$c]['lines'][] = $m;
        }
        foreach ($out as $c => &$g) {
            $g['opening'] = round($g['opening'], 2);
            $g['closing'] = round($g['_running'], 2);
            unset($g['_running']);
        }

        return $out;
    }

    /** Resumen del panel: por cobrar/pagar, vencido y flujo a N días (MXN). */
    public function summary(string $kind, int $days = 30): array
    {
        $class = $kind === 'receivable' ? Receivable::class : Payable::class;
        $open = fn () => $class::open()->where('balance', '>', 0);
        $sum = fn ($q) => round((float) $q->sum(DB::raw('balance * exchange_rate')), 2);
        $today = now()->toDateString();

        return [
            'open'     => $sum($open()),
            'overdue'  => $sum($open()->whereDate('due_date', '<', $today)),
            'next'     => $sum($open()->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', now()->addDays($days)->toDateString())),
            'count'    => $open()->count(),
            'overdue_count' => $open()->whereDate('due_date', '<', $today)->count(),
        ];
    }

    /** Flujo de efectivo esperado a N días: cobros − pagos (MXN). */
    public function cashflow(int $days = 30): array
    {
        $in = $this->summary('receivable', $days);
        $out = $this->summary('payable', $days);

        return ['in' => $in['next'], 'out' => $out['next'], 'net' => round($in['next'] - $out['next'], 2)];
    }

    // ── Utilidades ─────────────────────────────────────────────────────────

    public function storeProof(UploadedFile $file): string
    {
        $dir = UploadPath::base() . '/finance-proofs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $name = uniqid('proof_', true) . '.' . strtolower($file->getClientOriginalExtension() ?: 'bin');
        $file->move($dir, $name);

        return 'finance-proofs/' . $name;
    }

    private function currencyAndRate(?string $currency, $rate): array
    {
        $currency = strtoupper($currency ?: 'MXN');
        if (!in_array($currency, ['MXN', 'USD'], true)) {
            throw new DomainException('Moneda no soportada.');
        }
        if ($currency === 'MXN') {
            return ['MXN', 1.0];
        }
        $rate = (float) $rate;
        if ($rate <= 0) {
            throw new DomainException('Captura el tipo de cambio para operar en USD.');
        }

        return [$currency, $rate];
    }

    /** Normaliza fechas, moneda, tipo de cambio y montos de una CXC/CXP. */
    private function normalizeDocumentData(array $data): array
    {
        [$data['currency'], $data['exchange_rate']] = $this->currencyAndRate($data['currency'] ?? 'MXN', $data['exchange_rate'] ?? null);

        $issue = Carbon::parse($data['issue_date'] ?? now())->startOfDay();
        $data['issue_date'] = $issue->toDateString();
        if (!empty($data['due_date'])) {
            $due = Carbon::parse($data['due_date'])->startOfDay();
            $data['credit_days'] = max(0, (int) $issue->diffInDays($due, false));
        } else {
            $days = max(0, (int) ($data['credit_days'] ?? 0));
            $due = $issue->copy()->addDays($days);
            $data['credit_days'] = $days;
        }
        $data['due_date'] = $due->toDateString();

        $subtotal = round((float) ($data['subtotal'] ?? 0), 2);
        $tax = round((float) ($data['tax_total'] ?? 0), 2);
        $total = isset($data['total']) && $data['total'] !== '' ? round((float) $data['total'], 2) : round($subtotal + $tax, 2);
        if ($total <= 0) {
            throw new DomainException('El total debe ser mayor a cero.');
        }
        $data['subtotal'] = $subtotal;
        $data['tax_total'] = $tax;
        $data['total'] = $total;

        return $data;
    }
}
