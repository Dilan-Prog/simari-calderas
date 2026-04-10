<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContactEmergency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserManageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $users = User::with('role:id,name_role_es')
        ->get(['id', 'first_name', 'last_name', 'email', 'role_id', 'status']);

        $roles = Role::select('id', 'name_role_es')->get();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'position' => 'nullable|string|max:150',
            // 'avatar_url' => 'nullable|url|max:255',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:1,2,3,active,inactive,suspended',
            'rfc' => 'required|string|max:15|unique:users,rfc',
            'curp' => 'nullable|string|max:18|unique:users,curp',
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number',
            'birthdate' => 'nullable|date',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
            // contact emergency
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:50',
        ]);

        $user = new User();
        $user->name = trim($request->first_name . ' ' . $request->last_name);
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
        if($request->filled('emergency_contact_name') || $request->filled('emergency_contact_phone') || $request->filled('emergency_contact_relationship')){
            $contactEmergency = new ContactEmergency();
            $contactEmergency->user_id = $user->id;
            $contactEmergency->name = $request->emergency_contact_name;
            $contactEmergency->phone = $request->emergency_contact_phone;
            $contactEmergency->relationship = $request->emergency_contact_relationship;
            $contactEmergency->save();
        }
        Log::info('Usuario creado: ' . $user->id . ' - ' . $user->first_name . ' ' . $user->last_name);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
