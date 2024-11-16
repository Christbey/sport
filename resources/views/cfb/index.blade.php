@php use Carbon\Carbon; @endphp

<x-app-layout>
    <div class="container px-4 mx-auto max-w-6xl">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-gray-800">
                College Football Predictions
                <span class="ml-2 px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full">Week {{ $week }}</span>
            </h1>

            <!-- Week Selection Form -->
            <x-week-selection-form
                    :weeks="$weeks"
                    :selected-week="$week"
                    action="{{ route('cfb.index') }}"
            />
        </div>

        @if($hypotheticals->isEmpty())
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <p class="text-gray-500">No predictions available for Week {{ $week }}</p>
                <p class="text-sm text-gray-400 mt-2">Check back later for updates</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($hypotheticals as $game)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-all duration-300
                        {{ $game->completed ? ($game->correct ? 'ring-2 ring-green-500' : 'ring-2 ring-red-500') : 'hover:scale-[1.02]' }}">

                        {{-- Game Header --}}
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="text-sm font-medium text-gray-500">
                                    {{ Carbon::parse($game->start_date)->timezone('America/Chicago')->format('l, F j') }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ Carbon::parse($game->start_date)->timezone('America/Chicago')->format('g:i A') }}
                                </div>
                            </div>
                        </div>

                        {{-- Game Content --}}
                        <div class="p-6">
                            {{-- Teams Comparison --}}
                            <div class="flex justify-between items-center mb-6">
                                {{-- Away Team --}}
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600 mb-1">{{ $game->away_team_school }}</div>
                                    @if($game->completed)
                                        <div class="text-2xl font-bold {{ $game->away_points > $game->home_points ? 'text-green-600' : 'text-gray-600' }}">
                                            {{ $game->away_points ?? 'N/A' }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Game Info --}}
                                <div class="text-center px-4">
                                    <div class="text-sm font-medium text-gray-500 mb-1">Spread</div>
                                    <div class="text-lg font-bold text-gray-700">
                                        {{ $game->hypothetical_spread * -1 > 0 ? '+' : '' }}{{ $game->hypothetical_spread * -1 }}
                                    </div>
                                </div>

                                {{-- Home Team --}}
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600 mb-1">{{ $game->home_team_school }}</div>
                                    @if($game->completed)
                                        <div class="text-2xl font-bold {{ $game->home_points > $game->away_points ? 'text-green-600' : 'text-gray-600' }}">
                                            {{ $game->home_points ?? 'N/A' }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Win Probability Bar --}}
                            <div class="mb-6">
                                <div class="flex justify-between text-sm text-gray-500 mb-1">
                                    <span>Away {{ number_format((1 - $game->home_winning_percentage) * 100, 1) }}%</span>
                                    <span>Home {{ number_format($game->home_winning_percentage * 100, 1) }}%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-600 rounded-full"
                                         style="width: {{ $game->home_winning_percentage * 100 }}%"></div>
                                </div>
                            </div>

                            {{-- Market Comparison --}}
                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Market Spread</span>
                                        <div class="text-sm font-bold text-gray-700">{{ $game->formatted_spread  }}</div>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Edge</span>
                                        <div class="text-sm font-bold {{ abs($game->hypothetical_spread - floatval(str_replace(['−', '+'], ['-', ''], $game->formatted_spread))) > 3 ? 'text-green-600' : 'text-gray-600' }}">
                                            {{ number_format(abs($game->hypothetical_spread - floatval(str_replace(['−', '+'], ['-', ''], $game->formatted_spread))), 1) }}
                                            pts
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Button --}}
                            <a href="{{ route('cfb.hypothetical.show', ['game_id' => $game->game_id]) }}"
                               class="block w-full text-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                View Analysis
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
