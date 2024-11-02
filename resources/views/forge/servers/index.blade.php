<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Servers</h1>
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
                @foreach($servers as $server)
                    <tr class="border-b">
                        <td class="py-4 px-6">{{ $server['id'] ?? 'N/A' }}</td>
                        <td class="py-4 px-6">{{ $server['name'] ?? 'Unnamed Server' }}</td>
                        <td class="py-4 px-6">
                            @if(isset($server['id']))
                                <a href="{{ route('forge.sites.index', $server['id']) }}"
                                   class="text-blue-600 hover:underline">View Sites</a>
                            @else
                                <span class="text-gray-500">No Actions Available</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
