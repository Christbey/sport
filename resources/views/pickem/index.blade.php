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
        <div class="bg-white shadow-sm rounded-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Leaderboard</h2>
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Correct
                        Picks
                    </th>
                </x-slot>
                <x-slot name="body">
                    @foreach($leaderboard as $entry)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{{ $entry->user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->correct_picks }}</td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-table>
        </div>

        <!-- All Picks Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">All Picks for Game</h2>
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Event
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Team
                        Picked
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Correct
                        Pick
                    </th>
                </x-slot>
                <x-slot name="body">
                    @foreach($allPicks as $pick)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $pick->event ? $pick->event->short_name : 'Unknown Event' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pick->team ? $pick->team->team_abv : 'Unknown Team' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $pick->is_correct ? 'text-green-500 font-semibold' : 'text-red-500 font-semibold' }}">
                                {{ $pick->is_correct ? 'Yes' : 'No' }}
                            </td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-table>
        </div>
    </div>
</x-app-layout>
