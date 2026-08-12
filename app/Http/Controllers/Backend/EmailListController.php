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
        $emailLists = EmailList::withCount('members')->latest()->paginate(15);

        return view('admin.email-lists.index', compact('emailLists'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();

        return view('admin.email-lists.create', compact('customers'));
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

        return redirect()->route('admin.email-lists.index')
            ->with('success', "Lista \"{$emailList->name}\" creada exitosamente.");
    }

    public function edit(EmailList $emailList)
    {
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();
        $emailList->load('members');

        return view('admin.email-lists.edit', compact('emailList', 'customers'));
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

        return redirect()->route('admin.email-lists.index')
            ->with('success', "Lista \"{$emailList->name}\" actualizada exitosamente.");
    }

    public function destroy(EmailList $emailList)
    {
        $emailList->delete();

        return redirect()->route('admin.email-lists.index')
            ->with('success', "Lista \"{$emailList->name}\" eliminada.");
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

        return redirect()->route('admin.email-lists.edit', $emailList)
            ->with('success', 'Clientes agregados a la lista.');
    }

    public function removeMember(EmailList $emailList, Customer $customer)
    {
        $emailList->members()->detach($customer->id);

        return redirect()->route('admin.email-lists.edit', $emailList)
            ->with('success', 'Cliente eliminado de la lista.');
    }
}
