<!-- resources/views/user-roles/edit.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Roles</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Edit Roles for {{ $user->name }}</h2>
        </div>

        <div class="p-6">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('user-roles.update', ['user' => $user->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Roles
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg">
                        @foreach($roles as $role)
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="role_{{ $role->id }}"
                                       name="roles[]"
                                       value="{{ $role->name }}"
                                       {{ in_array($role->name, $userRoles) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="role_{{ $role->id }}"
                                       class="ml-2 block text-sm text-gray-900">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('user-roles.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>