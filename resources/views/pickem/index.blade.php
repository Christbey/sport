<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-6 lg:px-8">

        <!-- Filter Form -->
        <form method="GET" action="{{ route('picks.leaderboard') }}" class="mb-8">
            <div class="mb-4">
                <label for="game_week" class="block text-sm font-medium text-gray-700 mb-1">Select Game Week</label>
                <select name="game_week" id="game_week"
                        class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">All Weeks</option>
                    @foreach($games as $game)
                        <option value="{{ $game->game_week }}" {{ request('game_week') == $game->game_week ? 'selected' : '' }}>
                            Week {{ $game->game_week }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Filter
            </button>
        </form>

        <!-- Leaderboard Section -->
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 px-6 py-3 bg-gray-50 ">
                Leaderboard</h2>
            <table class="w-full text-sm text-left text-gray-500 ">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 ">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <a href="{{ route('picks.leaderboard', ['sort' => 'user', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                           class="flex items-center">
                            User
                            @if(request('sort') == 'user')
                                <svg class="w-3 h-3 ms-1 {{ request('direction') == 'asc' ? '' : 'rotate-180' }}"
                                     xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.574 11.024h6.852a2.075..."/>
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <a href="{{ route('picks.leaderboard', ['sort' => 'correct_picks', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                           class="flex items-center">
                            Correct Picks
                            @if(request('sort') == 'correct_picks')
                                <svg class="w-3 h-3 ms-1 {{ request('direction') == 'asc' ? '' : 'rotate-180' }}"
                                     xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.574 11.024h6.852a2.075..."/>
                                </svg>
                            @endif
                        </a>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($leaderboard as $entry)
                    <tr class="bg-white border-b ">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $entry->user->name }}</td>
                        <td class="px-6 py-4">{{ $entry->correct_picks }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <!-- All Picks Section -->
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 px-6 py-3 bg-gray-50">
                All Picks for Game</h2>
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <a href="{{ route('picks.leaderboard', ['sort' => 'event', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                           class="flex items-center">
                            Event
                            @if(request('sort') == 'event')
                                <svg class="w-3 h-3 ms-1 {{ request('direction') == 'asc' ? '' : 'rotate-180' }}"
                                     xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.574 11.024h6.852a2.075..."/>
                                </svg>
                            @endif
                        </a>
                    </th>
                    <th scope="col" class="px-6 py-3">Team Picked</th>
                    <th scope="col" class="px-6 py-3">Correct Pick</th>
                </tr>
                </thead>
                <tbody>
                @foreach($allPicks as $pick)
                    <tr class="bg-white border-b hover:bg-gray-100 ">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $pick->event ? $pick->event->short_name : 'Unknown Event' }}
                        </td>
                        <td class="px-6 py-4">{{ $pick->team ? $pick->team->team_abv : 'Unknown Team' }}</td>
                        <td class="px-6 py-4 {{ $pick->is_correct ? 'text-green-500 font-semibold' : 'text-red-500 font-semibold' }}">
                            {{ $pick->is_correct ? 'Yes' : 'No' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
