@php use Carbon\Carbon; @endphp

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-all duration-300
">

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
                <div class="text-lg font-bold text-gray-600 mb-1">{{ $game->away_team_school }}</div>
                @if($game->completed)
                    <div class="text-2xl font-bold {{ $game->away_points > $game->home_points ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $game->away_points ?? 'N/A' }}
                    </div>
                @endif
            </div>

            {{-- Home Team --}}
            <div class="text-center">
                <div class="text-lg font-bold text-gray-600 mb-1">{{ $game->home_team_school }}</div>
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

            <!-- Progress bar -->
            <div class="w-full bg-gray-200 rounded-full">
                <div class="bg-blue-600 h-2.5 rounded-full"
                     style="width: {{ $game->home_winning_percentage * 100 }}%"></div>
            </div>
        </div>

        {{-- Market Comparison --}}
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-sm font-medium text-gray-500">Market Spread</span>
                    <div class="text-sm font-bold text-gray-700">{{ $game->formatted_spread }}</div>
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

        {{-- Action Button and Icon in the Same Row --}}
        <div class="flex items-center justify-between">
            <x-link-button href="{{ route('cfb.hypothetical.show', ['game_id' => $game->game_id]) }}">
                View Analysis
            </x-link-button>

            {{--            @if($game->completed)--}}
            {{--                @if($game->correct)--}}
            {{--                    <!-- Trophy Icon -->--}}
            {{--                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="currentColor"--}}
            {{--                         viewBox="0 0 24 24">--}}
            {{--                        <!-- SVG path for Trophy Icon -->--}}
            {{--                        <path d="M2 5a1 1 0 011-1h2a3 3 0 006 0h2a3 3 0 006 0h2a1 1 0 011 1v2a7 7 0 01-6 6.92V17a2 2 0 011 1.732V20h3v2H6v-2h3v-1.268A2 2 0 0110 17v-3.08A7 7 0 014 7V5zm2 2v2a5 5 0 004 4.9V17a1 1 0 002 0v-3.1A5 5 0 0018 9V7h-2a5 5 0 00-4 4.9V17a3 3 0 01-6 0v-5.1A5 5 0 006 7H4z"/>--}}
            {{--                    </svg>--}}
            {{--                @else--}}
            {{--                    <!-- X Icon -->--}}
            {{--                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="currentColor"--}}
            {{--                         viewBox="0 0 20 20">--}}
            {{--                        <!-- SVG path for X Icon -->--}}
            {{--                        <path fill-rule="evenodd"--}}
            {{--                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.536-10.536a1 1 0 00-1.414-1.414L10 8.586 7.879 6.465a1 1 0 10-1.414 1.414L8.586 10l-2.121 2.121a1 1 0 001.414 1.414L10 11.414l2.121 2.121a1 1 0 001.414-1.414L11.414 10l2.122-2.122z"--}}
            {{--                              clip-rule="evenodd"/>--}}
            {{--                    </svg>--}}
            {{--                @endif--}}
            {{--            @endif--}}
        </div>
    </div>
</div>
