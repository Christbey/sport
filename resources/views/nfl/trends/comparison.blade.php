<div class="container mx-auto px-4 py-8">

    <h1 class="text-3xl font-bold mb-8">NFL Trends Comparison</h1>

    {{-- Week Selection --}}
    <div class="mb-8">
        <form method="GET" action="{{ route('nfl.trends.compare') }}" class="max-w-md">
            @csrf
            <label for="week" class="block text-sm font-medium text-gray-700 mb-2">Select Week</label>
            <select name="week" id="week"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    onchange="this.form.submit()">
                <option value="">Select Week</option>
                @foreach($upcomingGames->keys() as $week)
                    <option value="{{ $week }}" {{ $selectedWeek == $week ? 'selected' : '' }}>
                        Week {{ $week }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Games for Selected Week --}}
    @if($selectedWeek)
        <div class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Games for Week {{ $selectedWeek }}</h2>
            <div class="max-w-md">
                <form method="GET" action="{{ route('nfl.trends.compare') }}">
                    <input type="hidden" name="week" value="{{ $selectedWeek }}">
                    <label for="game" class="block text-sm font-medium text-gray-700 mb-2">Select Game</label>
                    <select name="game" id="game"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="this.form.submit()">
                        <option value="">Select Game</option>
                        @foreach($weekGames as $game)
                            <option value="{{ $game->game_id }}">
                                {{ $game->home_team }} vs {{ $game->away_team }}
                                ({{ Carbon\Carbon::parse($game->game_date)->format('M d, Y') }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    @endif

    @if(isset($comparison))
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Team 1 (Home) Analysis -->
            <div>
                <h2 class="text-2xl font-bold mb-6">{{ $comparison['team1']['name'] }} Analysis</h2>

                <!-- Record & Betting -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">General Trends</h3>
                    <div class="space-y-3">
                        <!-- Record -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Record:</span>
                            <span>{{ $comparison['team1']['trends']['general']['record']['wins'] }}-{{ $comparison['team1']['trends']['general']['record']['losses'] }} ({{ $comparison['team1']['trends']['general']['record']['percentage'] }}%)</span>
                        </div>
                        <!-- ATS -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Against Spread:</span>
                            <span>{{ $comparison['team1']['trends']['general']['ats']['wins'] }}-{{ $comparison['team1']['trends']['general']['ats']['losses'] }} ({{ $comparison['team1']['trends']['general']['ats']['percentage'] }}%)</span>
                        </div>
                        <!-- Over/Under -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Over/Under:</span>
                            <span>O{{ $comparison['team1']['trends']['general']['over_under']['overs'] }}-U{{ $comparison['team1']['trends']['general']['over_under']['unders'] }} (Over {{ $comparison['team1']['trends']['general']['over_under']['percentage'] }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- Quarter Trends -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">Quarter Trends</h3>
                    <div class="space-y-2">
                        @foreach($comparison['team1']['trends']['quarters'] as $trend)
                            <div class="bg-gray-50 p-3 rounded">{{ $trend }}</div>
                        @endforeach
                    </div>
                </div>

                <!-- Half Trends -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">Half Trends</h3>
                    <div class="space-y-2">
                        @foreach($comparison['team1']['trends']['halves'] as $trend)
                            <div class="bg-gray-50 p-3 rounded">{{ $trend }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Team 2 (Away) Analysis -->
            <div>
                <h2 class="text-2xl font-bold mb-6">{{ $comparison['team2']['name'] }} Analysis</h2>

                <!-- Record & Betting -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">General Trends</h3>
                    <div class="space-y-3">
                        <!-- Record -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Record:</span>
                            <span>{{ $comparison['team2']['trends']['general']['record']['wins'] }}-{{ $comparison['team2']['trends']['general']['record']['losses'] }} ({{ $comparison['team2']['trends']['general']['record']['percentage'] }}%)</span>
                        </div>
                        <!-- ATS -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Against Spread:</span>
                            <span>{{ $comparison['team2']['trends']['general']['ats']['wins'] }}-{{ $comparison['team2']['trends']['general']['ats']['losses'] }} ({{ $comparison['team2']['trends']['general']['ats']['percentage'] }}%)</span>
                        </div>
                        <!-- Over/Under -->
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                            <span class="font-medium">Over/Under:</span>
                            <span>O{{ $comparison['team2']['trends']['general']['over_under']['overs'] }}-U{{ $comparison['team2']['trends']['general']['over_under']['unders'] }} (Over {{ $comparison['team2']['trends']['general']['over_under']['percentage'] }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- Quarter Trends -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">Quarter Trends</h3>
                    <div class="space-y-2">
                        @foreach($comparison['team2']['trends']['quarters'] as $trend)
                            <div class="bg-gray-50 p-3 rounded">{{ $trend }}</div>
                        @endforeach
                    </div>
                </div>

                <!-- Half Trends -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">Half Trends</h3>
                    <div class="space-y-2">
                        @foreach($comparison['team2']['trends']['halves'] as $trend)
                            <div class="bg-gray-50 p-3 rounded">{{ $trend }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
@endif