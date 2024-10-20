@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="container mx-auto max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            College Football Hypothetical Spreads - Week {{ $week }}
        </h1>

        <!-- Week Selection Form -->
        <x-week-selection-form
                :weeks="$weeks"
                :selected-week="$week"
                action="{{ route('cfb.index') }}"
        />


        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
            @if($hypotheticals->isEmpty())
                <p class="text-gray-500">No predictions found for the selected week.</p>
            @else
                @foreach($hypotheticals as $game)
                    <div class="bg-white shadow-md rounded-lg overflow-hidden relative hover:shadow-lg transition-shadow duration-300">
                        <div class="p-4 sm:p-6">
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

                            <!-- Display Game Start Date in CST -->
                            <div class="text-gray-600 mb-4">
                                <p>Start Time:
                                    {{ Carbon::parse($game->start_date)
                                        ->setTimezone('America/Chicago')
                                        ->format('l, F j, Y g:i A') }}
                                </p>
                            </div>

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
                                <p>DraftKings: {{ $game->formatted_spread }}</p>

                            </div>

                            @if($game->completed == 1)
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

                            <div class="mt-4">
                                <a href="{{ route('cfb.hypothetical.show', ['game_id' => $game->game_id]) }}"
                                   class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
