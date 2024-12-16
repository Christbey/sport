<!-- resources/views/livewire/role-management.blade.php -->
<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-medium text-gray-900">Roles</h3>
            <p class="mt-1 text-sm text-gray-600">
                Manage system roles and their permissions.
            </p>
        </div>
    </div>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 sm:p-6 bg-white shadow sm:rounded-lg">
            @if (session()->has('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4">
                <x-button wire:click="$set('showCreateModal', true)">
                    Add Role
                </x-button>
            </div>

            <div class="mt-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Permissions
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($roles as $role)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $role->name }}
                            </td>
                            <td class="px-6 py-4">
                                @foreach($rolePermissions->get($role->id, []) as $permission)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-1">
                                            {{ $permission->permission_name }}
                                        </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <x-button type="button"
                                          wire:click="startEditing({{ $role->id }})"
                                          class="mr-2">
                                    Edit
                                </x-button>
                                <x-button type="button"
                                          wire:click="cloneRole({{ $role->id }})"
                                          class="mr-2">
                                    Clone
                                </x-button>
                                <x-danger-button wire:click="deleteRole({{ $role->id }})"
                                                 wire:confirm="Are you sure you want to delete this role?">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <x-dialog-modal wire:model="showCreateModal">
        <x-slot name="title">
            Create Role
        </x-slot>

        <x-slot name="content">
            <div class="mt-4">
                <x-label for="name" value="Role Name"/>
                <x-input id="name"
                         type="text"
                         class="mt-1 block w-full"
                         wire:model="name"/>
                <x-input-error for="name" class="mt-2"/>
            </div>

            <div class="mt-4">
                <x-label value="Permissions"/>
                <div class="mt-2 grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded">
                    @foreach($permissions as $permission)
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   wire:model="selectedPermissions"
                                   value="{{ $permission->id }}"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="selectedPermissions" class="mt-2"/>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCreateModal', false)"
                                wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-3"
                      wire:click="create"
                      wire:loading.attr="disabled">
                Create
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Edit Modal -->
    <x-dialog-modal wire:model="showEditModal">
        <x-slot name="title">
            Edit Role
        </x-slot>

        <x-slot name="content">
            <div class="mt-4">
                <x-label for="name" value="Role Name"/>
                <x-input id="name"
                         type="text"
                         class="mt-1 block w-full"
                         wire:model="name"/>
                <x-input-error for="name" class="mt-2"/>
            </div>

            <div class="mt-4">
                <x-label value="Permissions"/>
                <div class="mt-2 grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded">
                    @foreach($permissions as $permission)
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   wire:model="selectedPermissions"
                                   value="{{ $permission->id }}"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="selectedPermissions" class="mt-2"/>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditModal', false)"
                                wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-3"
                      wire:click="update"
                      wire:loading.attr="disabled">
                Update
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
