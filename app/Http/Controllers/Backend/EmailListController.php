<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EmailList;
use Illuminate\Http\Request;

class EmailListController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => EmailList::withCount('members')->latest()->paginate(15),
        ]);
    }

    public function show(EmailList $emailList)
    {
        return response()->json($emailList->load('members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'type'                  => 'required|in:static,active',
            'filter_definition'     => 'nullable|json',
            'customer_ids'          => 'nullable|array',
            'customer_ids.*'        => 'exists:customers,id',
        ]);

        $emailList = EmailList::create([
            'name'              => $data['name'],
            'type'              => $data['type'],
            'filter_definition' => !empty($data['filter_definition'])
                ? json_decode($data['filter_definition'], true)
                : null,
        ]);

        if ($data['type'] === 'static' && !empty($data['customer_ids'])) {
            $now = now();
            $emailList->members()->sync(
                collect($data['customer_ids'])->mapWithKeys(fn ($customerId) => [
                    $customerId => ['added_at' => $now],
                ])
            );
        }

        return response()->json($emailList, 201);
    }

    public function update(Request $request, EmailList $emailList)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:static,active',
            'filter_definition' => 'nullable|json',
        ]);

        $emailList->update([
            'name'              => $data['name'],
            'type'              => $data['type'],
            'filter_definition' => !empty($data['filter_definition'])
                ? json_decode($data['filter_definition'], true)
                : null,
        ]);

        return response()->json($emailList);
    }

    public function destroy(EmailList $emailList)
    {
        $emailList->delete();

        return response()->json(null, 204);
    }

    /**
     * Agrega clientes a una lista estática (no hace nada para listas dinámicas).
     */
    public function addMembers(Request $request, EmailList $emailList)
    {
        $data = $request->validate([
            'customer_ids'   => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        $now = now();
        $emailList->members()->syncWithoutDetaching(
            collect($data['customer_ids'])->mapWithKeys(fn ($customerId) => [
                $customerId => ['added_at' => $now],
            ])
        );

        return response()->json($emailList->load('members'));
    }

    public function removeMember(EmailList $emailList, Customer $customer)
    {
        $emailList->members()->detach($customer->id);

        return response()->json($emailList->load('members'));
    }

    /**
     * Precarga de clientes activos para el buscador estático de la lista
     * (mismo patrón sin-AJAX ya usado en email-lists/create.blade.php).
     */
    public function customersForPicker()
    {
        return response()->json(
            Customer::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email', 'company'])
        );
    }

    /**
     * Conteo en vivo de destinatarios para una lista dinámica ("activa"),
     * sin necesidad de persistir el registro. EmailList::resolveRecipients()
     * solo lee $this->type/$this->filter_definition (atributos en memoria)
     * y nunca $this->id, así que un modelo sin guardar funciona igual.
     *
     * NOTA: el operador que llega en la request está en español (mismo
     * vocabulario del mockup), pero WorkflowConditionEvaluator/el fallback
     * de resolveRecipients() solo reconocen operadores en inglés
     * (equals/not_equals/greater_than/less_than/contains) — cualquier otro
     * valor cae en su "default => false", produciendo un conteo de 0
     * siempre. Se traduce aquí antes de construir el filtro para que el
     * conteo estimado sea real.
     */
    public function estimateRecipients(Request $request)
    {
        $data = $request->validate([
            'field'    => 'required|string',
            'operator' => 'required|in:igual,distinto,mayor,menor,contiene',
            'value'    => 'nullable|string',
        ]);

        $operatorMap = [
            'igual'    => 'equals',
            'distinto' => 'not_equals',
            'mayor'    => 'greater_than',
            'menor'    => 'less_than',
            'contiene' => 'contains',
        ];

        $tmp = new EmailList([
            'type'              => 'active',
            'filter_definition' => [[
                'field'    => $data['field'],
                'operator' => $operatorMap[$data['operator']],
                'value'    => $data['value'] ?? null,
            ]],
        ]);

        return response()->json(['count' => $tmp->resolveRecipients()->count()]);
    }

    /**
     * Lista mínima {id,name} para poblar <select> en Campañas/Secuencias.
     */
    public function options()
    {
        return response()->json(EmailList::orderBy('name')->get(['id', 'name']));
    }
}
