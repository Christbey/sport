<!-- resources/views/nfl/elo_predictions.blade.php -->
<x-app-layout>
    <div class="container mx-auto max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">NFL Elo Predictions</h1>

        <!-- Week Selection Form -->
        <form method="GET" action="{{ route('nfl.elo.predictions') }}" class="mb-8">
            <label for="week" class="mr-2 text-gray-600">Select Week:</label>
            <select name="week" id="week" class="border border-gray-300 rounded p-2" onchange="this.form.submit()">
                <option value="">All Weeks</option>
                @foreach($weeks as $wk)
                    <option value="{{ $wk }}" {{ isset($week) && $week == $wk ? 'selected' : '' }}>
                        Week {{ $wk }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
            @if($eloPredictions->isEmpty())
                <p class="text-gray-500">No predictions found for the selected week.</p>
            @else
                @foreach($eloPredictions as $prediction)
                    <div class="bg-white shadow-md rounded-lg overflow-hidden relative hover:shadow-lg transition-shadow duration-300">
                        <div class="p-4 sm:p-6">
                            <!-- Team and Opponent Information -->
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-lg font-bold text-gray-800">
                                    <span class="block text-gray-500 text-sm">Opponent:</span>
                                    {{ $prediction->opponent }}
                                    <p class="text-gray-400 text-sm">
                                </div>
                                <div class="text-lg font-bold text-gray-800">
                                    <span class="block text-gray-500 text-sm">Team:</span>
                                    {{ $prediction->team }}
                                    <p class="text-gray-400 text-sm">
                                </div>
                            </div>

                            <!-- Game Status and Points from API -->
                            <div class="mb-4">
                                <p class="text-sm font-light text-gray-600">
                                    @if($prediction->gameStatus === 'Live - In Progress')
                                        <span class="inline-block bg-yellow-500 text-white px-2 py-1 rounded">Live</span>
                                    @elseif($prediction->gameStatus === 'Completed')
                                        <span class="inline-block bg-green-600 text-white px-2 py-1 rounded">Completed</span>
                                    @else
                                        <span class="inline-block bg-gray-500 text-white px-2 py-1 rounded">Not Started</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600">
                                    Clock: {{ $prediction->gameClock ?? 'Not Available' }}</p>
                                <div class="flex justify-between mt-2">
                                    <p class="text-sm text-gray-600">Away Points: <span
                                                class="font-bold">{{ $prediction->awayPts ?? 'N/A' }}</span></p>
                                    <p class="text-sm text-gray-600">Home Points: <span
                                                class="font-bold">{{ $prediction->homePts ?? 'N/A' }}</span></p>

                                </div>
                            </div>

                            <!-- Predicted Spread -->
                            <div class="border-t border-gray-200 pt-4">
                                <p class="font-semibold text-gray-700">Predicted Spread</p>
                                <p class="text-gray-600">
                                    @if($prediction->predicted_spread > 0)
                                        Home team favored by {{ number_format($prediction->predicted_spread, 2) }}
                                        points
                                    @elseif($prediction->predicted_spread < 0)
                                        Home team predicted to lose
                                        by {{ abs(number_format($prediction->predicted_spread, 2)) }} points
                                    @else
                                        Even game, no predicted favorite
                                    @endif
                                </p>
                            </div>

                            <!-- Correct/Incorrect Prediction -->
                            @if($prediction->gameStatus === 'Completed')
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="font-semibold text-gray-700">Prediction Outcome</p>
                                    <p class="text-gray-600">
                                        @if($prediction->wasCorrect)
                                            <span class="text-green-600 font-bold">Correct</span> - The prediction was
                                            accurate.
                                        @else
                                            <span class="text-red-600 font-bold">Incorrect</span> - The prediction was
                                            not accurate.
                                        @endif
                                    </p>
                                </div>
                            @else
                                <div class="mt-4">
                                    <p class="font-semibold text-gray-400">Prediction outcome pending...</p>
                                </div>
                            @endif

                            <!-- Betting Odds (if available) -->
                            @if(isset($nflBettingOdds[$prediction->game_id]))
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="font-semibold text-gray-700">Betting Odds</p>
                                    <p class="text-gray-600">
                                        @if($nflBettingOdds[$prediction->game_id]->spread_home > 0)
                                            Home team predicted to lose
                                            by {{ number_format($nflBettingOdds[$prediction->game_id]->spread_home, 2) }}
                                            points
                                        @elseif($nflBettingOdds[$prediction->game_id]->spread_home < 0)
                                            Home team favored
                                            by {{ abs(number_format($nflBettingOdds[$prediction->game_id]->spread_home, 2)) }}
                                            points
                                        @else
                                            Even betting odds
                                        @endif
                                    </p>
                                    <p class="text-gray-600">
                                        Over/Under: {{ number_format($nflBettingOdds[$prediction->game_id]->total_over, 2) }}</p>
                                </div>
                            @else
                                <div class="mt-4">
                                    <p class="font-semibold text-gray-400">No betting odds available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
