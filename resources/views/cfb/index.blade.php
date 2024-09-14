<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold mb-6 text-gray-800">College Football Hypothetical Spreads - Week {{ $week }}</h2>

                <!-- Week dropdown -->
                <form action="{{ route('cfb.index') }}" method="GET" class="mb-6">
                    <label for="week" class="block text-sm font-medium text-gray-700 mb-2">Select Week:</label>
                    <select name="week" id="week" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" onchange="this.form.submit()">
                        @foreach($weeks as $w)
                            <option value="{{ $w->week }}" {{ $week == $w->week ? 'selected' : '' }}>Week {{ $w->week }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 divide-y divide-gray-200 shadow-md rounded-lg">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Away Team</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Home Team</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hypothetical Spread</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Home Winning %</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($hypotheticals as $game)
                            <!-- Apply conditional background color based on the 'correct' value -->
                            <tr onclick="window.location='{{ route('cfb.hypothetical.show', $game->game_id) }}'"
                                class="{{ $game->correct == 1 ? 'bg-green-100' : '' }}"
                                style="cursor: pointer; color: {{ $game->winner_color }};">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $game->away_team_school }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $game->home_team_school }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $game->hypothetical_spread }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $game->home_winning_percentage * 100 }}%</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
