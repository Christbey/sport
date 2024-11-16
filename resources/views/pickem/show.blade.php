<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header and Notifications remain the same --}}
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">NFL Pick'em</h1>
                <p class="mt-2 text-sm text-gray-600">Make your predictions for this week's games</p>
            </div>
            {{-- Week Selection Form remains the same --}}
            @if(session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Week Selection --}}
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form id="weekForm" method="GET" action="{{ route('pickem.schedule') }}" class="max-w-xs">
                    <label for="game_week" class="block text-sm font-medium text-gray-700">Select Week</label>
                    <div class="mt-2 flex items-center">
                        <select name="game_week" id="game_week"
                                class="block w-full rounded-md border-gray-300 pr-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                onchange="this.form.submit()">
                            <option value="">All Weeks</option>
                            @foreach($weeks as $week)
                                @php
                                    $week_number = (int)str_replace('Week ', '', $week->game_week);
                                @endphp
                                <option value="{{ $week_number }}" {{ $game_week == $week_number ? 'selected' : '' }}>
                                    Week {{ $week_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            {{-- Games Grid with Fixed Team Selection --}}
            <form action="{{ route('pickem.pickWinner') }}" method="POST">
                @csrf
                <div class=" space-y-8">
                    @if($schedules->isEmpty())
                        {{-- Empty state remains the same --}}
                    @else
                        <div class="pb-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($schedules as $schedule)
                                @php
                                    $userPick = $userSubmissions[$schedule->espn_event_id]->team_id ?? null;
                                @endphp
                                <div class="bg-white rounded-lg shadow-sm">
                                    <div class="p-6">
                                        <input type="hidden" name="event_ids[]" value="{{ $schedule->espn_event_id }}">

                                        {{-- Game Time/Status --}}
                                        <div class="text-xs font-medium text-gray-500 mb-4">
                                            {{ $schedule->status_type_detail ?? 'Time TBD' }}
                                        </div>

                                        {{-- Teams Selection --}}
                                        <div class="space-y-4">
                                            {{-- Away Team --}}
                                            <div class="relative border rounded-lg p-4
                                                {{ $userPick == $schedule->away_team_id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                                <div class="flex items-center">
                                                    <input type="radio"
                                                           name="team_ids[{{ $schedule->espn_event_id }}]"
                                                           id="away_{{ $schedule->espn_event_id }}"
                                                           value="{{ $schedule->away_team_id }}"
                                                           {{ $userPick == $schedule->away_team_id ? 'checked' : '' }}
                                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                                    <label for="away_{{ $schedule->espn_event_id }}"
                                                           class="ml-3 flex flex-col cursor-pointer">
                                                        <span class="block text-sm font-medium text-gray-900">
                                                            {{ $schedule->awayTeam->team_name ?? 'Unknown' }}
                                                        </span>
                                                        <span class="block text-sm text-gray-500">
                                                            {{ $schedule->away_team_record ?? 'N/A' }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                            {{-- Home Team --}}
                                            <div class="relative border rounded-lg p-4
                                                {{ $userPick == $schedule->home_team_id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                                <div class="flex items-center">
                                                    <input type="radio"
                                                           name="team_ids[{{ $schedule->espn_event_id }}]"
                                                           id="home_{{ $schedule->espn_event_id }}"
                                                           value="{{ $schedule->home_team_id }}"
                                                           {{ $userPick == $schedule->home_team_id ? 'checked' : '' }}
                                                           class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                                    <label for="home_{{ $schedule->espn_event_id }}"
                                                           class="ml-3 flex flex-col cursor-pointer">
                                                        <span class="block text-sm font-medium text-gray-900">
                                                            {{ $schedule->homeTeam->team_name ?? 'Unknown' }}
                                                        </span>
                                                        <span class="block text-sm text-gray-500">
                                                            {{ $schedule->home_team_record ?? 'N/A' }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Submit Button --}}
                        <div class="fixed bottom-0 inset-x-0 pb-6 sm:pb-8 bg-gradient-to-t from-gray-50">
                            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                                <button type="submit"
                                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Save Picks
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
