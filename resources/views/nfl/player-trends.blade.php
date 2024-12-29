@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-8">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-gray-900">NFL Player Trends</h1>
                <div class="text-sm text-gray-500">
                    @if(isset($team))
                        Analyzing: <span class="font-semibold">{{ $team }}</span>
                    @endif
                </div>
            </div>

            {{-- Fetch Odds Button for Admins --}}
            @if(auth()->check() && auth()->user()->isAdmin())
                <form action="{{ route('player-trends.fetch-odds') }}" method="POST" class="mb-4">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ request('event_id') }}">
                    <input type="hidden" name="market" value="{{ request('market', 'player_receptions') }}">
                    <button type="submit"
                            class="w-full sm:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                        Fetch Player Odds
                    </button>
                </form>
            @endif


            {{-- Filter Form --}}
            <form action="{{ route('player.trends.index') }}" method="GET" class="space-y-4">
                <div class="grid sm:grid-cols-5 gap-4">
                    {{-- Event Dropdown --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event</label>
                        <select name="event_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select an event...</option>
                            @foreach($events as $event)
                                <option value="{{ $event->event_id }}" {{ request('event_id') == $event->event_id ? 'selected' : '' }}>
                                    {{ $event->away_team }} @ {{ $event->home_team }}
                                    - {{ Carbon::parse($event->datetime)->format('M d, g:i A') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Market Dropdown --}}
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Market</label>
                        <select name="market"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(config('nfl.markets') as $key => $data)
                                <option value="{{ $key }}" {{ request('market') == $key ? 'selected' : '' }}>
                                    {{ $data['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Submit Button --}}
                    <div class="sm:col-span-1 flex items-end">
                        <button type="submit"
                                class="w-full h-10 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            Filter
                        </button>
                    </div>
                </div>
            </form>

            {{-- Trends Table --}}
            @if(isset($noDataMessage))
                <p class="text-sm text-gray-500">{{ $noDataMessage }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 bg-white rounded-lg shadow-md dark:bg-gray-800 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Player
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Point
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Over
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Under
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Hit %
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Action
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($playerTrends as $trend)
                            @php
                                $overPercentageBadge = match (true) {
                                    $trend->over_percentage >= 70 => 'text-green-800 bg-green-100 dark:bg-green-200 dark:text-green-900',
                                    $trend->over_percentage >= 50 => 'text-yellow-800 bg-yellow-100 dark:bg-yellow-200 dark:text-yellow-900',
                                    default => 'text-red-800 bg-red-100 dark:bg-red-200 dark:text-red-900',
                                };

                                $actionBadge = match ($trend->action) {
                                    'Bet' => 'text-blue-800 bg-blue-100 dark:bg-blue-200 dark:text-blue-900',
                                    'Consider' => 'text-yellow-800 bg-yellow-100 dark:bg-yellow-200 dark:text-yellow-900',
                                    'Stay Away' => 'text-gray-800 bg-gray-100 dark:bg-gray-200 dark:text-gray-900',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $trend->player }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $trend->point }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $trend->total_over_count }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $trend->total_under_count }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                    <span class="inline-block px-3 py-1 rounded-full {{ $overPercentageBadge }}">
                        {{ $trend->over_percentage }}%
                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                    <span class="inline-block px-3 py-1 rounded-full {{ $actionBadge }}">
                        {{ $trend->action }}
                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
