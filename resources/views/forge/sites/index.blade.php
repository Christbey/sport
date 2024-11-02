<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Sites for Server {{ $serverId }}</h1>
        <div class="overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 bg-white rounded-md shadow-md">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="py-3 px-6">ID</th>
                    <th scope="col" class="py-3 px-6">Name</th>
                    <th scope="col" class="py-3 px-6">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sites as $site)
                    <tr class="border-b">
                        <td class="py-4 px-6">{{ $site['id'] ?? 'N/A' }}</td>
                        <td class="py-4 px-6">{{ $site['name'] ?? 'Unnamed Site' }}</td>
                        <td class="py-4 px-6">
                            @if(isset($site['id']))
                                <form action="{{ route('forge.commands.run', [$serverId, $site['id']]) }}"
                                      method="POST">
                                    @csrf
                                    <input type="text" name="command" placeholder="Enter command"
                                           class="p-2 border rounded-md" required>
                                    <button type="submit"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 ml-2">
                                        Run Command
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-500">No Actions Available</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-4 px-6 text-center text-gray-500">No sites found for this server.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
