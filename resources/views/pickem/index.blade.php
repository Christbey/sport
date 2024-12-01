<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 ">Pickem Leaderboard</h1>
            <p class="mt-2 text-sm text-gray-600 ">Track standings and view all picks for each game
                week.</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600 ">Current Week</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 ">
                        Week {{ $games->max('game_week') }}</div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600 ">Total Players</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 ">{{ $leaderboard->count() }}</div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 overflow-hidden shadow-sm rounded-xl">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-700/80">Current Period</div>
                    <div class="mt-2 text-3xl font-bold text-gray-700">Weeks {{ $period_start_week }}
                        -{{ $period_end_week }}</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div x-data="{ isFilterOpen: false }" class="mb-8">
            <div class="bg-white  overflow-hidden shadow-sm rounded-xl">
                <div class="p-6">
                    <form method="GET" action="{{ route('pickem.leaderboard') }}" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                            <div class="flex-1">
                                <label for="game_week"
                                       class="block text-sm font-medium text-gray-700 ">Game
                                    Week</label>
                                <select
                                        name="game_week"
                                        id="game_week"
                                        class="mt-1 block w-full rounded-lg border-gray-300  shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">All Weeks</option>
                                    @foreach($games as $game)
                                        <option value="{{ $game->game_week }}" {{ request('game_week') == $game->game_week ? 'selected' : '' }}>
                                            Week {{ $game->game_week }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-4 sm:mt-0">
                                <button
                                        type="submit"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Standings</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                        <tr class="bg-gray-50">
                            @php
                                $columns = [
                                    'name' => 'User',
                                    'correct_picks' => 'Correct Picks',
                                    'total_points' => 'Total Points',
                                    'period_points' => 'Period Points'
                                ];
                            @endphp

                            @foreach($columns as $key => $label)
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <a
                                            href="{{ route('pickem.leaderboard', ['sort' => $key, 'direction' => request('sort') == $key && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="group flex items-center space-x-1"
                                    >
                                        <span>{{ $label }}</span>
                                        <svg class="w-4 h-4 transition-transform duration-200 {{ request('sort') == $key ? (request('direction') == 'desc' ? 'rotate-180' : '') : 'text-gray-400 opacity-0 group-hover:opacity-100' }}"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 ">
                        @foreach($leaderboard as $index => $user)
                            <tr class="hover:bg-gray-50  transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="w-8 text-sm text-gray-500 ">{{ $index + 1 }}.</span>
                                        <span class="font-medium text-gray-900 ">{{ $user->name }}</span>
                                        @if($index < 3)
                                            <svg class="w-4 h-4 ml-2 {{ ['text-yellow-400', 'text-gray-400', 'text-amber-600'][$index] }}"
                                                 fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M5 3h14l1 2v3c0 3-2 6-5 7v5h4v2H5v-2h4v-5c-3-1-5-4-5-7V5l1-2zm2 2v3c0 3 2 5 5 5s5-2 5-5V5H7z"/>
                                            </svg>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700 ">
                                    {{ $user->correct_picks }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 ">{{ $user->total_points }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                            <span class="font-semibold {{ $user->period_points > 0 ? 'text-green-600 ' : 'text-gray-900 ' }}">
                                                {{ $user->period_points }}
                                            </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- All Picks Table -->
        <div class="bg-white  overflow-hidden shadow-sm rounded-xl">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900  mb-4">Recent Picks</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                        <tr class="bg-gray-50">
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 ">
                                Event
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 ">
                                Team Picked
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">
                                Result
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 ">
                        @foreach($allPicks as $pick)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 ">
                                    {{ $pick->event ? $pick->event->short_name : 'Unknown Event' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pick->is_correct ? 'bg-green-100 text-green-800 ' : 'bg-gray-100 text-gray-800 ' }}">
                                            {{ $pick->team ? $pick->team->team_abv : 'Unknown Team' }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($pick->is_correct)
                                        <span class="inline-flex items-center text-green-600 ">
                                                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Correct
                                            </span>
                                    @else
                                        <span class="inline-flex items-center text-red-600 ">
                                                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Incorrect
                                            </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>