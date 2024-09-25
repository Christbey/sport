<x-app-layout>
    <div class="container mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6 text-center">{{ ucwords(str_replace('-', ' ', $rankingType)) }} Rankings</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white shadow-md rounded-lg">
                <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-6 text-left">Rank</th>
                    <th class="py-3 px-6 text-left">Team</th>
                    <th class="py-3 px-6 text-right">Rating</th>

                    {{-- Conditionally render v 1-5, v 6-10, v 11-16 columns --}}
                    @if(isset($rows[0]['v_1_5']))
                        <th class="py-3 px-6 text-right">v 1-5</th>
                        <th class="py-3 px-6 text-right">v 6-10</th>
                        <th class="py-3 px-6 text-right">v 11-16</th>
                    @endif

                    <th class="py-3 px-6 text-right">High</th>
                    <th class="py-3 px-6 text-right">Low</th>
                    <th class="py-3 px-6 text-right">Last</th>
                </tr>
                </thead>
                <tbody class="text-gray-700">
                @foreach($rows as $row)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6">{{ $row['rank'] }}</td>
                        <td class="py-3 px-6"><a href="{{ $row['team_link'] }}" class="text-blue-600 hover:text-blue-800">{{ $row['team'] }}</a></td>
                        <td class="py-3 px-6 text-right">{{ $row['rating'] }}</td>

                        {{-- Conditionally display v 1-5, v 6-10, and v 11-16 if they exist --}}
                        @if(isset($row['v_1_5']))
                            <td class="py-3 px-6 text-right">{{ $row['v_1_5'] }}</td>
                            <td class="py-3 px-6 text-right">{{ $row['v_6_10'] }}</td>
                            <td class="py-3 px-6 text-right">{{ $row['v_11_16'] }}</td>
                        @endif

                        <td class="py-3 px-6 text-right">{{ $row['high'] }}</td>
                        <td class="py-3 px-6 text-right">{{ $row['low'] }}</td>
                        <td class="py-3 px-6 text-right">{{ $row['last'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
