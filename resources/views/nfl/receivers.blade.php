<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Top Receivers</h1>

        @if(isset($error))
            <p class="text-red-500">{{ $error }}</p>
        @else
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Player</th>
                        <th scope="col" class="px-6 py-3">Team</th>
                        <th scope="col" class="px-6 py-3">Total Yards</th>
                        <th scope="col" class="px-6 py-3">Receptions</th>
                        <th scope="col" class="px-6 py-3">Touchdowns</th>
                    </tr>
                    </thead>
                    <tbody>
                    {{-- Loop over the 'data' key of the paginated response --}}
                    @foreach($receivers['data'] as $receiver)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">{{ $receiver['long_name'] }}</td>
                            <td class="px-6 py-4">{{ $receiver['team_abv'] }}</td>
                            <td class="px-6 py-4">{{ $receiver['total_receiving_yards'] }}</td>
                            <td class="px-6 py-4">{{ $receiver['total_receptions'] }}</td>
                            <td class="px-6 py-4">{{ $receiver['total_recTDs'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Add pagination links --}}
            <div class="mt-6 flex justify-center ">
                @if(request()->get('page', 1) > 1)
                    <a href="{{ url()->current() }}?page={{ request()->get('page', 1) - 1 }}" class="mr-4 bg-blue-500 hover:bg-blue-700 text-gray-800 font-bold py-2 px-4 rounded">
                        Previous
                    </a>
                @endif
                @if($receivers['next_page_url'])
                    <a href="{{ url()->current() }}?page={{ request()->get('page', 1) + 1 }}" class="bg-blue-500 hover:bg-blue-700 text-gray-800 font-bold py-2 px-4 rounded">
                        Next
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
