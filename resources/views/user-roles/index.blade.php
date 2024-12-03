<!-- resources/views/user-roles/index.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Roles</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">User Roles Management</h2>
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
                        User
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Roles
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4">
                            @foreach($user->roles as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-1">
                                            {{ $role->name }}
                                        </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="openEditModal('{{ $user->id }}', '{{ $user->name }}', {{ $user->roles->pluck('id') }})"
                                    class="text-blue-600 hover:text-blue-900">
                                Edit Roles
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="modalTitle">Edit User Roles</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editForm" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Roles for <span id="userName" class="text-blue-600"></span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded">
                        @foreach($roles as $role)
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="role_{{ $role->id }}"
                                       name="roles[]"
                                       value="{{ $role->name }}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="role_{{ $role->id }}"
                                       class="ml-2 text-sm text-gray-900">
                                    {{ $role->name }}
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
                        Update Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditModal(userId, userName, userRoles) {
        // Set modal title and form action
        document.getElementById('userName').textContent = userName;
        document.getElementById('editForm').action = `/user-roles/${userId}`;

        // Reset all checkboxes
        document.querySelectorAll('[id^="role_"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Check the roles that the user has
        userRoles.forEach(roleId => {
            const checkbox = document.getElementById(`role_${roleId}`);
            if (checkbox) checkbox.checked = true;
        });

        // Show modal
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        // Reset form
        document.querySelectorAll('[id^="role_"]').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
</script>
</body>
</html>