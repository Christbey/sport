<x-app-layout>
    <div class="container mx-auto max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Game Stats for Game ID: {{ $teamSchedule->game_id }}</h1>

        <div class="grid grid-cols-1 gap-6">
            <!-- Only display one card for each matchup -->
            @if($predictions->isNotEmpty())
                <div class="bg-white shadow-md rounded-lg p-6">
                    <!-- Matchup Information -->
                    <div class="flex justify-between items-start mb-4">
                        <!-- Team Information -->
                        <div class="w-1/2 mr-4">
                            <h2 class="text-lg font-bold text-blue-600">{{ $predictions->first()->opponent }}</h2>

                        </div>

                        <!-- Opponent Information -->
                        <div class="w-1/2">
                            <h2 class="text-lg font-bold text-green-600">{{ $predictions->first()->team }}</h2>
                            <p>Expected
                                Outcome: {{ number_format(( $predictions->first()->expected_outcome) * 100, 2) }}
                                %</p>
                        </div>
                    </div>

                    <!-- Shared Matchup Information -->
                    <p class="text-gray-600"><strong>Predicted
                            Spread:</strong> {{ $predictions->first()->predicted_spread }}</p>
                    <p class="text-gray-600"><strong>Game Status:</strong> {{ $teamSchedule->game_status }}</p>
                    <p class="text-gray-600"><strong>Game Date:</strong> {{ $teamSchedule->game_date }}</p>
                </div>
            @else
                <p>No predictions available for this game.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Home Team Last 3 Games -->
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Home Team Last 3 Games</h3>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                @foreach($homeTeamLastGames as $game)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <p>
                            <strong>Opponent:</strong> {{ $game->home_team_id === $homeTeamId ? $game->away_team : $game->home_team }}
                        </p>
                        <p><strong>Date:</strong> {{ $game->game_date }}</p>
                        <p><strong>Score:</strong> {{ $game->home_pts }} - {{ $game->away_pts }}</p>
                        <p><strong>Rushing Yards:</strong> {{ $game->rushing_yards }}</p>
                        <p><strong>Passing Yards:</strong> {{ $game->passing_yards }}</p>
                        <p><strong>Status:</strong> {{ $game->game_status }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Away Team Last 3 Games -->
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Away Team Last 3 Games</h3>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach($awayTeamLastGames as $game)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <p>
                            <strong>Opponent:</strong> {{ $game->home_team_id === $awayTeamId ? $game->away_team : $game->home_team }}
                        </p>
                        <p><strong>Date:</strong> {{ $game->game_date }}</p>
                        <p><strong>Score:</strong> {{ $game->home_pts }} - {{ $game->away_pts }}</p>
                        <p><strong>Rushing Yards:</strong> {{ $game->rushing_yards }}</p>
                        <p><strong>Passing Yards:</strong> {{ $game->passing_yards }}</p>
                        <p><strong>Status:</strong> {{ $game->game_status }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Injuries -->
            <h3 class="text-lg font-semibold text-red-600 mb-2">Home Team Injuries</h3>
            <div class="grid grid-cols-1 gap-4 mb-6">
                @forelse($homeTeamInjuries as $injury)
                    <div class="bg-red-50 shadow-md rounded-lg p-4">
                        <p><strong>Injury:</strong> {{ $injury }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No current injuries reported.</p>
                @endforelse
            </div>

            <!-- Away Team Injuries -->
            <h3 class="text-lg font-semibold text-red-600 mb-2">Away Team Injuries</h3>
            <div class="grid grid-cols-1 gap-4">
                @forelse($awayTeamInjuries as $injury)
                    <div class="bg-red-50 shadow-md rounded-lg p-4">
                        <p><strong>Injury:</strong> {{ $injury }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No current injuries reported.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>