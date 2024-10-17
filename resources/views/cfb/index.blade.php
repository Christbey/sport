<x-app-layout>
    <div class="container mx-auto max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            College Football Hypothetical Spreads - Week {{ $week }}
        </h1>

        <!-- Week Selection Form -->
        <form method="GET" action="{{ route('cfb.index') }}" class="mb-8">
            <label for="week" class="mr-2 text-gray-600">Select Week:</label>
            <select name="week" id="week" class="border border-gray-300 rounded p-2" onchange="this.form.submit()">
                <option value="">All Weeks</option>
                @foreach($weeks as $wk)
                    <option value="{{ $wk->week }}" {{ $week == $wk->week ? 'selected' : '' }}>
                        Week {{ $wk->week }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
            @if($hypotheticals->isEmpty())
                <p class="text-gray-500">No predictions found for the selected week.</p>
            @else
                @foreach($hypotheticals as $game)
                    <div class="bg-white shadow-md rounded-lg overflow-hidden relative hover:shadow-lg transition-shadow duration-300">
                        <div class="p-4 sm:p-6">
                            <!-- Team and Opponent Information -->
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-lg font-bold text-gray-800">
                                    {{ $game->away_team_school }}
                                    <p class="text-sm text-gray-600">
                                        <span class="font-bold">{{ $game->hypothetical_spread }}</span>
                                    </p>
                                </div>
                                <div class="text-lg font-bold text-gray-800">
                                    {{ $game->home_team_school }}
                                    <p class="text-sm text-gray-600">
                                        <span class="font-bold">{{ $game->home_winning_percentage * 100 }}%</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Predicted Spread Logic -->
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-gray-600 font-semibold text-small">
                                    @if($game->hypothetical_spread > 0)
                                        {{ $game->home_team_school }} favored
                                        by {{ number_format($game->hypothetical_spread, 2) }} points
                                    @elseif($game->hypothetical_spread < 0)
                                        {{ $game->home_team_school }} predicted to lose
                                        by {{ abs(number_format($game->hypothetical_spread, 2)) }} points
                                    @else
                                        Even game, no predicted favorite
                                    @endif
                                </p>
                            </div>

                            <!-- Show Prediction Outcome Only If Game is Completed -->
                            @if($game->game->completed == 1)
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="font-semibold text-gray-700">Prediction Outcome</p>
                                    <p class="text-gray-600">
                                        @if($game->correct == 1)
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
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
