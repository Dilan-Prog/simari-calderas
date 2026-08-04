<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContactEmergency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
// FIX #5: Added Rule import to build a proper CURP format/uniqueness rule
// (regex + exact size), replacing the old plain string-based unique rule.
use Illuminate\Validation\Rule;

class UserManageController extends Controller
{
    public function index()
    {
        // FIX QA: 'created_at' was missing from this explicit column select,
        // so index.blade.php's join-date column silently rendered blank for
        // every user (the value was genuinely null on this partial-select
        // model, not just unformatted).
        $users = User::with(['role:id,name_role_es', 'contactEmergency'])
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'role_id',
                'status',
                'rfc',
                'curp',
                'social_segurity_number',
                'phone',
                'position',
                'created_at'
            ]);

        $roles = Role::select('id', 'name_role_es')->get();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'users.index')
            ->value('columns');

        return view('admin.users.index', compact('users', 'roles', 'visibleColumns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            // FIX #2: Changed 'nullable' to 'required' — the `position` column
            // is NOT NULL in the DB, so a nullable rule let empty values through
            // Laravel validation and crash the INSERT with a silent 500 QueryException.
            'position' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:active,inactive,suspended',
            'rfc' => ['required', 'string', 'regex:/^[A-Z0-9]{12,13}$/', 'unique:users,rfc'],
            // FIX #5: Added exact size + official CURP regex format. The old rule
            // only capped length at 18 with no format check, so a request bypassing
            // the JS input mask (direct API call, pasted value) could save an
            // incomplete or malformed CURP.
            'curp' => ['nullable', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', Rule::unique('users', 'curp')],
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number',
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
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->position = $request->position;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->rfc = $request->rfc;
        $user->curp = $request->curp;
        $user->social_segurity_number = $request->social_segurity_number;
        $user->role_id = $request->role_id;
        $user->password = bcrypt($request->password);
        $user->save();
        if ($request->has('emergency_contact_name')) {
            foreach ($request->emergency_contact_name as $index => $name) {
                if (! empty($name)) {
                    $contactEmergency = new ContactEmergency;
                    $contactEmergency->user_id = $user->id;
                    $contactEmergency->name = $name;
                    $contactEmergency->phone = $request->emergency_phone[$index] ?? null;
                    $contactEmergency->relationship = $request->relationship[$index] ?? null;
                    $contactEmergency->save();
                }
            }
        }
        return response()->json([
            'success' => true,
            'user'    => $user->load('role'),
        ]);
    }

    public function show(string $id)
    {
        $user = User::with(['role:id,name_role_es', 'contactEmergency'])
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            // FIX #2: Changed 'nullable' to 'required' — the `position` column
            // is NOT NULL in the DB, so a nullable rule let empty values through
            // Laravel validation and crash the UPDATE with a silent 500 QueryException.
            'position' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:active,inactive,suspended',
            'rfc' => 'required|string|max:15|unique:users,rfc,' . $id,
            // FIX #5: Added exact size + official CURP regex format. The old rule
            // only capped length at 18 with no format check, so a request bypassing
            // the JS input mask (direct API call, pasted value) could save an
            // incomplete or malformed CURP.
            'curp' => ['nullable', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', Rule::unique('users', 'curp')->ignore($id)],
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number,' . $id,
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
            'emergency_contact_name' => 'nullable|array',
            'emergency_contact_name.*' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|array',
            'emergency_phone.*' => 'nullable|string|max:30',
            'relationship' => 'nullable|array',
            'relationship.*' => 'nullable|string|max:50',
        ]);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->position = $request->position;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->rfc = $request->rfc;
        $user->curp = $request->curp;
        $user->social_segurity_number = $request->social_segurity_number;
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

        return response()->json([
            'success' => true,
            'user' => $user->load(['role:id,name_role_es', 'contactEmergency']),
        ]);
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
