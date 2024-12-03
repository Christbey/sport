<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('permissions.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            Permission::create(['name' => $request->name]);

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permission created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id
        ]);

        try {
            DB::beginTransaction();

            $permission->update(['name' => $request->name]);

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permission updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            DB::beginTransaction();

            // Check if permission is being used by any roles
            if ($permission->roles->count() > 0) {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot delete permission as it is assigned to roles.');
            }

            $permission->delete();

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permission deleted successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    // Optional: Method to bulk create permissions
    public function bulkStore(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|max:255|distinct|unique:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->permissions as $permissionName) {
                Permission::create(['name' => $permissionName]);
            }

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with('success', 'Permissions created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create permissions: ' . $e->getMessage());
        }
    }

    // Optional: Method to get permissions via AJAX
    public function getPermissions(Request $request)
    {
        $query = Permission::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $permissions = $query->orderBy('name')->get();

        return response()->json($permissions);
    }
}