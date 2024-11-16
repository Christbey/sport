@php use Carbon\Carbon; @endphp
@props(['title', 'games'])

<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ $title }}</h2>

    @if ($games->isNotEmpty())
        <div class="overflow-hidden">
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Matchup</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Result</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($games as $game)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ Carbon::parse($game->start_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                    <span class="font-medium {{ $game->home_points > $game->away_points ? 'text-green-600' : 'text-gray-600' }}">
                                        {{ $game->homeTeam->school ?? 'Unknown Team' }}
                                    </span>
                                <span class="text-xs text-gray-500">vs</span>
                                <span class="font-medium {{ $game->away_points > $game->home_points ? 'text-blue-600' : 'text-gray-600' }}">
                                        {{ $game->awayTeam->school ?? 'Unknown Team' }}
                                    </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                <div class="font-medium">
                                        <span class="{{ $game->home_points > $game->away_points ? 'text-green-600' : '' }}">
                                            {{ $game->home_points }}
                                        </span>
                                    -
                                    <span class="{{ $game->away_points > $game->home_points ? 'text-blue-600' : '' }}">
                                            {{ $game->away_points }}
                                        </span>
                                </div>
                                <span class="text-xs text-gray-500 mt-1">
                                        @if ($game->home_points > $game->away_points)
                                        {{ $game->homeTeam->school }} won
                                    @elseif ($game->home_points < $game->away_points)
                                        {{ $game->awayTeam->school }} won
                                    @else
                                        Tie game
                                    @endif
                                    </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-600">
                <p class="mb-2">
                    <span class="font-medium">Summary:</span>
                    @php
                        $wins = 0;
                        $losses = 0;
                        $mainTeam = explode(' for ', $title)[1] ?? null;
                        
                        foreach ($games as $game) {
                            if ($game->homeTeam->school === $mainTeam) {
                                if ($game->home_points > $game->away_points) $wins++;
                                else if ($game->home_points < $game->away_points) $losses++;
                            } else if ($game->awayTeam->school === $mainTeam) {
                                if ($game->away_points > $game->home_points) $wins++;
                                else if ($game->away_points < $game->home_points) $losses++;
                            }
                        }
                    @endphp
                    <span class="font-medium ml-1">
                        {{ $wins }}-{{ $losses }}
                    </span>
                    in last {{ $games->count() }} games
                </p>

                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="font-medium text-gray-700">Performance Metrics:</p>
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <div>
                            <p class="text-xs text-gray-500">Average Points Scored</p>
                            <p class="font-medium">
                                {{ round($games->avg(fn($game) => 
                                    $game->homeTeam->school === $mainTeam ? $game->home_points : $game->away_points
                                ), 1) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Average Points Allowed</p>
                            <p class="font-medium">
                                {{ round($games->avg(fn($game) => 
                                    $game->homeTeam->school === $mainTeam ? $game->away_points : $game->home_points
                                ), 1) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">No recent games found.</p>
            <p class="text-sm text-gray-400 mt-1">Check back later for updates.</p>
        </div>
    @endif
</div>