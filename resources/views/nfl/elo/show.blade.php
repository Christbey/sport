@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($predictions->isNotEmpty())
                {{-- Game Matchup Card --}}
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
                    {{-- Teams Header --}}
                    <div class="bg-gray-800 text-white p-6">
                        <div class="flex justify-between items-center">
                            <div class="text-center flex-1">
                                <h2 class="text-2xl font-bold">{{ $predictions->first()->opponent }}</h2>
                                <p class="text-gray-400 text-sm mt-1">Away Team</p>
                            </div>
                            <div class="px-4">
                                <span class="text-2xl font-bold text-gray-400">VS</span>
                            </div>
                            <div class="text-center flex-1">
                                <h2 class="text-2xl font-bold">{{ $predictions->first()->team }}</h2>
                                <p class="text-gray-400 text-sm mt-1">Home Team</p>
                            </div>
                        </div>
                    </div>

                    {{-- Game Details --}}
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Win Probability --}}
                            <div class="bg-blue-50 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-blue-800 mb-2">Win Probability</h3>
                                <div class="relative pt-1">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-blue-600">
                                            {{ $predictions->first()->opponent }}
                                        </div>
                                        <div class="text-xs text-blue-600">
                                            {{ $predictions->first()->team }}
                                        </div>
                                    </div>
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-blue-200 mt-1">
                                        <div class="bg-blue-600 rounded"
                                             style="width: {{ $predictions->first()->expected_outcome * 100 }}%"></div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <span class="text-lg font-bold text-blue-800">
                                            {{ number_format($predictions->first()->expected_outcome * 100, 1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Predicted Spread --}}
                            <div class="bg-green-50 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-green-800 mb-2">Predicted Spread</h3>
                                <p class="text-lg font-bold text-green-800">
                                    {{ $predictions->first()->predicted_spread > 0 ? '-' : '' }}{{ number_format($predictions->first()->predicted_spread, 1) }}
                                </p>
                                <p class="text-xs text-green-600 mt-1">
                                    {{ $predictions->first()->predicted_spread > 0 ? 'Home Favored' : 'Away Favored' }}
                                </p>
                            </div>

                            {{-- Game Date --}}
                            <div class="bg-purple-50 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-purple-800 mb-2">Game Date</h3>
                                <p class="text-lg font-bold text-purple-800">
                                    {{ Carbon::parse($teamSchedule->game_date)->format('D, M j, Y') }}
                                </p>
                                <p class="text-xs text-purple-600 mt-1">
                                    {{ Carbon::parse($teamSchedule->game_date)->format('g:i A T') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Games Section --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    {{-- Away Team Recent Games --}}
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-blue-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">{{ $predictions->first()->opponent }} Recent
                                Games</h2>
                        </div>
                        <div class="p-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Opponent
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Score
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rush/Pass
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($awayTeamLastGames as $game)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $game->home_team_id === $awayTeamId ? $game->away_team : $game->home_team }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ Carbon::parse($game->game_date)->format('M j') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="font-medium">{{ $game->home_pts }}</span> -
                                            <span class="font-medium">{{ $game->away_pts }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $game->rushing_yards }}/{{ $game->passing_yards }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Home Team Recent Games --}}
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-green-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">{{ $predictions->first()->team }} Recent
                                Games</h2>
                        </div>
                        <div class="p-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Opponent
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Score
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rush/Pass
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($homeTeamLastGames as $game)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $game->home_team_id === $homeTeamId ? $game->away_team : $game->home_team }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ Carbon::parse($game->game_date)->format('M j') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="font-medium">{{ $game->home_pts }}</span> -
                                            <span class="font-medium">{{ $game->away_pts }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $game->rushing_yards }}/{{ $game->passing_yards }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Injuries Section --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Away Team Injuries --}}
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-red-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">{{ $predictions->first()->opponent }}
                                Injuries</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @forelse($awayTeamInjuries as $injury)
                                    <div class="bg-red-50 rounded-lg p-4">
                                        <p class="text-red-700">{{ $injury }}</p>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-center py-4">No injuries reported</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Home Team Injuries --}}
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-red-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">{{ $predictions->first()->team }} Injuries</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @forelse($homeTeamInjuries as $injury)
                                    <div class="bg-red-50 rounded-lg p-4">
                                        <p class="text-red-700">{{ $injury }}</p>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-center py-4">No injuries reported</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No predictions available</h3>
                    <p class="mt-1 text-sm text-gray-500">No data found for this game</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
