<!-- resources/views/college_basketball_hypotheticals/index.blade.php -->
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">College Basketball Hypothetical Games</h1>

        <!-- Filter Form -->
        <form action="{{ route('cbb.index') }}" method="GET" class="mb-8">
            <div class="flex items-end space-x-4">
                <div>
                    <label for="game_date" class="block text-gray-700 font-medium mb-2">Filter by Game Date:</label>
                    <select name="game_date" id="game_date"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500">
                        <option value="">All Dates</option>
                        @foreach($dates as $date)
                            <option value="{{ $date->game_date }}" {{ $selectedDate == $date->game_date ? 'selected' : '' }}>
                                {{ $date->game_date }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-300">
                        Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Table of Hypotheticals -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-md shadow-sm">
                <thead>
                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">

                    <th class="py-3 px-4 border-b border-gray-200">Home Team</th>
                    <th class="py-3 px-4 border-b border-gray-200">Away Team</th>
                    <th class="py-3 px-4 border-b border-gray-200">Hypothetical Spread</th>
                    <th class="py-3 px-4 border-b border-gray-200">Offense Difference</th>
                    <th class="py-3 px-4 border-b border-gray-200">Defense Difference</th>
                </tr>
                </thead>
                <tbody class="text-gray-800 text-sm">
                @forelse($hypotheticals as $hypothetical)
                    <tr class="border-b hover:bg-gray-50">
        
                        <td class="py-3 px-4">{{ $hypothetical->home_team }}</td>
                        <td class="py-3 px-4">{{ $hypothetical->away_team }}</td>
                        <td class="py-3 px-4 text-center">{{ $hypothetical->hypothetical_spread }}</td>
                        <td class="py-3 px-4 text-center">{{ $hypothetical->offense_difference }}</td>
                        <td class="py-3 px-4 text-center">{{ $hypothetical->defense_difference }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-4 text-center text-gray-600">No games found for the selected date.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
