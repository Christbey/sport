@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="min-h-screen bg-gray-100">
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6">NBA Statistics</h1>

                        {{-- Filters --}}
                        <form method="GET" action="{{ route('player-prop-bets.index') }}" class="mb-8 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                {{-- View Type Toggle --}}
                                <div class="col-span-4 space-y-2">
                                    <label class="block text-sm font-semibold text-gray-700">View Type:</label>
                                    <div class="flex items-center space-x-4">
                                        <label>
                                            <input type="radio" name="view_type" value="player"
                                                    {{ request('view_type', 'player') === 'player' ? 'checked' : '' }}>
                                            Player Stats
                                        </label>
                                        <label>
                                            <input type="radio" name="view_type" value="team"
                                                    {{ request('view_type') === 'team' ? 'checked' : '' }}> Team Stats
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
                                <div class="flex items-end space-x-4">
                                    <button type="submit"
                                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                        Filter
                                    </button>
                                    <a href="{{ route('player-prop-bets.index') }}"
                                       class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- Conditional Display --}}
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
</x-app-layout>
