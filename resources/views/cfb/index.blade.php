@php use Carbon\Carbon; @endphp

<x-app-layout>
    <div class="container px-3 mx-auto max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mt-2 mb-6">
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
                    <div class="bg-white shadow-md rounded-lg overflow-hidden transition-shadow duration-300 hover:shadow-lg
                        {{ $game->completed ? ($game->correct == 1 ? 'border-4 border-green-500/25' : 'border-4 border-red-500/25') : '' }}">

                        <!-- Date and Time Display -->
                        <div class="text-gray-700 p-4 text-xs sm:text-sm font-semibold">
                            {{ Carbon::parse($game->start_date)
                                ->setTimezone('America/Chicago')
                                ->format('l, F j, Y g:i A') }}
                        </div>

                        <div class="p-4 sm:p-6">
                            <div class="flex justify-between items-center mb-4">
                                <!-- Away Team -->
                                <div class="text-lg font-bold text-gray-800">
                                    {{ $game->away_team_school }}
                                    <p class="text-sm text-gray-600 font-bold">{{ $game->away_points ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600">Spread: <span
                                                class="font-bold">{{ $game->hypothetical_spread }}</span></p>
                                </div>

                                <!-- Home Team -->
                                <div class="text-lg font-bold text-gray-800">
                                    {{ $game->home_team_school }}
                                    <p class="text-sm text-gray-600 font-bold">{{ $game->home_points ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600">Win Probability: <span class="font-bold">{{ $game->home_winning_percentage * 100 }}%</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Spread Analysis -->
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-gray-600 font-semibold text-sm">
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
                                <p class="text-sm italic text-green-600">DraftKings: {{ $game->formatted_spread }}</p>
                            </div>

                            <!-- View Details Button -->
                            <div class="mt-4">
                                <a href="{{ route('cfb.hypothetical.show', ['game_id' => $game->game_id]) }}"
                                   class="inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">
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
