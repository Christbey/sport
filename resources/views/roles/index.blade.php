<!-- resources/views/roles/index.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Roles</h2>
                <button onclick="openCreateModal()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Add Role
                </button>
            </div>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

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
                        <td class="px-6 py-4 whitespace-nowrap">{{ $role->name }}</td>
                        <td class="px-6 py-4">
                            @foreach($role->permissions as $permission)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-1">
                                            {{ $permission->name }}
                                        </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="openEditModal('{{ $role->id }}', '{{ $role->name }}', {{ $role->permissions->pluck('id') }})"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </button>
                            <form action="{{ route('roles.destroy', $role) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-semibold mb-4">Create Role</h3>
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="create_name" class="block text-gray-700 text-sm font-bold mb-2">
                        Role Name
                    </label>
                    <input type="text"
                           name="name"
                           id="create_name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Permissions
                    </label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded">
                        @foreach($permissions as $permission)
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="create_permission_{{ $permission->id }}"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="create_permission_{{ $permission->id }}"
                                       class="ml-2 text-sm text-gray-900">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeCreateModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h3 class="text-lg font-semibold mb-4">Edit Role</h3>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label for="edit_name" class="block text-gray-700 text-sm font-bold mb-2">
                        Role Name
                    </label>
                    <input type="text"
                           name="name"
                           id="edit_name"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Permissions
                    </label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded">
                        @foreach($permissions as $permission)
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="edit_permission_{{ $permission->id }}"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="edit_permission_{{ $permission->id }}"
                                       class="ml-2 text-sm text-gray-900">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeEditModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('create_name').value = '';
        // Uncheck all permissions
        document.querySelectorAll('[id^="create_permission_"]').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    function openEditModal(id, name, permissions) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('edit_name').value = name;
        document.getElementById('editForm').action = `/roles/${id}`;

        // Reset all permissions first
        document.querySelectorAll('[id^="edit_permission_"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Check the permissions that the role has
        permissions.forEach(permissionId => {
            const checkbox = document.getElementById(`edit_permission_${permissionId}`);
            if (checkbox) checkbox.checked = true;
        });
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('edit_name').value = '';
        // Uncheck all permissions
        document.querySelectorAll('[id^="edit_permission_"]').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    // Close modals when clicking outside
    window.onclick = function (event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
</script>
</body>
</html>