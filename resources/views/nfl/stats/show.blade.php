<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Stats Results</h1>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    @foreach($tableHeadings as $heading)
                        <th scope="col" class="px-6 py-3">
                            {{ $heading }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($data as $row)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        @foreach($row as $value)
                            <td class="px-6 py-4">
                                {{ $value }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if(isset($players) && $players instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="mt-6 flex-col justify-center">
                {{ $players->appends(request()->except('page'))->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
