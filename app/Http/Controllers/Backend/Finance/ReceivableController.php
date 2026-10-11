<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Models\Receivable;
use App\Models\SalesOrder;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceivableController extends FinanceDocumentController
{
    protected function cfg(): array
    {
        return [
            'kind'        => 'receivable',
            'model'       => Receivable::class,
            'party_col'   => 'customer_id',
            'party_label' => 'Cliente',
            'singular'    => 'cuenta por cobrar',
            'plural'      => 'Cuentas por cobrar',
            'route'       => 'admin.receivables',
            'perm'        => 'receivables',
            'title'       => 'Cuentas por cobrar',
            'flow_label'  => 'por cobrar',
        ];
    }

    protected function partyRelation(): string
    {
        return 'customer';
    }

    protected function partyOptions(bool $onlyWithDocuments = false): array
    {
        return $this->customerOptions($onlyWithDocuments, Receivable::class);
    }

    protected function partySearch(Builder $q, string $s): void
    {
        $q->orWhereHas('customer', fn ($p) => $p->where('company', 'like', "%{$s}%")
            ->orWhere('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->documentRules() + [
            'customer_id' => 'required|exists:customers,id',
            'source_type' => ['nullable', Rule::in(array_keys(Receivable::SOURCE_TYPES))],
            'source_id'   => 'nullable|integer|min:1',
            'project_id'  => 'nullable|integer|min:1',
        ]);
        $data['due_date'] = $data['due_date'] ?? null;

        try {
            $doc = $this->finance->createReceivable($data, auth()->id());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.receivables.show', $doc)->with('success', "Cuenta por cobrar {$doc->number} creada.");
    }

    public function update(Request $request, $id)
    {
        $doc = Receivable::findOrFail($id);
        $data = $request->validate($this->documentRules() + [
            'customer_id' => 'required|exists:customers,id',
            'source_type' => ['nullable', Rule::in(array_keys(Receivable::SOURCE_TYPES))],
            'source_id'   => 'nullable|integer|min:1',
            'project_id'  => 'nullable|integer|min:1',
        ]);
        if ((int) $data['customer_id'] !== (int) $doc->customer_id && $doc->applications()->exists()) {
            return back()->withInput()->with('error', 'No se puede cambiar el cliente con pagos aplicados.');
        }

        try {
            $this->finance->updateDocument($doc, $data);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.receivables.show', $doc)->with('success', 'Cuenta por cobrar actualizada.');
    }

    /** Botón "Generar cuenta por cobrar" del pedido. */
    public function fromSalesOrder(Request $request, SalesOrder $salesOrder)
    {
        $request->validate(['credit_days' => 'nullable|integer|min:0|max:3650']);

        try {
            $doc = $this->finance->createReceivableFromSalesOrder(
                $salesOrder,
                $request->filled('credit_days') ? (int) $request->credit_days : 0,
                [],
                auth()->id()
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.receivables.show', $doc)->with('success', "Cuenta por cobrar {$doc->number} generada desde {$salesOrder->order_number}.");
    }
}
