<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-gray-900">NFL Trends Analysis</h1>
                <div class="text-sm text-gray-500">
                    @if(isset($selectedTeam))
                        Analyzing: <span class="font-semibold">{{ $selectedTeam }}</span>
                    @endif
                </div>
            </div>

            <form action="{{ route('nfl.trends.config') }}" method="GET">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Games to Analyze</label>
                        <input type="number"
                               name="games"
                               value="{{ $games ?? 20 }}"
                               min="1"
                               max="100"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-1 flex items-end">
                        <button type="submit"
                                class="w-full h-10 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            Analyze
                        </button>
                    </div>
                </div>
            </form>

            @if(isset($trends))
                <div class="space-y-6">
                    {{-- General Record Card --}}
                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Record Summary (Last {{ $totalGames }}
                            Games)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @php
                                $getStatusColor = function($wins, $total) {
                                    $percentage = ($wins / $total) * 100;
                                    return $percentage >= 55 ? 'text-green-600' : ($percentage <= 45 ? 'text-red-600' : 'text-gray-600');
                                };
                            @endphp

                            {{-- Overall Record --}}
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <p class="text-sm font-medium text-gray-500">Overall Record</p>
                                <p class="mt-2 text-2xl font-bold {{ $getStatusColor($trends['general']['record']['wins'], $totalGames) }}">
                                    {{ $trends['general']['record']['wins'] }}
                                    -{{ $trends['general']['record']['losses'] }}
                                    <span class="text-sm">({{ $trends['general']['record']['percentage'] }}%)</span>
                                </p>
                            </div>

                            {{-- ATS Record --}}
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <p class="text-sm font-medium text-gray-500">Against The Spread</p>
                                <p class="mt-2 text-2xl font-bold {{ $getStatusColor($trends['general']['ats']['wins'], $trends['general']['ats']['wins'] + $trends['general']['ats']['losses']) }}">
                                    {{ $trends['general']['ats']['wins'] }}-{{ $trends['general']['ats']['losses'] }}
                                    <span class="text-sm">({{ $trends['general']['ats']['percentage'] }}%)</span>
                                </p>
                            </div>

                            {{-- Over/Under Record --}}
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <p class="text-sm font-medium text-gray-500">Over/Under</p>
                                <p class="mt-2 text-2xl font-bold {{ $getStatusColor($trends['general']['over_under']['overs'], $totalGames) }}">
                                    {{ $trends['general']['over_under']['overs'] }}
                                    -{{ $trends['general']['over_under']['unders'] }}
                                    <span class="text-sm">({{ $trends['general']['over_under']['percentage'] }}%)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Trend Sections --}}
                @if(isset($trends))
                    <div class="space-y-6">


                        {{-- Trend Sections --}}
                        @foreach(['scoring', 'quarters', 'halves', 'margins', 'totals', 'first_score'] as $sectionKey)
                            @if(isset($trends[$sectionKey]) && !empty($trends[$sectionKey]))
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ ucfirst($sectionKey) }}
                                        Trends</h2>
                                    <div class="space-y-3">
                                        @foreach($trends[$sectionKey] as $trend)
                                            @php
                                                $trend = (string)$trend;
                                                $positiveTerms = ['won', 'scored', 'covered', 'over'];
                                                $negativeTerms = ['lost', 'under', 'fewer'];

                                                $isPositive = false;
                                                $isNegative = false;

                                                foreach ($positiveTerms as $term) {
                                                    if (str_contains(strtolower($trend), $term)) {
                                                        $isPositive = true;
                                                        break;
                                                    }
                                                }

                                                foreach ($negativeTerms as $term) {
                                                    if (str_contains(strtolower($trend), $term)) {
                                                        $isNegative = true;
                                                        break;
                                                    }
                                                }

                                            $trend = (string)$trend;
    // Extract percentage from the trend string using regex
    preg_match('/(\d+) of their last (\d+)/', $trend, $matches);
    $percentage = $matches ? ($matches[1] / $matches[2]) * 100 : 0;

    $isOverFifty = $percentage >= 50;
    $trendColor = $isOverFifty ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    $textColor = $isOverFifty ? 'text-green-700' : 'text-red-700';
                                            @endphp
                                            <div class="rounded-lg border {{ $trendColor }} p-3">
                                                <p class="text-sm {{ $textColor }}">{{ $trend }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach            @endif

                    </div>
                @endif
        </div>
    </div>

</x-app-layout>