<x-app-layout>
    <div class="max-w-3xl mx-auto py-6 px-6 lg:px-8">
        <h1 class="text-3xl font-bold mb-6">Leaderboard</h1>

        <form method="GET" action="{{ route('picks.leaderboard') }}" class="mb-6">
            <div class="mb-4">
                <label for="game_week" class="block text-sm font-medium text-gray-700">Select Game Week</label>
                <select name="game_week" id="game_week"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">All Weeks</option>
                    @foreach($games as $game)
                        <option value="{{ $game->game_week }}" {{ request('game_week') == $game->game_week ? 'selected' : '' }}>
                             {{ $game->game_week }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Filter
            </button>
        </form>


        <h2 class="text-2xl font-semibold mb-4">Leaderboard</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 divide-y divide-gray-200 shadow-md rounded-lg">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correct Picks</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach($leaderboard as $entry)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $entry->user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->correct_picks }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="text-2xl font-semibold mt-8 mb-4">All Picks for Game</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 divide-y divide-gray-200 shadow-md rounded-lg">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Picked</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correct Pick</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach($allPicks as $pick)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $pick->event ? $pick->event->short_name : 'Unknown Event' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $pick->team ? $pick->team->team_abv : 'Unknown Team' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $pick->is_correct ? 'text-green-500' : 'text-red-500' }}">
                            {{ $pick->is_correct ? 'Yes' : 'No' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
