<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6 text-center">{{ ucwords(str_replace('-', ' ', $rankingType)) }} Rankings</h2>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">Rank</th>
                    <th class="px-6 py-3 text-left">Team</th>
                    <th class="px-6 py-3 text-right">Rating</th>

                    {{-- Conditionally render v 1-5, v 6-10, v 11-16 columns --}}
                    @if(isset($rows[0]['v_1_5']))
                        <th class="px-6 py-3 text-right">v 1-5</th>
                        <th class="px-6 py-3 text-right">v 6-10</th>
                        <th class="px-6 py-3 text-right">v 11-16</th>
                    @endif

                    <th class="px-6 py-3 text-right">High</th>
                    <th class="px-6 py-3 text-right">Low</th>
                    <th class="px-6 py-3 text-right">Last</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $row['rank'] }}</td>
                        <td class="px-6 py-4"><a href="{{ $row['team_link'] }}" class="text-blue-600 hover:text-blue-800">{{ $row['team'] }}</a></td>
                        <td class="px-6 py-4 text-right">{{ $row['rating'] }}</td>

                        {{-- Conditionally display v 1-5, v 6-10, and v 11-16 if they exist --}}
                        @if(isset($row['v_1_5']))
                            <td class="px-6 py-4 text-right">{{ $row['v_1_5'] }}</td>
                            <td class="px-6 py-4 text-right">{{ $row['v_6_10'] }}</td>
                            <td class="px-6 py-4 text-right">{{ $row['v_11_16'] }}</td>
                        @endif

                        <td class="px-6 py-4 text-right">{{ $row['high'] }}</td>
                        <td class="px-6 py-4 text-right">{{ $row['low'] }}</td>
                        <td class="px-6 py-4 text-right">{{ $row['last'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
