<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Traits\NormalizesProductFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierProductBulkEditController extends Controller
{
    use NormalizesProductFields;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200, 300, 400, 500];

    private const BULK_EDIT_FIELDS = ['sku', 'cost', 'lead_time_days', 'is_primary'];

    private function filteredSupplierProductsQuery(Request $request): Builder
    {
        $query = SupplierProduct::query()->with(['supplier:id,company_name,status', 'product:id,name,sku']);

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id');
    }

    public function index(Request $request)
    {
        $query = $this->filteredSupplierProductsQuery($request);

        $totalFiltered = (clone $query)->count();
        $perPageInput  = $request->input('per_page', 25);
        $showAll       = $perPageInput === 'all';
        $perPage       = $showAll ? $totalFiltered : (int) $perPageInput;

        $links = $showAll
            ? $query->get()
            : $query->paginate(max($perPage, 1))->withQueryString();

        // Todos los estados, no solo activos — un admin gestionando vínculos
        // ya existentes también necesita encontrar los de un proveedor
        // suspendido/inactivo.
        $suppliers = Supplier::orderBy('company_name')->get(['id', 'company_name', 'status']);

        return view('admin.supplier.bulk_edit_products', compact('links', 'suppliers', 'totalFiltered'))
            ->with('perPageOptions', self::PER_PAGE_OPTIONS);
    }

    /**
     * Valida y sanea un cambio individual. Devuelve [ok, valorLimpio,
     * mensajeDeError] — nunca lanza, para que un cambio inválido no tumbe
     * los demás cambios del mismo guardado (mismo contrato que
     * ProductController::validateBulkEditChange()).
     */
    private function validateBulkEditChange(string $field, $value): array
    {
        switch ($field) {
            case 'sku':
                $v = trim((string) $value);
                if (mb_strlen($v) > 100) {
                    return [false, null, 'Máximo 100 caracteres.'];
                }

                return [true, $v === '' ? null : $v, null];

            case 'cost':
                if ($value === null || trim((string) $value) === '') {
                    return [true, null, null];
                }
                $clean = $this->sanitizePrice($value);
                if ($clean === null || $clean < 0) {
                    return [false, null, 'No es un número válido.'];
                }

                return [true, $clean, null];

            case 'lead_time_days':
                if ($value === null || trim((string) $value) === '') {
                    return [true, null, null];
                }
                if (!is_numeric($value) || (int) $value < 0) {
                    return [false, null, 'Debe ser un número entero ≥ 0.'];
                }

                return [true, (int) $value, null];

            case 'is_primary':
                return [true, (bool) $value, null];
        }

        return [false, null, 'Campo no soportado.'];
    }

    public function save(Request $request)
    {
        $request->validate([
            'changes' => 'required|array|min:1',
            'changes.*.id' => 'required|integer|exists:suppliers_products,id',
            'changes.*.field' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!in_array($value, self::BULK_EDIT_FIELDS, true)) {
                    $fail('Campo no soportado.');
                }
            }],
        ]);

        $results = [];
        $validByPivotId = [];

        foreach ($request->input('changes') as $change) {
            $id = (int) $change['id'];
            $field = $change['field'];
            [$ok, $clean, $error] = $this->validateBulkEditChange($field, $change['value'] ?? null);

            if ($ok) {
                $validByPivotId[$id][$field] = $clean;
            }

            $results[] = ['id' => $id, 'field' => $field, 'ok' => $ok, 'error' => $error];
        }

        if (!empty($validByPivotId)) {
            DB::transaction(function () use ($validByPivotId) {
                foreach ($validByPivotId as $id => $fields) {
                    $pivot = SupplierProduct::find($id);
                    if (!$pivot) {
                        continue;
                    }
                    foreach ($fields as $field => $value) {
                        $pivot->{$field} = $value;
                    }
                    $pivot->save();

                    // Si esta fila quedó como principal, se demueven las
                    // demás del MISMO producto — incluyendo otra fila del
                    // mismo lote si también se marcó principal (gana la
                    // última procesada, mismo criterio de "última escritura
                    // gana" que ya usa la edición individual vía
                    // SupplierProductController::update()).
                    if (($fields['is_primary'] ?? false) === true) {
                        SupplierProduct::demoteOtherPrimaries($pivot->product_id, $pivot->id);
                    }
                }
            });
        }

        return response()->json(['success' => true, 'results' => $results]);
    }
}
