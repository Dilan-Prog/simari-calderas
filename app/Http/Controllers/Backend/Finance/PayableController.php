<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Models\Payable;
use App\Models\PurchaseOrder;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PayableController extends FinanceDocumentController
{
    protected function cfg(): array
    {
        return [
            'kind'        => 'payable',
            'model'       => Payable::class,
            'party_col'   => 'supplier_id',
            'party_label' => 'Proveedor',
            'singular'    => 'cuenta por pagar',
            'plural'      => 'Cuentas por pagar',
            'route'       => 'admin.payables',
            'perm'        => 'payables',
            'title'       => 'Cuentas por pagar',
            'flow_label'  => 'por pagar',
        ];
    }

    protected function partyRelation(): string
    {
        return 'supplier';
    }

    protected function partyOptions(bool $onlyWithDocuments = false): array
    {
        return $this->supplierOptions($onlyWithDocuments, Payable::class);
    }

    protected function partySearch(Builder $q, string $s): void
    {
        $q->orWhereHas('supplier', fn ($p) => $p->where('company_name', 'like', "%{$s}%")
            ->orWhere('contact_name', 'like', "%{$s}%"))
            ->orWhere('supplier_invoice', 'like', "%{$s}%");
    }

    private function rules(): array
    {
        return $this->documentRules() + [
            'supplier_id'       => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_invoice'  => 'nullable|string|max:60',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['due_date'] = $data['due_date'] ?? null;

        try {
            $doc = $this->finance->createPayable($data, auth()->id());
            if ($doc->purchase_order_id) {
                \App\Services\Erp\DocumentFlow::link($doc->purchaseOrder, $doc);
            }
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payables.show', $doc)->with('success', "Cuenta por pagar {$doc->number} creada.");
    }

    public function update(Request $request, $id)
    {
        $doc = Payable::findOrFail($id);
        $data = $request->validate($this->rules());
        if ((int) $data['supplier_id'] !== (int) $doc->supplier_id && $doc->applications()->exists()) {
            return back()->withInput()->with('error', 'No se puede cambiar el proveedor con pagos aplicados.');
        }

        try {
            $this->finance->updateDocument($doc, $data);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payables.show', $doc)->with('success', 'Cuenta por pagar actualizada.');
    }

    /** Botón "Generar cuenta por pagar" de la orden de compra. */
    public function fromPurchaseOrder(Request $request, $purchaseOrder)
    {
        $po = PurchaseOrder::findOrFail($purchaseOrder);
        $request->validate(['credit_days' => 'nullable|integer|min:0|max:3650']);

        try {
            $doc = $this->finance->createPayableFromPurchaseOrder(
                $po,
                $request->filled('credit_days') ? (int) $request->credit_days : null,
                [],
                auth()->id()
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payables.show', $doc)->with('success', "Cuenta por pagar {$doc->number} generada desde {$po->po_number}.");
    }
}
