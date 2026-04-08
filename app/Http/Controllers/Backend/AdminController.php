<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Log;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function login() {
        return view('admin.auth.login');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'position' => 'nullable|string|max:150',
            // 'avatar_url' => 'nullable|url|max:255',
            'phone' => 'required|string|max:30',
            'status' => 'required|in:active,inactive,suspended',
            'rfc' => 'required|string|max:15|unique:users,rfc',
            'curp' => 'nullable|string|max:18|unique:users,curp',
            'social_segurity_number' => 'nullable|string|max:20|unique:users,social_segurity_number',
            'birthdate' => 'nullable|date',
            // 'id_contact_emergency' => 'nullable|integer|exists:users,id',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = new User();
        $user->name = trim($request->first_name . ' ' . $request->last_name);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->position = $request->position;
        // $user->avatar_url = $request->avatar_url;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->rfc = $request->rfc;
        $user->curp = $request->curp;
        $user->social_segurity_number = $request->social_segurity_number;
        $user->birthdate = $request->birthdate;
        // $user->id_contact_emergency = $request->id_contact_emergency;
        $user->role_id = $request->role_id;
        $user->password = bcrypt($request->password);
        // dd($request->all());
        $user->save();
        // Log::info('Usuario creado: ' . $user->id . ' - ' . $user->first_name . ' ' . $user->last_name);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }
}
