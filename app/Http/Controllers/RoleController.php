<?php

namespace App\Http\Controllers;

use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create(['name' => $request->name]);
            $role->syncPermissions($request->permissions ?? []);

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'array'
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $request->name]);

            // Convert permission IDs to Permission objects
            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        try {
            // Check if role has any users
            if ($role->users()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete role with assigned users');
            }

            // Protect super-admin or other critical roles
            if ($role->name === 'super-admin' || $role->name === 'admin') {
                return redirect()->back()
                    ->with('error', 'Cannot delete system roles');
            }

            DB::beginTransaction();

            // Remove all permissions before deleting
            $role->syncPermissions([]);
            $role->delete();

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    // Optional: Add a method to show role details
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        return view('roles.show', compact('role'));
    }

    // Optional: Add a method to clone a role
    public function clone(Role $role)
    {
        try {
            DB::beginTransaction();

            $newRole = Role::create([
                'name' => $role->name . ' (copy)'
            ]);

            $newRole->syncPermissions($role->permissions);

            DB::commit();

            return redirect()->route('roles.edit', $newRole)
                ->with('success', 'Role cloned successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to clone role: ' . $e->getMessage());
        }
    }
}