@php use Carbon\Carbon; @endphp
{{-- nba/player-prop-bets.blade.php --}}
<x-app-layout>
    <div class="min-h-screen bg-gray-100">
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6">NBA Player Prop Bet Statistics</h1>

                        {{-- Filters --}}
                        <form method="GET" action="{{ route('player-prop-bets.index') }}" class="mb-8 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label for="date" class="block text-sm font-semibold text-gray-700">Select
                                        Date:</label>
                                    <select name="date" id="date"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition duration-150 ease-in-out">
                                        <option value="">-- Choose a Date --</option>
                                        @foreach($eventDates as $eventDate)
                                            <option value="{{ $eventDate }}" {{ $date == $eventDate ? 'selected' : '' }}>
                                                {{ Carbon::parse($eventDate)->format('F d, Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label for="event_id" class="block text-sm font-semibold text-gray-700">Select
                                        Event:</label>
                                    <select name="event_id" id="event_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition duration-150 ease-in-out">
                                        <option value="">-- Choose an Event --</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event->event_id }}" {{ $eventId == $event->event_id ? 'selected' : '' }}>
                                                {{ $event->home_team_id }} vs {{ $event->away_team_id }}
                                                ({{ Carbon::parse($event->date)->format('h:i A') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-end space-x-4">
                                    <button type="submit"
                                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                                        Filter
                                    </button>
                                    <a href="{{ route('player-prop-bets.index') }}"
                                       class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        @if($noDataMessage)
                            <div class="rounded-md bg-yellow-50 p-4 border-l-4 border-yellow-400">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">{{ $noDataMessage }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900 sm:pl-6">
                                            Player Name
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Prop Total
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Avg Points
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Over Hits
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Over %
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Under Hits
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Under %
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">
                                            Events
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($playerOverStats as $stat)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                {{ $stat->player_name }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $stat->prop_total ? number_format($stat->prop_total, 1) : '-' }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $stat->average_points ? number_format($stat->average_points, 1) : '-' }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $stat->total_over_hits }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ number_format($stat->average_over_percentage, 1) }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $stat->total_under_hits }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ number_format($stat->average_under_percentage, 1) }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $stat->total_events }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>