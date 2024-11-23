@php use Carbon\Carbon; @endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 space-y-8">

        <div class="flex items-center justify-between mb-6">
            <div class="flex-1 text-center">
                <span class="block text-2xl font-bold text-blue-600">{{ $awayTeam->school }}</span>
                <span class="text-sm text-gray-500">Away Team</span>
            </div>
            <div class="px-4">
                <span class="text-2xl font-bold text-gray-400">VS</span>
            </div>
            <div class="flex-1 text-center">
                <span class="block text-2xl font-bold text-green-600">{{ $homeTeam->school }}</span>
                <span class="text-sm text-gray-500">Home Team</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500 mb-1">Hypothetical Spread</div>
                <div class="text-xl font-bold {{ $hypothetical->hypothetical_spread > 0 ? 'text-green-600' : 'text-blue-600' }}">
                    {{ $hypothetical->hypothetical_spread * -1 }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500 mb-1">Projected Winner</div>
                <div class="text-xl font-bold {{ $winnerTeam->id === $homeTeam->id ? 'text-green-600' : 'text-blue-600' }}">
                    {{ $winnerTeam->school }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500 mb-1">Home Win Probability</div>
                <div class="flex items-center">
                    <div class="text-xl font-bold {{ $homeWinningPercentage > 0.5 ? 'text-green-600' : 'text-blue-600' }}">
                        {{ number_format($homeWinningPercentage * 100, 1) }}%
                    </div>
                    <div class="ml-2 flex-1">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $homeWinningPercentage > 0.5 ? 'bg-green-600' : 'bg-blue-600' }}"
                                 style="width: {{ $homeWinningPercentage * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-600">
                <p class="mb-2">
                    <span class="font-medium">Spread Interpretation:</span>
                    @if($hypothetical->hypothetical_spread > 0)
                        {{ $homeTeam->school }} favored by {{ abs($hypothetical->hypothetical_spread) }}
                    @else
                        {{ $awayTeam->school }} favored by {{ abs($hypothetical->hypothetical_spread) }}
                    @endif
                </p>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="font-medium text-gray-700">Key Factors:</p>
                    <p class="mt-1 text-sm">
                        @if($homeWinningPercentage > 0.5)
                            {{ $homeTeam->school }} has a
                            <span class="font-medium text-green-600">{{ number_format(($homeWinningPercentage - 0.5) * 200, 1) }}%</span>
                            edge based on current projections
                        @else
                            {{ $awayTeam->school }} has a
                            <span class="font-medium text-blue-600">{{ number_format((0.5 - $homeWinningPercentage) * 200, 1) }}%</span>
                            edge based on current projections
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Team Comparison Table -->
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Team Rankings Comparison</h2>
                <div class="flex space-x-4">
                    <span class="text-sm px-3 py-1 bg-blue-100 text-blue-600 rounded-full">{{ $awayTeam->school }}</span>
                    <span class="text-sm px-3 py-1 bg-green-100 text-green-600 rounded-full">{{ $homeTeam->school }}</span>
                </div>
            </div>

            <div class="overflow-hidden">
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-sm font-medium text-gray-500 text-left">Category</th>
                        <th class="px-6 py-3 text-sm font-medium text-gray-500 text-center">{{ $awayTeam->school }}</th>
                        <th class="px-6 py-3 text-sm font-medium text-gray-500 text-center">{{ $homeTeam->school }}</th>
                        <th class="px-6 py-3 text-sm font-medium text-gray-500 text-center">Advantage</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach([
                        'Overall Ranking' => [
                            'away' => optional($awaySpRating)->ranking,
                            'home' => optional($homeSpRating)->ranking,
                            'icon' => 'trophy'
                        ],
                        'Offense Ranking' => [
                            'away' => optional($awaySpRating)->offense_ranking,
                            'home' => optional($homeSpRating)->offense_ranking,
                            'icon' => 'football'
                        ],
                        'Defense Ranking' => [
                            'away' => optional($awaySpRating)->defense_ranking,
                            'home' => optional($homeSpRating)->defense_ranking,
                            'icon' => 'shield'
                        ],
                        'Special Teams Rating' => [
                            'away' => optional($awaySpRating)->special_teams_rating,
                            'home' => optional($homeSpRating)->special_teams_rating,
                            'icon' => 'star'
                        ],
                    ] as $metric => $data)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-700">{{ $metric }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center">
                                <span class="text-sm {{ $data['away'] < $data['home'] ? 'font-bold text-blue-600' : 'text-gray-600' }}">
                                    {{ $data['away'] ?? 'N/A' }}
                                </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center">
                                <span class="text-sm {{ $data['home'] < $data['away'] ? 'font-bold text-green-600' : 'text-gray-600' }}">
                                    {{ $data['home'] ?? 'N/A' }}
                                </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($data['away'] && $data['home'])
                                    @if($data['away'] < $data['home'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $awayTeam->school }}
                                    </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $homeTeam->school }}
                                    </span>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="text-sm text-gray-600">
                    <p class="mb-2"><span class="font-medium">Rankings Explanation:</span> Lower numbers indicate better
                        rankings</p>
                    <p class="mb-2"><span class="font-medium">Overall Ranking:</span> Team's national ranking across all
                        metrics</p>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <p class="font-medium text-gray-700">Key Advantages:</p>
                        <div class="mt-2 space-y-1">
                            @foreach(['Overall', 'Offense', 'Defense', 'Special Teams'] as $category)
                                @php
                                    $awayRating = $category === 'Special Teams'
                                        ? optional($awaySpRating)->special_teams_rating
                                        : optional($awaySpRating)->{strtolower($category).'_ranking'};
                                    $homeRating = $category === 'Special Teams'
                                        ? optional($homeSpRating)->special_teams_rating
                                        : optional($homeSpRating)->{strtolower($category).'_ranking'};
                                @endphp
                                @if($awayRating && $homeRating)
                                    <p class="text-sm">
                                        {{ $category }}:
                                        <span class="font-medium {{ $awayRating < $homeRating ? 'text-blue-600' : 'text-green-600' }}">
                                    {{ $awayRating < $homeRating ? $awayTeam->school : $homeTeam->school }}
                                </span>
                                        by {{ abs($awayRating - $homeRating) }} spots
                                    </p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Stat Comparison Table -->

        <!-- Team Efficiency Metrics -->


        {{-- resources/views/components/matchup-advantages-card.blade.php --}}
        <x-efficiency-metrics-card
                :efficiency-metrics="$efficiencyMetrics"
                :home-team="$homeTeam"
                :away-team="$awayTeam"
        />

        <x-matchup-advantages-card
                :matchup-advantages="$matchupAdvantages"
                :home-team="$homeTeam"
                :away-team="$awayTeam"
        />

        <x-scoring-prediction
                :scoring-prediction="$scoringPrediction"
                :home-team="$homeTeam"
                :away-team="$awayTeam"
        />

        <x-drive-metrics
                :drive-metrics="$driveMetrics"
                :home-team="$homeTeam"
                :away-team="$awayTeam"
        />
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
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Game Notes</h2>
                    <span class="text-sm bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
            {{ Carbon::parse($game->start_date)->format('M d, Y') }}
        </span>
                </div>

                <form action="{{ route('cfb.notes.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="game_id" value="{{ $game->id }}">

                    {{-- Team Selection --}}
                    <div class="space-y-2">
                        <label for="team_id" class="block text-sm font-medium text-gray-700">Select Team</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="team_id" value="{{ $homeTeam->id }}"
                                       class="peer sr-only" checked>
                                <div class="w-full p-4 border rounded-lg peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-400 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-900">{{ $homeTeam->school }}</span>
                                        <span class="text-xs bg-green-100 text-green-600 px-2.5 py-0.5 rounded-full">Home</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="team_id" value="{{ $awayTeam->id }}"
                                       class="peer sr-only">
                                <div class="w-full p-4 border rounded-lg peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-400 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-900">{{ $awayTeam->school }}</span>
                                        <span class="text-xs bg-blue-100 text-blue-600 px-2.5 py-0.5 rounded-full">Away</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Note Content --}}
                    <div class="space-y-2">
                        <label for="note" class="block text-sm font-medium text-gray-700">
                            Note Content
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                <textarea
                        name="note"
                        id="note"
                        rows="4"
                        class="block w-full pr-10 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 resize-none"
                        placeholder="Enter your analysis, observations, or predictions..."
                ></textarea>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-start pt-2">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Add your insights about team performance, key matchups, or game predictions.
                        </p>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Clear
                        </button>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                      clip-rule="evenodd"/>
                            </svg>
                            Save Note
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-700">Recent Notes</h3>
                        <span class="text-xs text-gray-500">Last 3 notes</span>
                    </div>
                    <div class="mt-2 space-y-2">
                        @forelse(auth()->user()->notes()->latest()->take(3)->get() as $note)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500">
                            {{ $note->created_at->diffForHumans() }}
                        </span>
                                    <span class="text-xs {{ $note->team_id === $homeTeam->id ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' }} px-2 py-0.5 rounded-full">
                            {{ $note->team->school }}
                        </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">{{ $note->note }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-4">No recent notes</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endauth
    </div>
</x-app-layout>
