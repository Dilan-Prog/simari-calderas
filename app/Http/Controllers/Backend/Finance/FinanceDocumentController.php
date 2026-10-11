<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\FinanceService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Base común de Cuentas por cobrar y por pagar (misma mecánica, distinta
 * contraparte). Las subclases solo definen cfg() y lo específico de cada una.
 */
abstract class FinanceDocumentController extends Controller
{
    public function __construct(protected FinanceService $finance)
    {
    }

    /** @return array{kind:string,model:class-string,party_col:string,party_label:string,singular:string,plural:string,route:string,perm:string,title:string} */
    abstract protected function cfg(): array;

    /** Opciones de contraparte para filtros y formularios: [id => nombre]. */
    abstract protected function partyOptions(bool $onlyWithDocuments = false): array;

    abstract protected function partyRelation(): string;

    /** Agrega (OR) la búsqueda por nombre de contraparte. */
    abstract protected function partySearch(Builder $q, string $s): void;

    protected function filtered(Request $request): Builder
    {
        $c = $this->cfg();
        $q = $c['model']::with($this->partyRelation());

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%");
                $this->partySearch($w, $s);
            });
        }
        if ($request->filled($c['party_col'])) {
            $q->where($c['party_col'], (int) $request->input($c['party_col']));
        }
        if ($request->filled('status')) {
            if ($request->status === 'abiertas') {
                $q->open();
            } else {
                $q->where('status', $request->status);
            }
        }
        if ($request->filled('currency')) {
            $q->where('currency', $request->currency);
        }
        if ($request->filled('due_from')) {
            $q->whereDate('due_date', '>=', $request->due_from);
        }
        if ($request->filled('due_to')) {
            $q->whereDate('due_date', '<=', $request->due_to);
        }

        return $q;
    }

    public function index(Request $request)
    {
        $c = $this->cfg();
        $docs = $this->filtered($request)->orderByRaw("FIELD(status,'vencida','parcial','pendiente','pagada','cancelada')")
            ->orderBy('due_date')->paginate(20)->withQueryString();

        return view('admin.finance.docs.index', [
            'cfg'      => $c,
            'docs'     => $docs,
            'summary'  => $this->finance->summary($c['kind']),
            'parties'  => $this->partyOptions(true),
            'statuses' => ['abiertas' => 'Abiertas', 'pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'vencida' => 'Vencida', 'pagada' => 'Pagada', 'cancelada' => 'Cancelada'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $c = $this->cfg();
        $rows = $this->filtered($request)->orderBy('due_date')->get();

        return response()->streamDownload(function () use ($rows, $c) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Folio', $c['party_label'], 'Emisión', 'Vencimiento', 'Días crédito', 'Moneda', 'T. cambio', 'Total', 'Pagado', 'Saldo', 'Estatus']);
            foreach ($rows as $d) {
                fputcsv($out, [
                    $d->number, $d->partyName(), $d->issue_date->format('Y-m-d'), $d->due_date->format('Y-m-d'), $d->credit_days,
                    $d->currency, $d->exchange_rate, $d->total, $d->paid_amount, $d->balance, $d::statusLabel($d->status),
                ]);
            }
            fclose($out);
        }, $c['route'] . '-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function aging(Request $request)
    {
        $c = $this->cfg();
        $partyId = $request->filled('party') ? (int) $request->party : null;

        return view('admin.finance.docs.aging', [
            'cfg'     => $c,
            'report'  => $this->finance->agingReport($c['kind'], $partyId),
            'buckets' => FinanceService::BUCKETS,
            'parties' => $this->partyOptions(true),
            'partyId' => $partyId,
        ]);
    }

    public function statement(Request $request, int $party)
    {
        $c = $this->cfg();
        $options = $this->partyOptions();
        abort_unless(isset($options[$party]), 404);

        return view('admin.finance.docs.statement', [
            'cfg'       => $c,
            'partyId'   => $party,
            'partyName' => $options[$party],
            'groups'    => $this->finance->statement($c['kind'], $party, $request->input('from'), $request->input('to')),
            'from'      => $request->input('from'),
            'to'        => $request->input('to'),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.finance.docs.form', $this->formData(null, $request));
    }

    public function edit($id)
    {
        $doc = $this->cfg()['model']::findOrFail($id);
        if ($doc->status === 'cancelada') {
            return redirect()->route($this->cfg()['route'] . '.show', $doc)->with('error', 'Un documento cancelado no se puede editar.');
        }

        return view('admin.finance.docs.form', $this->formData($doc));
    }

    protected function formData($doc, ?Request $request = null): array
    {
        return [
            'cfg'     => $this->cfg(),
            'doc'     => $doc,
            'parties' => $this->partyOptions(),
            'prefill' => $request?->only(['customer_id', 'supplier_id']) ?? [],
        ];
    }

    /** Reglas comunes de captura. */
    protected function documentRules(): array
    {
        return [
            'issue_date'    => 'required|date',
            'credit_days'   => 'nullable|integer|min:0|max:3650',
            'due_date'      => 'nullable|date',
            'currency'      => 'required|in:MXN,USD',
            'exchange_rate' => 'nullable|numeric|min:0.0001|max:99999',
            'subtotal'      => 'required|numeric|min:0|max:99999999',
            'tax_total'     => 'nullable|numeric|min:0|max:99999999',
            'total'         => 'nullable|numeric|min:0|max:99999999',
            'notes'         => 'nullable|string|max:5000',
        ];
    }

    public function show($id)
    {
        $c = $this->cfg();
        $doc = $c['model']::with($this->partyRelation())->findOrFail($id);
        $applications = $doc->applications()->with('payment')->latest('id')->get();

        return view('admin.finance.docs.show', [
            'cfg'          => $c,
            'doc'          => $doc,
            'applications' => $applications,
            'source'       => method_exists($doc, 'source') ? $doc->source() : null,
        ]);
    }

    public function cancel($id)
    {
        $c = $this->cfg();
        $doc = $c['model']::findOrFail($id);
        try {
            $this->finance->cancelDocument($doc);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($c['route'] . '.show', $doc)->with('success', "{$doc->number} cancelada.");
    }

    public function destroy($id)
    {
        $c = $this->cfg();
        $doc = $c['model']::findOrFail($id);
        if ($doc->applications()->exists()) {
            return back()->with('error', 'Tiene pagos aplicados; desaplícalos o cancela el documento.');
        }
        DB::transaction(function () use ($doc) {
            \App\Services\Erp\DocumentFlow::unlinkAll($doc);
            $doc->delete();
        });

        return redirect()->route($c['route'] . '.index')->with('success', 'Documento eliminado.');
    }

    protected function customerOptions(bool $onlyWith, string $docModel): array
    {
        $q = Customer::query();
        if ($onlyWith) {
            $q->whereIn('id', $docModel::query()->select('customer_id'));
        }

        return $q->get(['id', 'first_name', 'last_name', 'company'])
            ->mapWithKeys(fn ($x) => [$x->id => $x->company ?: trim($x->first_name . ' ' . $x->last_name)])
            ->sort()->all();
    }

    protected function supplierOptions(bool $onlyWith, string $docModel): array
    {
        $q = Supplier::query();
        if ($onlyWith) {
            $q->whereIn('id', $docModel::query()->select('supplier_id'));
        }

        return $q->get(['id', 'company_name', 'contact_name'])
            ->mapWithKeys(fn ($x) => [$x->id => $x->company_name ?: ($x->contact_name ?: '—')])
            ->sort()->all();
    }
}
