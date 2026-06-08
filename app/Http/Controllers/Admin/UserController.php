<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::with('roles')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new \App\Models\User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,specialist',
        ]);
        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->assignRole($data['role']);
        return redirect()->route('admin.users.index')->with('success', 'Gebruiker aangemaakt.');
    }

    public function edit(\App\Models\User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, \App\Models\User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|in:admin,specialist',
        ]);
        $user->update(['name' => $data['name'], 'email' => $data['email']]);
        if (!empty($data['password'])) {
            $user->update(['password' => bcrypt($data['password'])]);
        }
        $user->syncRoles([$data['role']]);
        return redirect()->route('admin.users.index')->with('success', 'Gebruiker bijgewerkt.');
    }

    public function destroy(\App\Models\User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Je kunt jezelf niet verwijderen.');
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Gebruiker verwijderd.');
    }
}
