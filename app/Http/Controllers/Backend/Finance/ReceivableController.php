<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Models\Receivable;
use App\Models\SalesOrder;
use App\Support\FinanceOriginOptions;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    protected function formData($doc, ?Request $request = null): array
    {
        return parent::formData($doc, $request) + [
            // Selects de documento de origen y proyecto (en vez de teclear números internos).
            'origins'  => FinanceOriginOptions::receivableOrigins($doc),
            'projects' => FinanceOriginOptions::projects($doc),
        ];
    }

    /**
     * El formulario manda un solo select "tipo:id" (source_document); se convierte a source_type + source_id
     * (los campos que ya usaban la validación y el servicio). Sin select, se respetan los campos directos.
     */
    private function mergeOrigin(Request $request): void
    {
        if (! $request->has('source_document')) {
            return;
        }

        $raw = trim((string) $request->input('source_document'));
        $origin = FinanceOriginOptions::parseOrigin($raw);

        if ($raw !== '' && (! $origin || ! (Receivable::SOURCE_TYPES[$origin['type']])::find($origin['id']))) {
            throw ValidationException::withMessages(['source_document' => 'El documento de origen elegido ya no existe.']);
        }

        $request->merge(['source_type' => $origin['type'] ?? null, 'source_id' => $origin['id'] ?? null]);
    }

    public function store(Request $request)
    {
        $this->mergeOrigin($request);
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
        $this->mergeOrigin($request);
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
