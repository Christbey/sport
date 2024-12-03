<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        $roles = Role::all();
        return view('user-roles.index', compact('users', 'roles'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('user-roles.edit', compact('user', 'roles', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array'
        ]);

        try {
            DB::beginTransaction();
            $user->syncRoles($request->roles ?? []);
            DB::commit();

            return redirect()->route('user-roles.index')
                ->with('success', 'User roles updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update user roles: ' . $e->getMessage());
        }
    }
}