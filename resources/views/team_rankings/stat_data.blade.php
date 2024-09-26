<!-- resources/views/team_rankings/stat_data.blade.php -->
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6">{{ ucfirst(str_replace('-', ' ', $stat)) }} Statistics</h2>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Rank</th>
                    <th scope="col" class="px-6 py-3">Team</th>
                    <th scope="col" class="px-6 py-3">2024</th>
                    <th scope="col" class="px-6 py-3">Last 3</th>
                    <th scope="col" class="px-6 py-3">Last 1</th>
                    <th scope="col" class="px-6 py-3">Home</th>
                    <th scope="col" class="px-6 py-3">Away</th>
                    <th scope="col" class="px-6 py-3">2023</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $row['rank'] }}</td>
                        <td class="px-6 py-4"><a href="{{ $row['team_link'] }}">{{ $row['team'] }}</a></td>
                        <td class="px-6 py-4">{{ $row['2024'] }}</td>
                        <td class="px-6 py-4">{{ $row['last_3'] }}</td>
                        <td class="px-6 py-4">{{ $row['last_1'] }}</td>
                        <td class="px-6 py-4">{{ $row['home'] }}</td>
                        <td class="px-6 py-4">{{ $row['away'] }}</td>
                        <td class="px-6 py-4">{{ $row['2023'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
