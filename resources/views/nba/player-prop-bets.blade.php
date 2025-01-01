@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="min-h-screen bg-gray-100">
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center sm:text-left">NBA Statistics</h1>

                        {{-- Filters --}}
                        <form method="GET" action="{{ route('player-prop-bets.index') }}" class="mb-8 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                                {{-- View Type Toggle --}}
                                <div class="col-span-1 sm:col-span-2 space-y-2">
                                    <label class="block text-sm font-semibold text-gray-700">View Type:</label>
                                    <div class="flex flex-wrap items-center space-x-4">
                                        <label class="flex items-center space-x-2">
                                            <input type="radio" name="view_type" value="player"
                                                   class="text-indigo-600 focus:ring-indigo-500"
                                                    {{ request('view_type', 'player') === 'player' ? 'checked' : '' }}>
                                            <span>Player Stats</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="radio" name="view_type" value="team"
                                                   class="text-indigo-600 focus:ring-indigo-500"
                                                    {{ request('view_type') === 'team' ? 'checked' : '' }}>
                                            <span>Team Stats</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Date Filter --}}
                                <div class="space-y-2">
                                    <label for="date" class="block text-sm font-semibold text-gray-700">Select
                                        Date:</label>
                                    <select name="date" id="date"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">-- Choose a Date --</option>
                                        @foreach($eventDates as $eventDate)
                                            <option value="{{ $eventDate }}" {{ $date == $eventDate ? 'selected' : '' }}>
                                                {{ Carbon::parse($eventDate)->format('F d, Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Prop Type Filter --}}
                                <div class="space-y-2">
                                    <label for="prop_type" class="block text-sm font-semibold text-gray-700">Select Prop
                                        Type:</label>
                                    <select name="prop_type" id="prop_type"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">-- Choose a Type --</option>
                                        @foreach($propTypes as $type)
                                            <option value="{{ $type }}" {{ $propType == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Filter Button --}}
                                <div class="flex flex-col sm:flex-row items-center sm:justify-end space-y-4 sm:space-y-0 sm:space-x-4">
                                    <!-- Filter Button -->
                                    <button
                                            type="submit"
                                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2
               bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500"
                                    >
                                        Filter
                                    </button>

                                    <!-- Reset Link -->
                                    <a
                                            href="{{ route('player-prop-bets.index') }}"
                                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2
               bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 focus:ring-gray-300"
                                    >
                                        Reset
                                    </a>
                                </div>

                            </div>
                        </form>

                        {{-- Conditional Display --}}
                        <div class="overflow-x-auto">
                            @if(request('view_type', 'player') === 'player')
                                {{-- Player Stats Table --}}
                                @include('nba.partials.player-stats', ['playerOverStats' => $playerOverStats])
                            @elseif(request('view_type') === 'team')
                                {{-- Team Stats Table --}}
                                @include('nba.partials.team-stats', ['teamStats' => $teamStats])
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
