@php
    use carbon\Carbon;
@endphp
<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Grid Layout --}}
        <form method="GET" action="{{ route('player-prop-bets.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {{-- View Type Card --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">View Type</h2>
                    <div class="flex flex-col space-y-2">
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

                {{-- Date Filter Card --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Date</h2>
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

                {{-- Prop Type Card --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Prop Type</h2>
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

                {{-- Actions Card --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
                    <div class="flex flex-col space-y-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                            Filter
                        </button>
                        <a href="{{ route('player-prop-bets.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 focus:ring-gray-300">
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>

        {{-- Stats Content --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">NBA Statistics</h1>

                <div class="overflow-x-auto">
                    @if(request('view_type', 'player') === 'player')
                        @include('nba.partials.player-stats', ['playerOverStats' => $playerOverStats])
                    @elseif(request('view_type') === 'team')
                        @include('nba.partials.team-stats', ['teamStats' => $teamStats])
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>