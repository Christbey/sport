<x-app-layout>
    <div class="max-w-3xl lg:mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <!-- Form for filtering by week -->
            <form id="weekForm" method="GET" action="{{ route('pickem.filter') }}" class="mb-6">
                <label for="week_id" class="block text-sm font-medium text-gray-700 mb-2"></label>
                <select name="week_id" id="week_id" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" onchange="this.form.submit()">
                    <option value="">All Weeks</option>
                    @foreach($weeks as $week)
                        <option value="{{ $week->game_week }}" {{ $week_id == $week->game_week ? 'selected' : '' }}>
                            {{ $week->game_week }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Display matchups and submit form -->
            <h2 class="text-2xl text-gray-700 font-semibold mb-6">{{ $week_id ?? 'All' }} Matchups</h2>

            <form action="{{ route('pickem.pickWinner') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
                    @if($schedules->isEmpty())
                        <p class="text-gray-500">No events found for this week.</p>
                    @else
                        @foreach($schedules as $schedule)
                            @php
                                $userPick = $userSubmissions[$schedule->espn_event_id]->team_id ?? null; // Get the user's choice for this event (if any)
                            @endphp
                            <div class="bg-white shadow-md rounded-lg overflow-hidden relative">
                                <div class="p-4 sm:p-6">
                                    <input type="hidden" name="event_ids[]" value="{{ $schedule->espn_event_id }}">

                                    <!-- Away Team Radio -->
                                    <div class="mb-4">
                                        <div class="flex text-gray-800 items-center">
                                            <input id="away_team_{{ $schedule->id }}" name="team_ids[{{ $schedule->espn_event_id }}]" type="radio" value="{{ $schedule->away_team_id }}" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                                    {{ $userPick == $schedule->away_team_id ? 'checked' : '' }}>
                                            <label for="away_team_{{ $schedule->id }}" class="ml-3 block text-sm font-medium {{ $userPick == $schedule->away_team_id ? 'font-bold' : '' }}">
                                                {{ $schedule->awayTeam->team_name ?? 'Unknown' }}
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Home Team Radio -->
                                    <div class="flex items-center mb-2">
                                        <input id="home_team_{{ $schedule->id }}" name="team_ids[{{ $schedule->espn_event_id }}]" type="radio" value="{{ $schedule->home_team_id }}" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                                {{ $userPick == $schedule->home_team_id ? 'checked' : '' }}>
                                        <label for="home_team_{{ $schedule->id }}" class="ml-3 block text-sm font-medium {{ $userPick == $schedule->home_team_id ? 'font-bold' : '' }}">
                                            {{ $schedule->homeTeam->team_name ?? 'Unknown' }}
                                        </label>
                                    </div>

                                    <!-- Game Status (Footer) -->
                                    <div class="text-xs font-light relaxed game-footer mt-4 {{ $schedule->textColor }}">
                                        <p>{{ $schedule->status_type_detail ?? 'No status available' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Submit All Picks Button -->
                <div class="mt-6">
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Submit All Picks
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>