@php use Carbon\Carbon; @endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Hypothetical Spread Overview -->
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800">{{ $awayTeam->school }} vs {{ $homeTeam->school }} Spread
                Overview</h2>
            <p class="text-gray-700 mt-2">
                <strong>Hypothetical Spread:</strong> {{ $hypothetical->hypothetical_spread }} |
                <strong>Projected Winner:</strong> {{ $winnerTeam->school }} |
                <strong>Home Winning Percentage:</strong> {{ $homeWinningPercentage * 100 }}%
            </p>
        </div>

        <!-- Team Comparison Table -->
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Team Comparison</h2>
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">Metric</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">{{ $awayTeam->school }}</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">{{ $homeTeam->school }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach([
                    'Overall Ranking' => ['away' => optional($awaySpRating)->ranking, 'home' => optional($homeSpRating)->ranking],
                    'Offense Ranking' => ['away' => optional($awaySpRating)->offense_ranking, 'home' => optional($homeSpRating)->offense_ranking],
                    'Defense Ranking' => ['away' => optional($awaySpRating)->defense_ranking, 'home' => optional($homeSpRating)->defense_ranking],
                    'Special Teams Rating' => ['away' => optional($awaySpRating)->special_teams_rating, 'home' => optional($homeSpRating)->special_teams_rating],
                ] as $metric => $ratings)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $metric }}</td>
                        <td class="px-6 py-4 text-sm {{ $ratings['away'] > $ratings['home'] ? 'font-semibold' : 'text-gray-600' }}">
                            {{ $ratings['away'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm {{ $ratings['home'] > $ratings['away'] ? 'font-semibold' : 'text-gray-600' }}">
                            {{ $ratings['home'] ?? 'N/A' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Team Stat Comparison Table -->
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Team Stat Comparison</h2>
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">Stat</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">{{ $awayTeam->school }}</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">{{ $homeTeam->school }}</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">Total</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($statsData as $stat => $values)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ ucwords(str_replace('_', ' ', $stat)) }}</td>
                        <td class="px-6 py-4 text-sm {{ $values['away'] > $values['home'] ? 'font-semibold' : 'text-gray-600' }}">
                            {{ $values['away'] }}
                        </td>
                        <td class="px-6 py-4 text-sm {{ $values['home'] > $values['away'] ? 'font-semibold' : 'text-gray-600' }}">
                            {{ $values['home'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $values['total'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mismatch Analysis -->
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Mismatch Analysis</h2>
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">Metric</th>
                    <th class="px-6 py-3 font-medium text-gray-500 text-left">Mismatch Value</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($mismatches as $metric => $value)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ ucwords(str_replace('_', ' ', $metric)) }}</td>
                        <td class="px-6 py-4 text-sm {{ $value > 0 ? 'font-semibold text-gray-700' : 'text-gray-600' }}">
                            {{ $value }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- Last 3 Matchups and Previous Matchups Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <x-matchup-section title="Last 3 Matchups for {{ $homeTeam->school }}" :games="$homeTeamLast3Games"/>
            <x-matchup-section title="Last 3 Matchups for {{ $awayTeam->school }}" :games="$awayTeamLast3Games"/>
            <x-previous-matchups-section :awayTeam="$awayTeam" :homeTeam="$homeTeam"
                                         :previousResults="$previousResults"/>
        </div>

        <!-- User Notes Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <x-user-notes-section :team="$homeTeam" :notes="$homeTeamNotes->where('user_id', auth()->id())"/>
            <x-user-notes-section :team="$awayTeam" :notes="$awayTeamNotes"/>
        </div>

        <!-- Add a Note Form -->
        @auth
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
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
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save
                        Note
                    </button>
                </form>
            </div>
        @endauth

    </div>
</x-app-layout>
