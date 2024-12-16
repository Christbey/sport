<!-- resources/views/livewire/permission-management.blade.php -->
<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-medium text-gray-900">Permissions</h3>
            <p class="mt-1 text-sm text-gray-600">
                Manage system permissions that can be assigned to roles.
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

            <div class="mb-4">
                <x-button wire:click="$set('showCreateModal', true)">
                    Add Permission
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
                            Roles Using
                        </th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($permissions as $permission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $permission->name }}
                            </td>
                            <td class="px-6 py-4">
                                @foreach($permission->roles as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                            {{ $role->name }}
                                        </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <x-button type="button"
                                          wire:click="startEditing({{ $permission->id }})"
                                          class="text-blue-600 hover:text-blue-900">
                                    Edit
                                </x-button>
                                <x-danger-button wire:click="delete({{ $permission->id }})"
                                                 wire:confirm="Are you sure you want to delete this permission?">
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
            Create Permission
        </x-slot>

        <x-slot name="content">
            <div class="mt-4">
                <x-label for="name" value="Permission Name"/>
                <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name"/>
                <x-input-error for="name" class="mt-2"/>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCreateModal', false)" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-3" wire:click="create" wire:loading.attr="disabled">
                Create
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Edit Modal -->
    <x-dialog-modal wire:model="showEditModal">
        <x-slot name="title">
            Edit Permission
        </x-slot>

        <x-slot name="content">
            <div class="mt-4">
                <x-label for="name" value="Permission Name"/>
                <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name"/>
                <x-input-error for="name" class="mt-2"/>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditModal', false)" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-3" wire:click="update" wire:loading.attr="disabled">
                Update
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>