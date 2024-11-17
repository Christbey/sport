<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header Section --}}
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">NFL Game Predictions</h1>
                <p class="mt-2 text-sm text-gray-600">Powered by Elo Ratings System</p>
            </div>

            {{-- Week Selection --}}
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form method="GET" action="{{ route('nfl.elo.predictions') }}" class="max-w-xs">
                    <label for="week" class="block text-sm font-medium text-gray-700">Game Week</label>
                    <div class="mt-2 flex items-center space-x-4">
                        <select name="week" id="week"
                                class="block w-full rounded-md border-gray-300 pr-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                onchange="this.form.submit()">
                            <option value="">All Weeks</option>
                            @foreach($weeks as $wk)
                                <option value="{{ $wk }}" {{ isset($week) && $week == $wk ? 'selected' : '' }}>
                                    Week {{ $wk }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- Predictions Grid --}}
            @if($eloPredictions->isEmpty())
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No predictions available</h3>
                    <p class="mt-1 text-sm text-gray-500">No games found for the selected week</p>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($eloPredictions as $prediction)
                        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                            {{-- Game Header --}}
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">
                                        {{ $prediction->gameStatusDetail ?? 'Time TBD' }}
                                    </span>
                                    <span class="
                                        text-xs font-medium px-2.5 py-0.5 rounded-full
                                        {{ $prediction->gameStatus === 'Live - In Progress' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($prediction->gameStatus === 'Completed' ? 'bg-gray-100 text-gray-800' : 
                                           'bg-blue-100 text-blue-800') }}
                                    ">
                                        {{ $prediction->gameStatus }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-6">
                                {{-- Teams Matchup --}}
                                <div class="flex justify-between items-center mb-6">
                                    {{-- Away Team --}}
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ $prediction->opponent }}</p>
                                        @if(isset($prediction->awayPts))
                                            <p class="text-2xl font-bold {{ $prediction->gameStatus === 'Completed' ? ($prediction->awayPts > $prediction->homePts ? 'text-green-600' : 'text-gray-400') : 'text-gray-900' }}">
                                                {{ $prediction->awayPts }}
                                            </p>
                                        @endif
                                    </div>

                                    {{-- VS Divider --}}
                                    <div class="px-4">
                                        <span class="text-gray-400 font-medium">VS</span>
                                    </div>

                                    {{-- Home Team --}}
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ $prediction->team }}</p>
                                        @if(isset($prediction->homePts))
                                            <p class="text-2xl font-bold {{ $prediction->gameStatus === 'Completed' ? ($prediction->homePts > $prediction->awayPts ? 'text-green-600' : 'text-gray-400') : 'text-gray-900' }}">
                                                {{ $prediction->homePts }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Predictions Section --}}
                                <div class="space-y-4">
                                    {{-- Predicted Spread --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-sm font-medium text-gray-700">Predicted Spread</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            @if($prediction->predicted_spread > 0)
                                                <span class="font-medium text-green-600">{{ $prediction->team }}</span>
                                                favored by {{ number_format($prediction->predicted_spread, 1) }}
                                            @elseif($prediction->predicted_spread < 0)
                                                <span class="font-medium text-green-600">{{ $prediction->opponent }}</span>
                                                favored by {{ abs(number_format($prediction->predicted_spread, 1)) }}
                                            @else
                                                Even matchup
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Betting Odds --}}
                                    @if(isset($nflBettingOdds[$prediction->game_id]))
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-sm font-medium text-gray-700">Market Odds</p>
                                            <div class="mt-1 space-y-1">
                                                <p class="text-sm text-gray-600">
                                                    Spread: {{ $nflBettingOdds[$prediction->game_id]->spread_home > 0 ? '+' : '' }}{{ number_format($nflBettingOdds[$prediction->game_id]->spread_home, 1) }}
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    Over/Under: {{ number_format($nflBettingOdds[$prediction->game_id]->total_over, 1) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Prediction Result --}}
                                    @if($prediction->gameStatus === 'Completed')
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-sm font-medium text-gray-700">Prediction Result</p>
                                            <p class="mt-1 text-sm {{ $prediction->wasCorrect ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $prediction->wasCorrect ? '✓ Correct Prediction' : '✗ Incorrect Prediction' }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Action Button --}}
                                <div class="mt-6">
                                    <a href="{{ route('nfl.elo.show', ['gameId' => $prediction->game_id]) }}"
                                       class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-150">
                                        View Analysis
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
```