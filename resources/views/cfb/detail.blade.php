@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Hypothetical Spread Overview -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800">{{ $awayTeam->school }}
                vs {{ $homeTeam->school }} Spread Overview</h2>
            <p class="text-gray-700">
                <strong>Hypothetical Spread:</strong> {{ $hypothetical->hypothetical_spread }} |
                <strong>Projected Winner:</strong> {{ $winnerTeam->school }} |
                <strong>Home Winning Percentage:</strong> {{ $homeWinningPercentage * 100 }}%
            </p>
        </div>

        <!-- Team Averages Comparison Table -->
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Team Comparison</h2>
            <table class="min-w-full table-auto text-left border-collapse border border-gray-200">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500">Metric</th>
                    <th class="px-6 py-3 font-medium text-gray-500">{{ $awayTeam->school }}</th>
                    <th class="px-6 py-3 font-medium text-gray-500">{{ $homeTeam->school }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach([
                    'Overall Ranking' => ['away' => optional($homeSpRating)->ranking, 'home' => optional($awaySpRating)->ranking],
                    'Overall Rating' => ['away' => optional($homeSpRating)->overall_rating, 'home' => optional($awaySpRating)->overall_rating],
                    'Elo Rating' => ['away' => $hypothetical->home_elo, 'home' => $hypothetical->away_elo],
                    'FPI Rating' => ['away' => $hypothetical->home_fpi, 'home' => $hypothetical->away_fpi],
                    'Offense Ranking' => ['away' => optional($homeSpRating)->offense_ranking, 'home' => optional($awaySpRating)->offense_ranking],
                    'Offense Rating' => ['away' => optional($homeSpRating)->offense_rating, 'home' => optional($awaySpRating)->offense_rating],
                    'Defense Ranking' => ['away' => optional($homeSpRating)->defense_ranking, 'home' => optional($awaySpRating)->defense_ranking],
                    'Defense Rating' => ['away' => optional($homeSpRating)->defense_rating, 'home' => optional($awaySpRating)->defense_rating],
                    'Special Teams Rating' => ['away' => optional($homeSpRating)->special_teams_rating, 'home' => optional($awaySpRating)->special_teams_rating],
                ] as $metric => $ratings)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $metric }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $ratings['away'] ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $ratings['home'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mismatch Analysis -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Mismatch Analysis</h2>
            <ul class="list-disc pl-5 text-gray-700">
                <li><strong>Net PPA Differential:</strong> {{ $ppaMismatch }}</li>
                <li><strong>Success Rate Differential:</strong> {{ $successRateMismatch }}</li>
                <li><strong>Explosiveness Differential:</strong> {{ $explosivenessMismatch }}</li>
            </ul>
        </div>

        <!-- Performance Trends -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Performance Trends</h2>
            <ul class="list-disc pl-5 text-gray-700">
                <li><strong>Home Team Offense Trend:</strong> {{ $home_offense_trend }}</li>
                <li><strong>Away Team Offense Trend:</strong> {{ $away_offense_trend }}</li>
            </ul>
        </div>

        <!-- Last 3 Matchups for Home Team -->
        <div class="bg-gray-50 shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Last 3 Matchups for {{ $homeTeam->school }}</h2>
            <ul class="list-disc pl-5 space-y-2">
                @foreach($homeTeamLast3Games as $game)
                    <li class="text-gray-600">
                        <span class="font-semibold">{{ $game->homeTeam->school ?? 'Unknown Team' }}</span> vs
                        <span class="font-semibold">{{ $game->awayTeam->school ?? 'Unknown Team' }}</span>
                        on <span class="text-blue-600">{{ Carbon::parse($game->start_date)->format('M d, Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Last 3 Matchups for Away Team -->
        <div class="bg-gray-50 shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Last 3 Matchups for {{ $awayTeam->school }}</h2>
            <ul class="list-disc pl-5 space-y-2">
                @foreach($awayTeamLast3Games as $game)
                    <li class="text-gray-600">
                        <span class="font-semibold">{{ $game->homeTeam->school ?? 'Unknown Team' }}</span> vs
                        <span class="font-semibold">{{ $game->awayTeam->school ?? 'Unknown Team' }}</span>
                        on <span class="text-blue-600">{{ Carbon::parse($game->start_date)->format('M d, Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Previous Matchups Between the Two Teams -->
        <div class="bg-gray-50 shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Previous Matchups Between {{ $awayTeam->school }}
                and {{ $homeTeam->school }}</h2>
            @if($previousResults->isEmpty())
                <p class="text-gray-500">No previous matchups found between these two teams.</p>
            @else
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($previousResults as $result)
                        <li class="text-gray-600">
                            <span class="text-blue-600">{{ Carbon::parse($result['date'])->format('M d, Y') }}</span> -
                            Winner: <span class="font-semibold">{{ $result['winner'] }}</span> -
                            Score: <span class="font-semibold">{{ $result['score'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Notes Section -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Notes for {{ $homeTeam->school }}</h2>
            @if($homeTeamNotes->isEmpty())
                <p class="text-gray-500">No notes found for {{ $homeTeam->school }}.</p>
            @else
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($homeTeamNotes as $note)
                        <li class="text-gray-600">
                            <span class="font-semibold">{{ $note->created_at->format('M d, Y H:i') }}</span>:
                            {{ $note->note }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Notes for {{ $awayTeam->school }}</h2>
            @if($awayTeamNotes->isEmpty())
                <p class="text-gray-500">No notes found for {{ $awayTeam->school }}.</p>
            @else
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($awayTeamNotes as $note)
                        <li class="text-gray-600">
                            <span class="font-semibold">{{ $note->created_at->format('M d, Y H:i') }}</span>:
                            {{ $note->note }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Add Note Form -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Add a Note</h2>
            <form action="{{ route('cfb.notes.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="team_id" class="block text-sm font-medium text-gray-700">Select Team</label>
                    <select name="team_id" id="team_id"
                            class="form-select mt-1 block w-full border-gray-300 rounded-md">
                        <option value="{{ $homeTeam->id }}">{{ $homeTeam->school }}</option>
                        <option value="{{ $awayTeam->id }}">{{ $awayTeam->school }}</option>
                    </select>
                </div>

                <input type="hidden" name="game_id" value="{{ $game->id }}">

                <div class="mb-4">
                    <label for="note" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea name="note" id="note" rows="4"
                              class="form-textarea mt-1 block w-full border-gray-300 rounded-md"
                              placeholder="Add your notes here..."></textarea>
                </div>

                <div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Save Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
