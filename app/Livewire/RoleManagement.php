<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagement extends Component
{
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingRole = null;
    public $name = '';
    public $selectedPermissions = [];

    public function create()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);

            DB::commit();

            $this->reset('name', 'selectedPermissions', 'showCreateModal');
            session()->flash('success', 'Role created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function startEditing($roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->editingRole = $roleId;
        $this->name = $role->name;
        $this->selectedPermissions = DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();
        $this->showEditModal = true;
    }

    public function update()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $role = Role::findOrFail($this->editingRole);
            $role->update(['name' => $this->name]);

            DB::table('role_has_permissions')->where('role_id', $this->editingRole)->delete();

            if (!empty($this->selectedPermissions)) {
                $permissions = array_map(function ($permissionId) {
                    return [
                        'permission_id' => $permissionId,
                        'role_id' => $this->editingRole
                    ];
                }, $this->selectedPermissions);

                DB::table('role_has_permissions')->insert($permissions);
            }

            DB::commit();

            $this->reset('name', 'selectedPermissions', 'showEditModal', 'editingRole');
            session()->flash('success', 'Role updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function deleteRole($roleId)
    {
        try {
            DB::beginTransaction();

            $usersCount = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->count();

            if ($usersCount > 0) {
                session()->flash('error', 'Cannot delete role with assigned users');
                return;
            }

            $role = Role::findOrFail($roleId);
            if (in_array($role->name, ['super-admin', 'admin'])) {
                session()->flash('error', 'Cannot delete system roles');
                return;
            }

            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();

            DB::commit();
            session()->flash('success', 'Role deleted successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    public function cloneRole($roleId)
    {
        try {
            DB::beginTransaction();

            $originalRole = Role::findOrFail($roleId);

            $newRoleId = DB::table('roles')->insertGetId([
                'name' => $originalRole->name . ' (copy)',
                'guard_name' => 'web'
            ]);

            $permissions = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();

            if (!empty($permissions)) {
                $rolePermissions = array_map(function ($permissionId) use ($newRoleId) {
                    return [
                        'permission_id' => $permissionId,
                        'role_id' => $newRoleId
                    ];
                }, $permissions);

                DB::table('role_has_permissions')->insert($rolePermissions);
            }

            DB::commit();
            session()->flash('success', 'Role cloned successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to clone role: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $roles = DB::table('roles')
            ->select('roles.id', 'roles.name')
            ->selectSub(function ($query) {
                $query->from('model_has_roles')
                    ->whereColumn('model_has_roles.role_id', 'roles.id')
                    ->selectRaw('count(*)');
            }, 'users_count')
            ->get();

        $rolePermissions = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('role_has_permissions.role_id', 'permissions.id as permission_id', 'permissions.name as permission_name')
            ->get()
            ->groupBy('role_id');

        return view('livewire.role-management', [
            'roles' => $roles,
            'rolePermissions' => $rolePermissions,
            'permissions' => Permission::orderBy('name')->get()
        ]);
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name' . ($this->editingRole ? ',' . $this->editingRole : ''),
            'selectedPermissions' => 'array'
        ];
    }
}