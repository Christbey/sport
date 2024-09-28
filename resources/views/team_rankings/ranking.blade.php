<x-app-layout>
    <div class="container mx-auto py-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Team Rankings</h2>

        <form id="rankingForm" class="mb-6">
            <label for="rankingTypeSelect" class="block text-sm font-medium text-gray-700">Choose a Ranking:</label>
            <select id="rankingTypeSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">-- Select a Ranking --</option>

                <!-- Predictive Rankings -->
                <optgroup label="Predictive Rankings">
                    <option value="{{ route('api.team-rankings.fetch', ['rankingType' => 'predictive-by-other']) }}">Predictive Rating</option>
                    <option value="{{ route('api.team-rankings.fetch', ['rankingType' => 'predictive-by-offense']) }}">Offensive Predictive Rating</option>
                    <option value="{{ route('api.team-rankings.fetch', ['rankingType' => 'predictive-by-defense']) }}">Defensive Predictive Rating</option>
                </optgroup>
            </select>
        </form>

        <div id="rankingDataTable" class="mt-6">
            <!-- Table for ranking data will be rendered here dynamically -->
        </div>
    </div>

    <script>
        document.getElementById('rankingTypeSelect').addEventListener('change', function () {
            const selectedValue = this.value;
            if (selectedValue) {
                fetch(selectedValue)
                    .then(response => response.json())
                    .then(data => {
                        renderRankingTable(data.data);
                    })
                    .catch(error => {
                        console.error('Error fetching ranking data:', error);
                    });
            }
        });

        function renderRankingTable(rows) {
            const tableContainer = document.getElementById('rankingDataTable');
            let tableHTML = `
                <div class="overflow-x-auto">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2">Rank</th>
                                <th class="px-4 py-2">Team</th>
                                <th class="px-4 py-2">Rating</th>
                                <!-- Add other columns as needed -->
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
            `;

            rows.forEach(row => {
                tableHTML += `
                    <tr class="hover:bg-gray-100">
                        <td class="px-4 py-2">${row.rank}</td>
                        <td class="px-4 py-2">${row.team}</td>
                        <td class="px-4 py-2">${row.rating}</td>
                    </tr>
                `;
            });

            tableHTML += `
                        </tbody>
                    </table>
                </div>
            `;

            tableContainer.innerHTML = tableHTML;
        }
    </script>
</x-app-layout>
