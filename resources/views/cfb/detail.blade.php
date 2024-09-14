<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-semibold mb-6 text-gray-800"> {{ $homeTeam->school }} vs {{ $awayTeam->school }}</h1>

                <!-- Hypothetical Spread Overview -->
                <p>
                    <strong>Hypothetical Spread:</strong> {{ $hypothetical->hypothetical_spread }} |
                    <strong>Projected Winner:</strong> {{ $winnerTeam->school }} |
                    <strong>Home Winning Percentage:</strong> {{ $homeWinningPercentage * 100 }}%
                </p>

                <!-- Team Averages Comparison Table -->
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full bg-white border border-gray-200 divide-y divide-gray-200 shadow-md rounded-lg">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $homeTeam->school }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $awayTeam->school }}</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <!-- SP+ Ratings and Other Metrics -->
                        <tr>
                            <td class="px-6 py-4 text-sm">Overall Ranking</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->ranking ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->ranking ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Overall Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->overall_rating ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->overall_rating ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Elo Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $hypothetical->home_elo }}</td>
                            <td class="px-6 py-4 text-sm">{{ $hypothetical->away_elo }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">FPI Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $hypothetical->home_fpi }}</td>
                            <td class="px-6 py-4 text-sm">{{ $hypothetical->away_fpi }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Offense Ranking</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->offense_ranking ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->offense_ranking ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Offense Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->offense_rating ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->offense_rating ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Defense Ranking</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->defense_ranking ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->defense_ranking ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Defense Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->defense_rating ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->defense_rating ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-sm">Special Teams Rating</td>
                            <td class="px-6 py-4 text-sm">{{ $homeSpRating->special_teams_rating ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $awaySpRating->special_teams_rating ?? 'N/A' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mismatch Analysis -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Mismatch Analysis</h2>
                    <ul class="list-disc pl-5">
                        <li><strong> Net PPA Differential:</strong> {{ $ppaMismatch }}</li>
                        <li><strong> Success Rate Differential:</strong> {{ $successRateMismatch }}</li>
                        <li><strong> Explosiveness Differential:</strong> {{ $explosivenessMismatch }}</li>

                    </ul>
                </div>

                <!-- Performance Trends -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Performance Trends</h2>
                    <ul class="list-disc pl-5">
                        <li><strong>Home Team Offense Trend:</strong> {{ $home_offense_trend }}</li>
                        <li><strong>Away Team Offense Trend:</strong> {{ $away_offense_trend }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
