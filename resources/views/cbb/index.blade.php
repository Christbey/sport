@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">College Basketball Predictions</h1>
                <p class="mt-2 text-sm text-gray-600">Browse and analyze game predictions</p>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('cbb.index') }}" method="GET" class="mt-4 lg:mt-0">
                <div class="flex items-center space-x-4">
                    <div class="min-w-[200px]">
                        <label for="game_date" class="block text-sm font-medium text-gray-700 mb-1">Game Date</label>
                        <select name="game_date" id="game_date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All Dates</option>
                            @foreach($dates as $date)
                                <option value="{{ $date->game_date }}"
                                        {{ $selectedDate == $date->game_date ? 'selected' : '' }}>
                                    {{ Carbon::parse($date->game_date)->format('l, F j, Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Selected Date Display -->
        @if($selectedDate)
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-blue-700 font-medium">
                            Showing games for {{ Carbon::parse($selectedDate)->format('l, F j, Y') }}
                        </span>
                    </div>
                    <a href="{{ route('cbb.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Clear
                        Filter</a>
                </div>
            </div>
        @endif

        <!-- Games Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Spread
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Offensive Edge
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Defensive Edge
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($hypotheticals as $hypothetical)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $hypothetical->home_team }}
                                        <span class="text-xs text-green-600 ml-1">(Home)</span>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        vs {{ $hypothetical->away_team }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium
                                        {{ $hypothetical->hypothetical_spread > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $hypothetical->hypothetical_spread > 0 ? '+' : '' }}{{ $hypothetical->hypothetical_spread }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="{{ $hypothetical->offense_difference > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $hypothetical->offense_difference > 0 ? '+' : '' }}{{ number_format($hypothetical->offense_difference, 1) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="{{ $hypothetical->defense_difference > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $hypothetical->defense_difference > 0 ? '+' : '' }}{{ number_format($hypothetical->defense_difference, 1) }}
                                    </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="mt-2 text-sm">No games found for the selected date</p>
                                    <p class="mt-1 text-xs text-gray-400">Try selecting a different date</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
