<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionManagement extends Component
{
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingPermission = null;
    public $name = '';

    protected $rules = [
        'name' => 'required|string|max:255|unique:permissions,name'
    ];

    public function create()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            Permission::create(['name' => $this->name]);

            DB::commit();

            $this->reset('name', 'showCreateModal');
            session()->flash('success', 'Permission created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    public function startEditing($permissionId)
    {
        $this->editingPermission = $permissionId;
        $permission = Permission::findOrFail($permissionId);
        $this->name = $permission->name;
        $this->showEditModal = true;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->editingPermission
        ]);

        try {
            DB::beginTransaction();

            $permission = Permission::findOrFail($this->editingPermission);
            $permission->update(['name' => $this->name]);

            DB::commit();

            $this->reset('name', 'showEditModal', 'editingPermission');
            session()->flash('success', 'Permission updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    public function delete($permissionId)
    {
        try {
            DB::beginTransaction();

            $hasRoles = DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->exists();

            if ($hasRoles) {
                DB::rollBack();
                session()->flash('error', 'Cannot delete permission as it is assigned to roles.');
                return;
            }

            DB::table('permissions')->where('id', $permissionId)->delete();

            DB::commit();
            session()->flash('success', 'Permission deleted successfully');

        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.permission-management', [
            'permissions' => Permission::with('roles')->orderBy('name')->get()
        ]);
    }
}