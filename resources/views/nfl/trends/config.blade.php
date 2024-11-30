<x-app-layout>
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">NFL Trends Analysis</h1>

            <form action="{{ route('nfl.trends.config') }}" method="GET" class="mb-8">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Team</label>
                    <div class="flex gap-4">
                        <select name="team"
                                class="w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Select a team...</option>
                            @foreach(['ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE',
                                    'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC',
                                    'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG',
                                    'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WAS'] as $team)
                                <option value="{{ $team }}" {{ $team === ($selectedTeam ?? '') ? 'selected' : '' }}>
                                    {{ $team }}
                                </option>
                            @endforeach
                        </select>

                        <select name="season"
                                class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">All Seasons</option>
                            @foreach(range(date('Y'), 2020) as $year)
                                <option value="{{ $year }}" {{ $year == ($season ?? '') ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>

                        <input type="number"
                               name="games"
                               value="{{ $games ?? 20 }}"
                               min="1"
                               max="100"
                               placeholder="# of games"
                               class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">

                        <button type="submit"
                                class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Analyze
                        </button>
                    </div>
                </div>
            </form>

            @if(isset($trends))
                <div class="space-y-6">
                    {{-- General Record --}}
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h2 class="text-lg font-medium mb-3">Record (Last {{ $totalGames }} Games)</h2>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-gray-600">Overall</p>
                                <p class="text-xl font-bold">
                                    {{ $trends['general']['record']['wins'] }}
                                    -{{ $trends['general']['record']['losses'] }}
                                    ({{ $trends['general']['record']['percentage'] }}%)
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">Against The Spread</p>
                                <p class="text-xl font-bold">
                                    {{ $trends['general']['ats']['wins'] }}-{{ $trends['general']['ats']['losses'] }}
                                    ({{ $trends['general']['ats']['percentage'] }}%)
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">Over/Under</p>
                                <p class="text-xl font-bold">
                                    {{ $trends['general']['over_under']['overs'] }}
                                    -{{ $trends['general']['over_under']['unders'] }}
                                    ({{ $trends['general']['over_under']['percentage'] }}%)
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Display other trends sections --}}
                    @foreach(['scoring', 'quarters', 'halves', 'margins', 'totals', 'first_score'] as $section)
                        @if(isset($trends[$section]) && !empty($trends[$section]))
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-medium mb-3">{{ ucfirst($section) }} Trends</h2>
                                <ul class="space-y-2">
                                    @foreach($trends[$section] as $trend)
                                        <li class="text-gray-700">{{ $trend }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>