<x-app-layout>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">NFL Pick'em</h1>
                    <p class="mt-2 text-sm text-gray-600 ">Make your predictions for this week's
                        games</p>
                </div>

                <!-- Week Selection -->
                <div class="mt-4 md:mt-0">
                    <form id="weekForm" method="GET" action="{{ route('pickem.schedule') }}"
                          class="bg-white rounded-lg shadow-sm p-2">
                        <div class="flex items-center space-x-2">
                            <select name="game_week" id="game_week"
                                    class="block w-40 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
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
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Error Messages -->
            @if(session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Games Grid -->
            <form action="{{ route('pickem.pickWinner') }}" method="POST" x-data="{ hasChanges: false }">
                @csrf
                <div class="space-y-8">
                    @if($schedules->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 ">No games scheduled</h3>
                            <p class="mt-1 text-sm text-gray-500">There are no games scheduled for
                                this week.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-24">
                            @foreach($schedules as $schedule)
                                @php
                                    $userPick = $userSubmissions[$schedule->espn_event_id]->team_id ?? null;
                                @endphp
                                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                    <!-- Game Header -->
                                    <div class="px-4 py-3 bg-gray-50  border-b border-gray-200">
                                        <div class="text-xs font-medium text-gray-500 flex items-center justify-between">
                                            <span>{{ $schedule->status_type_detail ?? 'Time TBD' }}</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Week {{ $game_week }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-4 space-y-4">
                                        <input type="hidden" name="event_ids[]" value="{{ $schedule->espn_event_id }}">

                                        <!-- Teams Selection -->
                                        <div class="space-y-3">
                                            <!-- Away Team -->
                                            <label class="relative block cursor-pointer">
                                                <input type="radio"
                                                       name="team_ids[{{ $schedule->espn_event_id }}]"
                                                       value="{{ $schedule->away_team_id }}"
                                                       {{ $userPick == $schedule->away_team_id ? 'checked' : '' }}
                                                       class="sr-only peer"
                                                       @change="hasChanges = true">
                                                <div class="flex items-center p-4 rounded-lg border-2 transition-all duration-200
                                                    peer-checked:border-blue-500 peer-checked:bg-blue-50
                                                    {{ $userPick == $schedule->away_team_id
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 ' }}">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex justify-between">
                                                            <div class="text-sm font-medium text-gray-900 ">
                                                                {{ $schedule->awayTeam->team_name ?? 'Unknown' }}
                                                            </div>
                                                            <div class="text-sm text-gray-500 ">
                                                                {{ $schedule->away_team_record ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>

                                            <!-- Home Team -->
                                            <label class="relative block cursor-pointer">
                                                <input type="radio"
                                                       name="team_ids[{{ $schedule->espn_event_id }}]"
                                                       value="{{ $schedule->home_team_id }}"
                                                       {{ $userPick == $schedule->home_team_id ? 'checked' : '' }}
                                                       class="sr-only peer"
                                                       @change="hasChanges = true">
                                                <div class="flex items-center p-4 rounded-lg border-2 transition-all duration-200
                                                    peer-checked:border-blue-500 peer-checked:bg-blue-50
                                                    {{ $userPick == $schedule->home_team_id
                                                        ? 'border-blue-500 bg-blue-50 '
                                                        : 'border-gray-200 ' }}">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex justify-between">
                                                            <div class="text-sm font-medium text-gray-900 ">
                                                                {{ $schedule->homeTeam->team_name ?? 'Unknown' }}
                                                            </div>
                                                            <div class="text-sm text-gray-500 d">
                                                                {{ $schedule->home_team_record ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Submit Button -->
                        <div class="fixed bottom-0 inset-x-0 pb-6 bg-gradient-to-t from-gray-50 via-gray-50 to-transparent">
                            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                                <button type="submit"
                                        x-bind:class="{ 'opacity-50 cursor-not-allowed': !hasChanges }"
                                        x-bind:disabled="!hasChanges"
                                        class="w-full flex justify-center py-3 px-4 rounded-lg shadow-lg text-sm font-medium text-white
                                               bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                               transition-all duration-200">
                                    <span x-text="hasChanges ? 'Save Picks' : 'No Changes'"></span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('pickemForm', () => ({
                    hasChanges: false,

                    init() {
                        this.$watch('hasChanges', (value) => {
                            if (value) {
                                window.onbeforeunload = () => true;
                            } else {
                                window.onbeforeunload = null;
                            }
                        });
                    }
                }));
            });
        </script>
    @endpush
</x-app-layout>