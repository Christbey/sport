<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-gray-900">NFL Player Trends</h1>
                <div class="text-sm text-gray-500">
                    @if(isset($selectedTeam))
                        Analyzing: <span class="font-semibold">{{ $selectedTeam }}</span>
                    @endif
                </div>
            </div>

            {{-- Filter Form --}}
            <form action="{{ route('player.trends.index') }}" method="GET">
                <div class="grid sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                        <select name="team"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Season</label>
                        <select name="season"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Seasons</option>
                            @foreach(range(date('Y'), 2020) as $year)
                                <option value="{{ $year }}" {{ $year == ($season ?? '') ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Week</label>
                        <input type="number"
                               name="week"
                               value="{{ $week ?? '' }}"
                               min="1"
                               max="18"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-1 flex items-end">
                        <button type="submit"
                                class="w-full h-10 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            Filter
                        </button>
                    </div>
                </div>
            </form>

            {{-- Trends Table --}}
            @if($playerTrends->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md border border-gray-200">
                        <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Player</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Week</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Point</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Over Count</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Under Count</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Season</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($playerTrends as $trend)
                            <tr class="border-b hover:bg-gray-100">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $trend->player }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $trend->week }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $trend->point }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $trend->over_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $trend->under_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $trend->season }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">No trends data found for the selected filters.</p>
            @endif
        </div>
    </div>
</x-app-layout>
