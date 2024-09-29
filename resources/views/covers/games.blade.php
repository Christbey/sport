<x-app-layout>
    <div class="container mx-auto p-4">
        <h2 class="text-2xl font-bold mb-4">Available NFL Games</h2>

        <!-- Dropdown to select a game -->
        <div class="mb-4">
            <label for="gameSelect" class="block text-sm font-medium text-gray-700">Select a Game</label>
            <select id="gameSelect"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">-- Select a game --</option>
                @foreach($games as $game)
                    <option value="{{ $game['covers_game_id'] }}">
                        {{ $game['away_team'] }} @ {{ $game['home_team'] }} - {{ $game['game_time'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Section to display game details -->
        <div id="gameDetails" class="mt-4">

            <!-- Table to display game trends -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trend
                        </th>
                    </tr>
                    </thead>
                    <tbody id="gameTrendsList" class="bg-white divide-y divide-gray-200">
                    <!-- Trends will be dynamically inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('gameSelect').addEventListener('change', function () {
            const coversGameId = this.value;

            if (coversGameId) {
                fetch(`/api/covers/game/${coversGameId}`)
                    .then(response => response.json())
                    .then(data => {
                        const gameTrendsList = document.getElementById('gameTrendsList');
                        gameTrendsList.innerHTML = ''; // Clear previous data

                        if (data.trends && data.trends.length > 0) {
                            data.trends.forEach(trend => {
                                const row = document.createElement('tr');
                                const cell = document.createElement('td');
                                cell.classList.add('px-6', 'py-4', 'whitespace-nowrap', 'text-sm', 'text-gray-900');
                                cell.textContent = trend;
                                row.appendChild(cell);
                                gameTrendsList.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            const cell = document.createElement('td');
                            cell.classList.add('px-6', 'py-4', 'whitespace-nowrap', 'text-sm', 'text-gray-900');
                            cell.textContent = 'No trends available for this game.';
                            row.appendChild(cell);
                            gameTrendsList.appendChild(row);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching game details:', error);
                        const gameTrendsList = document.getElementById('gameTrendsList');
                        gameTrendsList.innerHTML = `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Error loading game details.</td>
                            </tr>
                        `;
                    });
            }
        });
    </script>
</x-app-layout>
