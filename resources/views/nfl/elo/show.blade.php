<x-app-layout>
    <div class="container mx-auto max-w-5xl">

        <!-- Prediction Card -->
        <div class="py-6">
            @if($predictions->isNotEmpty())
                <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200">
                    <!-- Header Section -->
                    <div class="flex justify-between items-center mb-4 border-b pb-4">
                        <div class="text-blue-700 font-semibold text-xl">
                            {{ $predictions->first()->opponent }}
                        </div>
                        <div class="text-green-700 font-semibold text-xl">
                            {{ $predictions->first()->team }}
                        </div>
                    </div>

                    <!-- Expected Outcome -->
                    <div class="flex justify-center items-center mb-4">
                        <p class="text-gray-700 text-lg">
                            <strong>Expected Outcome:</strong>
                            <span class="text-gray-800 font-semibold">{{ $predictions->first()->team }} Has a {{ number_format($predictions->first()->expected_outcome * 100, 2) }}% Chance of Winning</span>
                        </p>
                    </div>

                    <!-- Matchup Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                        <div>
                            <p><strong>Predicted Spread:</strong>
                                <span class="font-medium">{{ $predictions->first()->predicted_spread }}</span>
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <p><strong>Game Date:</strong>
                                <span class="font-medium">{{ $teamSchedule->game_date }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-gray-500 text-center">No predictions available for this game.</p>
            @endif
        </div>

        <!-- Last 3 Games Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Away Team Table -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Away Team Last 3 Games</h2>
                <x-table>
                    <x-slot name="head">
                        <x-table.heading>Opponent</x-table.heading>
                        <x-table.heading>Date</x-table.heading>
                        <x-table.heading>Score</x-table.heading>
                        <x-table.heading>Rushing Yards</x-table.heading>
                        <x-table.heading>Passing Yards</x-table.heading>
                    </x-slot>
                    <x-slot name="body">
                        @foreach($awayTeamLastGames as $game)
                            <x-table.row>
                                <x-table.cell>{{ $game->home_team_id === $awayTeamId ? $game->away_team : $game->home_team }}</x-table.cell>
                                <x-table.cell>{{ $game->game_date }}</x-table.cell>
                                <x-table.cell>{{ $game->home_pts }} - {{ $game->away_pts }}</x-table.cell>
                                <x-table.cell>{{ $game->rushing_yards }}</x-table.cell>
                                <x-table.cell>{{ $game->passing_yards }}</x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>

            <!-- Home Team Table -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Home Team Last 3 Games</h2>
                <x-table>
                    <x-slot name="head">
                        <x-table.heading>Opponent</x-table.heading>
                        <x-table.heading>Date</x-table.heading>
                        <x-table.heading>Score</x-table.heading>
                        <x-table.heading>Rushing Yards</x-table.heading>
                        <x-table.heading>Passing Yards</x-table.heading>
                    </x-slot>
                    <x-slot name="body">
                        @foreach($homeTeamLastGames as $game)
                            <x-table.row>
                                <x-table.cell>{{ $game->home_team_id === $homeTeamId ? $game->away_team : $game->home_team }}</x-table.cell>
                                <x-table.cell>{{ $game->game_date }}</x-table.cell>
                                <x-table.cell>{{ $game->home_pts }} - {{ $game->away_pts }}</x-table.cell>
                                <x-table.cell>{{ $game->rushing_yards }}</x-table.cell>
                                <x-table.cell>{{ $game->passing_yards }}</x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>
        </div>

        <!-- Injuries Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            
            <!-- Away Team Injuries -->
            <div>
                <h3 class="text-lg font-semibold text-red-600 mb-2">Away Team Injuries</h3>
                <div class="grid gap-4">
                    @forelse($awayTeamInjuries as $injury)
                        <div class="bg-red-50 shadow-md rounded-lg p-4 text-gray-700">
                            <p><strong>Injury:</strong> {{ $injury }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500">No current injuries reported.</p>
                    @endforelse
                </div>
            </div>

            <!-- Home Team Injuries -->
            <div>
                <h3 class="text-lg font-semibold text-red-600 mb-2">Home Team Injuries</h3>
                <div class="grid gap-4">
                    @forelse($homeTeamInjuries as $injury)
                        <div class="bg-red-50 shadow-md rounded-lg p-4 text-gray-700">
                            <p><strong>Injury:</strong> {{ $injury }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500">No current injuries reported.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
