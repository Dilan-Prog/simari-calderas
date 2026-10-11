<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentApplication;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\FinanceService;
use App\Support\UploadPath;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private FinanceService $finance)
    {
    }

    private function filtered(Request $request): Builder
    {
        $q = Payment::with(['customer', 'supplier', 'paymentMethod']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($p) => $p->where('company', 'like', "%{$s}%")
                        ->orWhere('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"))
                    ->orWhereHas('supplier', fn ($p) => $p->where('company_name', 'like', "%{$s}%")
                        ->orWhere('contact_name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('direction')) {
            $q->where('direction', $request->direction);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('currency')) {
            $q->where('currency', $request->currency);
        }
        if ($request->filled('method')) {
            $q->where('payment_method_id', (int) $request->method);
        }
        if ($request->filled('from')) {
            $q->whereDate('payment_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('payment_date', '<=', $request->to);
        }

        return $q;
    }

    public function index(Request $request)
    {
        $payments = $this->filtered($request)->latest('payment_date')->latest('id')->paginate(20)->withQueryString();

        return view('admin.finance.payments.index', [
            'payments' => $payments,
            'methods'  => PaymentMethod::orderBy('name')->pluck('name', 'id'),
            'statuses' => ['pendiente' => 'Pendiente', 'aplicado' => 'Aplicado', 'conciliado' => 'Conciliado', 'cancelado' => 'Cancelado'],
            'stats'    => [
                'pending'    => Payment::where('status', 'pendiente')->count(),
                'to_reconcile' => Payment::where('status', 'aplicado')->count(),
                'in_month'   => (float) Payment::where('direction', 'in')->whereIn('status', ['aplicado', 'conciliado', 'pendiente'])
                    ->whereYear('payment_date', now()->year)->whereMonth('payment_date', now()->month)
                    ->sum(DB::raw('amount * exchange_rate')),
                'out_month'  => (float) Payment::where('direction', 'out')->whereIn('status', ['aplicado', 'conciliado', 'pendiente'])
                    ->whereYear('payment_date', now()->year)->whereMonth('payment_date', now()->month)
                    ->sum(DB::raw('amount * exchange_rate')),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($request)->latest('payment_date')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Folio', 'Tipo', 'Contraparte', 'Fecha', 'Monto', 'Moneda', 'T. cambio', 'Aplicado', 'Método', 'Referencia', 'Estatus', 'Conciliado']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->number, $p->directionLabel(), $p->partyName(), $p->payment_date->format('Y-m-d'), $p->amount, $p->currency,
                    $p->exchange_rate, $p->applied_amount, $p->paymentMethod?->name, $p->reference, Payment::statusLabel($p->status),
                    $p->reconciled_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'pagos-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request)
    {
        $direction = $request->input('direction') === 'out' ? 'out' : 'in';

        return view('admin.finance.payments.create', [
            'direction' => $direction,
            'customers' => $this->options(Customer::class),
            'suppliers' => $this->options(Supplier::class),
            'methods'   => PaymentMethod::orderBy('name')->get(['id', 'name']),
            'prefill'   => [
                'customer_id' => $request->input('customer_id'),
                'supplier_id' => $request->input('supplier_id'),
                'doc'         => $request->input('doc'),
            ],
        ]);
    }

    private function options(string $model): array
    {
        if ($model === Customer::class) {
            return Customer::get(['id', 'first_name', 'last_name', 'company'])
                ->mapWithKeys(fn ($x) => [$x->id => $x->company ?: trim($x->first_name . ' ' . $x->last_name)])->sort()->all();
        }

        return Supplier::get(['id', 'company_name', 'contact_name'])
            ->mapWithKeys(fn ($x) => [$x->id => $x->company_name ?: ($x->contact_name ?: '—')])->sort()->all();
    }

    /** JSON: documentos abiertos de una contraparte para el formulario de pago. */
    public function openDocuments(Request $request)
    {
        $direction = $request->input('direction') === 'out' ? 'out' : 'in';
        $docs = $this->finance->openDocumentsFor($direction, (int) $request->input('party'));

        return response()->json($docs->map(fn ($d) => [
            'id'           => $d->id,
            'number'       => $d->number,
            'due_date'     => $d->due_date->format('d/m/Y'),
            'days_overdue' => $d->daysOverdue(),
            'currency'     => $d->currency,
            'rate'         => (float) $d->exchange_rate,
            'total'        => (float) $d->total,
            'balance'      => (float) $d->balance,
            'tone'         => $d->dueTone(),
        ])->values());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction'         => 'required|in:in,out',
            'customer_id'       => 'required_if:direction,in|nullable|exists:customers,id',
            'supplier_id'       => 'required_if:direction,out|nullable|exists:suppliers,id',
            'payment_date'      => 'required|date',
            'amount'            => 'required|numeric|min:0.01|max:999999999',
            'currency'          => 'required|in:MXN,USD',
            'exchange_rate'     => 'nullable|numeric|min:0.0001|max:99999',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'reference'         => 'nullable|string|max:120',
            'proof'             => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'notes'             => 'nullable|string|max:5000',
            'alloc'             => 'nullable|array',
            'alloc.*'           => 'nullable|numeric|min:0',
            'fifo'              => 'nullable|boolean',
        ]);
        if ($data['direction'] === 'in') {
            $data['supplier_id'] = null;
        } else {
            $data['customer_id'] = null;
        }

        try {
            $payment = DB::transaction(function () use ($data, $request) {
                $payment = $this->finance->registerPayment($data, $request->file('proof'), [], auth()->id());

                $allocations = [];
                if ($request->boolean('fifo')) {
                    $allocations = $this->finance->fifoAllocations($payment);
                } else {
                    foreach ((array) $request->input('alloc', []) as $docId => $amount) {
                        if ((float) $amount > 0) {
                            $allocations[] = ['id' => (int) $docId, 'amount' => (float) $amount];
                        }
                    }
                }
                if ($allocations) {
                    $this->finance->applyPayment($payment, $allocations);
                }

                return $payment;
            });
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $payment)->with('success', "Pago {$payment->number} registrado.");
    }

    public function show($id)
    {
        $payment = Payment::with(['customer', 'supplier', 'paymentMethod', 'createdBy'])->findOrFail($id);
        $applications = $payment->applications()->latest('id')->get()->each(fn ($a) => $a->setRelation('doc', $a->document()));
        $open = $payment->status === 'cancelado' || $payment->status === 'conciliado'
            ? collect()
            : $this->finance->openDocumentsFor($payment->direction, $payment->isIncoming() ? $payment->customer_id : $payment->supplier_id);

        return view('admin.finance.payments.show', [
            'payment'      => $payment,
            'applications' => $applications,
            'openDocs'     => $open,
        ]);
    }

    public function apply(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $request->validate(['alloc' => 'nullable|array', 'alloc.*' => 'nullable|numeric|min:0', 'fifo' => 'nullable|boolean']);

        try {
            if ($request->boolean('fifo')) {
                $allocations = $this->finance->fifoAllocations($payment);
            } else {
                $allocations = [];
                foreach ((array) $request->input('alloc', []) as $docId => $amount) {
                    if ((float) $amount > 0) {
                        $allocations[] = ['id' => (int) $docId, 'amount' => (float) $amount];
                    }
                }
            }
            $this->finance->applyPayment($payment, $allocations);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Aplicación registrada.');
    }

    public function unapply($id, $application)
    {
        $app = PaymentApplication::where('payment_id', $id)->findOrFail($application);
        try {
            $this->finance->unapply($app);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Aplicación revertida; el documento recuperó su saldo.');
    }

    public function reconcile($id)
    {
        return $this->action($id, fn ($p) => $this->finance->reconcile($p), 'Pago conciliado.');
    }

    public function unreconcile($id)
    {
        return $this->action($id, fn ($p) => $this->finance->unreconcile($p), 'Conciliación retirada.');
    }

    public function cancel($id)
    {
        return $this->action($id, fn ($p) => $this->finance->cancelPayment($p), 'Pago cancelado y aplicaciones revertidas.');
    }

    private function action($id, \Closure $fn, string $ok)
    {
        $payment = Payment::findOrFail($id);
        try {
            $fn($payment);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $ok);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        try {
            $this->finance->deletePayment($payment);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payments.index')->with('success', 'Pago eliminado.');
    }

    /** Comprobante: se sirve autenticado (no vive en una carpeta pública). */
    public function proof($id)
    {
        $payment = Payment::findOrFail($id);
        abort_unless($payment->proof_path, 404);
        $base = realpath(UploadPath::base());
        $full = realpath(UploadPath::full($payment->proof_path));
        abort_unless($base && $full && str_starts_with($full, $base) && is_file($full), 404);

        return response()->file($full);
    }
}
