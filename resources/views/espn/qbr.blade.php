<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">QBR Data for Week <span id="current-week">{{ $week }}</span></h1>

        <!-- Week Selector and Submit Button -->
        <div class="mb-6">
            <label for="weekSelect" class="block text-gray-700 mb-2">Select Week:</label>
            <div class="flex items-center space-x-4">
                <select id="weekSelect"
                        class="lg:w-1/4 form-select bg-white border border-gray-300 rounded py-2 px-4 text-gray-700 focus:outline-none focus:ring focus:border-blue-300">
                    @for ($i = 1; $i <= 18; $i++)
                        <option value="{{ $i }}" {{ $i == $week ? 'selected' : '' }}>Week {{ $i }}</option>
                    @endfor
                </select>
                <button id="loadDataBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Load Data
                </button>
            </div>
        </div>

        <!-- QBR Data Table -->
        <div id="qbr-data">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left font-medium text-gray-700">Athlete</th>
                        <th class="py-2 px-4 text-left font-medium text-gray-700">Team</th>
                        <th class="py-2 px-4 text-left font-medium text-gray-700">Opponent</th>
                        <th class="py-2 px-4 text-left font-medium text-gray-700">QBR</th>
                        <th class="py-2 px-4 text-left font-medium text-gray-700">Points Added</th>
                        <th class="py-2 px-4 text-left font-medium text-gray-700">Unqualified Rank</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($processedItems as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-4">{{ $item['athlete']['name'] ?? 'N/A' }}</td>
                            <td class="py-2 px-4">{{ $item['team']['name'] ?? 'N/A' }}</td>
                            <td class="py-2 px-4">{{ $item['opponent']['name'] ?? 'N/A' }}</td>
                            @foreach($item['stats'] as $stat)
                                @if($stat['name'] === 'qbr')
                                    <td class="py-2 px-4">{{ $stat['value'] ?? 'N/A' }}</td>
                                @elseif($stat['name'] === 'qbpaa')
                                    <td class="py-2 px-4">{{ $stat['value'] ?? 'N/A' }}</td>
                                @elseif($stat['name'] === 'unqualifiedRank')
                                    <td class="py-2 px-4">{{ $stat['value'] ?? 'N/A' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript for redirecting to the selected week's URL -->
    <script>
        document.getElementById('loadDataBtn').addEventListener('click', function () {
            const selectedWeek = document.getElementById('weekSelect').value;

            // Redirect the user to the new URL
            window.location.href = `/api/nfl/qbr/${selectedWeek}`;
        });
    </script>
</x-app-layout>
