<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-semibold mb-6 text-gray-800">{{ $homeTeam->school }} vs {{ $awayTeam->school }}</h1>
                <div class="mt-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Spread Comparison</h2>
                    <p>{{ $smartPick }}</p>
                </div>

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
                        @foreach([
                            'Overall Ranking' => ['home' => optional($homeSpRating)->ranking, 'away' => optional($awaySpRating)->ranking],
                            'Overall Rating' => ['home' => optional($homeSpRating)->overall_rating, 'away' => optional($awaySpRating)->overall_rating],
                            'Elo Rating' => ['home' => $hypothetical->home_elo, 'away' => $hypothetical->away_elo],
                            'FPI Rating' => ['home' => $hypothetical->home_fpi, 'away' => $hypothetical->away_fpi],
                            'Offense Ranking' => ['home' => optional($homeSpRating)->offense_ranking, 'away' => optional($awaySpRating)->offense_ranking],
                            'Offense Rating' => ['home' => optional($homeSpRating)->offense_rating, 'away' => optional($awaySpRating)->offense_rating],
                            'Defense Ranking' => ['home' => optional($homeSpRating)->defense_ranking, 'away' => optional($awaySpRating)->defense_ranking],
                            'Defense Rating' => ['home' => optional($homeSpRating)->defense_rating, 'away' => optional($awaySpRating)->defense_rating],
                            'Special Teams Rating' => ['home' => optional($homeSpRating)->special_teams_rating, 'away' => optional($awaySpRating)->special_teams_rating],
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
                    <div class="max-w-7xl mx-auto py-12">
                        <h1 class="text-2xl font-bold">Update Prediction</h1>

                        <form action="{{ route('cfb.hypothetical.correct', $hypothetical->id) }}" method="POST">
                            @csrf
                            @method('PATCH')  <!-- Change to PATCH -->

                            <!-- Select the correct team -->
                            <div class="mt-4">
                                <label for="team_id" class="block text-sm font-medium text-gray-700">Select Team</label>
                                <select name="team_id" id="team_id" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="{{ $homeTeam->id }}">{{ $homeTeam->school }} (Home)</option>
                                    <option value="{{ $awayTeam->id }}">{{ $awayTeam->school }} (Away)</option>
                                </select>
                            </div>

                            <!-- Correct prediction -->
                            <div class="mt-4">
                                <label for="correct" class="block text-sm font-medium text-gray-700">Correct Prediction</label>
                                <select name="correct" id="correct" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="1" {{ $hypothetical->correct == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $hypothetical->correct == 0 ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <button type="submit" class="mt-6 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
