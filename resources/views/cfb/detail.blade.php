<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-semibold mb-6 text-gray-800">{{ $homeTeam->school }} vs {{ $awayTeam->school }}</h1>

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
                        @foreach([
                            'Overall Ranking' => ['home' => $homeSpRating->ranking, 'away' => $awaySpRating->ranking],
                            'Overall Rating' => ['home' => $homeSpRating->overall_rating, 'away' => $awaySpRating->overall_rating],
                            'Elo Rating' => ['home' => $hypothetical->home_elo, 'away' => $hypothetical->away_elo],
                            'FPI Rating' => ['home' => $hypothetical->home_fpi, 'away' => $hypothetical->away_fpi],
                            'Offense Ranking' => ['home' => $homeSpRating->offense_ranking, 'away' => $awaySpRating->offense_ranking],
                            'Offense Rating' => ['home' => $homeSpRating->offense_rating, 'away' => $awaySpRating->offense_rating],
                            'Defense Ranking' => ['home' => $homeSpRating->defense_ranking, 'away' => $awaySpRating->defense_ranking],
                            'Defense Rating' => ['home' => $homeSpRating->defense_rating, 'away' => $awaySpRating->defense_rating],
                            'Special Teams Rating' => ['home' => $homeSpRating->special_teams_rating, 'away' => $awaySpRating->special_teams_rating],
                        ] as $metric => $ratings)
                            <tr>
                                <td class="px-6 py-4 text-sm">{{ $metric }}</td>
                                <td class="px-6 py-4 text-sm">{{ $ratings['home'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $ratings['away'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
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

        <!-- Notes Section -->
        <div class="bg-white shadow-sm sm:rounded-lg mt-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Add a Note</h2>

                <form action="{{ route('cfb.notes.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="team_id" class="block text-sm font-medium text-gray-700">Select Team</label>
                        <select name="team_id" id="team_id" class="form-select mt-1 block w-full">
                            <option value="{{ $homeTeam->id }}">{{ $homeTeam->school }}</option>
                            <option value="{{ $awayTeam->id }}">{{ $awayTeam->school }}</option>
                        </select>
                    </div>

                    <input type="hidden" name="game_id" value="{{ $game->id }}">

                    <div class="mb-4">
                        <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                        <textarea name="note" id="note" rows="4" class="form-input mt-1 block w-full" placeholder="Add your notes here..."></textarea>
                    </div>

                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Note</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hypothetical Outcome Section -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-semibold mb-6 text-gray-800">{{ $homeTeam->school }} vs {{ $awayTeam->school }}</h1>

                    <!-- Form to mark the hypothetical as correct or incorrect -->
                    <div class="mt-6">
                        <h2 class="text-xl font-semibold mb-4 text-gray-800">Was the Hypothetical Correct?</h2>
                        <form action="{{ route('cfb.hypothetical.correct', $hypothetical->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="mb-4">
                                <label for="correct" class="block text-sm font-medium text-gray-700">Prediction Outcome</label>
                                <select name="correct" id="correct" class="form-select mt-1 block w-full">
                                    <option value="1" {{ $hypothetical->correct === 1 ? 'selected' : '' }}>Correct</option>
                                    <option value="0" {{ $hypothetical->correct === 0 ? 'selected' : '' }}>Incorrect</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Outcome</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
