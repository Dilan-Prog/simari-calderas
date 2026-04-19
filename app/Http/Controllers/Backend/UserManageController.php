<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContactEmergency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    public function index()
    {
        $users = User::with(['role:id,name_role_es', 'contactEmergency'])
            ->get(['id', 'first_name', 'last_name', 'email', 'role_id', 'status',
                'birthdate', 'rfc', 'curp', 'social_segurity_number', 'phone', 'position']);

        $roles = Role::select('id', 'name_role_es')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'position' => 'nullable|string|max:150',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:1,2,3,active,inactive,suspended',
            'rfc' => 'required|string|max:15|unique:users,rfc',
            'curp' => 'nullable|string|max:18|unique:users,curp',
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number',
            'birthdate' => 'nullable|date',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
            'emergency_contact_name' => 'nullable|array',
            'emergency_contact_name.*' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|array',
            'emergency_phone.*' => 'nullable|string|max:30',
            'relationship' => 'nullable|array',
            'relationship.*' => 'nullable|string|max:50',
        ]);
        $user = new User;
        $user->name = trim($request->first_name.' '.$request->last_name);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->position = $request->position;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->rfc = $request->rfc;
        $user->curp = $request->curp;
        $user->social_segurity_number = $request->social_segurity_number;
        $user->birthdate = $request->birthdate;
        $user->role_id = $request->role_id;
        $user->password = bcrypt($request->password);
        $user->save();
        if ($request->has('emergency_contact_name')) {
            foreach ($request->emergency_contact_name as $index => $name) {
                // Solo guardamos si al menos el nombre del contacto tiene valor
                if (! empty($name)) {
                    $contactEmergency = new ContactEmergency;
                    $contactEmergency->user_id = $user->id;
                    $contactEmergency->name = $name;
                    // Accedemos al teléfono y parentesco usando el mismo índice ($index)
                    $contactEmergency->phone = $request->emergency_phone[$index] ?? null;
                    $contactEmergency->relationship = $request->relationship[$index] ?? null;
                    $contactEmergency->save();
                }
            }
        }
        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,'.$id,
            'position' => 'nullable|string|max:150',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:active,inactive,suspended',
            'rfc' => 'required|string|max:15|unique:users,rfc,'.$id,
            'curp' => 'nullable|string|max:18|unique:users,curp,'.$id,
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number,'.$id,
            'birthdate' => 'nullable|date',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
            'emergency_contact_name' => 'nullable|array',
            'emergency_contact_name.*' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|array',
            'emergency_phone.*' => 'nullable|string|max:30',
            'relationship' => 'nullable|array',
            'relationship.*' => 'nullable|string|max:50',
        ]);

        $user->name = trim($request->first_name.' '.$request->last_name);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->position = $request->position;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->rfc = $request->rfc;
        $user->curp = $request->curp;
        $user->social_segurity_number = $request->social_segurity_number;
        $user->birthdate = $request->birthdate;
        $user->role_id = $request->role_id;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        $user->contactEmergency()->delete();

        if ($request->has('emergency_contact_name')) {
            foreach ($request->emergency_contact_name as $index => $name) {
                if (! empty($name)) {
                    $user->contactEmergency()->create([
                        'name' => $name,
                        'phone' => $request->emergency_phone[$index] ?? null,
                        'relationship' => $request->relationship[$index] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 403, 'No puedes eliminarte a ti mismo.');
        $user->contactEmergency()->delete();
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
