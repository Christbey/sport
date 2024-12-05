@php use Illuminate\Pagination\LengthAwarePaginator; @endphp
<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('nfl.stats.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Analysis
                </a>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <!-- Header Section -->
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Stats Analysis</h1>
                            <p class="mt-2 text-sm text-gray-600">
                                Showing results for {{ Str::title(str_replace('_', ' ', $queryType)) }}
                            </p>
                            @if(isset($metadata['total_games']))
                                <p class="mt-1 text-xs text-gray-400">
                                    Total games analyzed: {{ number_format($metadata['total_games']) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Active Filters Display -->
                    <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                        <span class="text-blue-700 font-medium">
                            Showing games for:
                        </span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if(request('teamFilter'))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    Team: {{ request('teamFilter') }}
                                </span>
                            @endif
                            @if(request('conferenceFilter'))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    Conference: {{ request('conferenceFilter') }}
                                </span>
                            @endif
                            @if(request('divisionFilter'))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    Division: {{ request('divisionFilter') }}
                                </span>
                            @endif
                            @if(request('locationFilter'))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    Location: {{ Str::title(request('locationFilter')) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('nfl.stats.show', ['queryType' => $queryType]) }}"
                          class="mb-8 space-y-4 bg-gray-50 p-6 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Team Filter -->
                            <div>
                                <label for="teamFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Team
                                </label>
                                <select name="teamFilter" id="teamFilter"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">All Teams</option>
                                    @foreach($teamsList as $team)
                                        <option value="{{ $team }}" {{ request('teamFilter') == $team ? 'selected' : '' }}>
                                            {{ $team }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Conference Filter -->
                            <div>
                                <label for="conferenceFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Conference
                                </label>
                                <select name="conferenceFilter" id="conferenceFilter"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">All Conferences</option>
                                    <option value="AFC" {{ request('conferenceFilter') == 'AFC' ? 'selected' : '' }}>
                                        AFC
                                    </option>
                                    <option value="NFC" {{ request('conferenceFilter') == 'NFC' ? 'selected' : '' }}>
                                        NFC
                                    </option>
                                </select>
                            </div>

                            <!-- Division Filter -->
                            <div>
                                <label for="divisionFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Division
                                </label>
                                <select name="divisionFilter" id="divisionFilter"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">All Divisions</option>
                                    <option value="North" {{ request('divisionFilter') == 'North' ? 'selected' : '' }}>
                                        North
                                    </option>
                                    <option value="South" {{ request('divisionFilter') == 'South' ? 'selected' : '' }}>
                                        South
                                    </option>
                                    <option value="East" {{ request('divisionFilter') == 'East' ? 'selected' : '' }}>
                                        East
                                    </option>
                                    <option value="West" {{ request('divisionFilter') == 'West' ? 'selected' : '' }}>
                                        West
                                    </option>
                                </select>
                            </div>

                            <!-- Location Filter -->
                            <div>
                                <label for="locationFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Location
                                </label>
                                <select name="locationFilter" id="locationFilter"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">All Locations</option>
                                    <option value="home" {{ request('locationFilter') == 'home' ? 'selected' : '' }}>
                                        Home
                                    </option>
                                    <option value="away" {{ request('locationFilter') == 'away' ? 'selected' : '' }}>
                                        Away
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-4">
                            <button type="submit"
                                    class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Apply Filters
                            </button>
                        </div>
                    </form>

                    <!-- Stats Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                @foreach($tableHeadings as $heading)
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($data as $row)
                                @php
                                    $matchesTeam = !request('teamFilter') || $row->team_abv == request('teamFilter');
                                    $matchesConference = !request('conferenceFilter') || $row->conference == request('conferenceFilter');
                                    $matchesDivision = !request('divisionFilter') || $row->division == request('divisionFilter');
                                    $matchesLocation = !request('locationFilter') || strtolower($row->location_type) == strtolower(request('locationFilter'));

                                    $winPercentage = isset($row->wins, $row->losses) ?
                                        $row->wins / ($row->wins + $row->losses) : null;
                                @endphp

                                @if($matchesTeam && $matchesConference && $matchesDivision && $matchesLocation)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        @foreach($row as $key => $value)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if(is_numeric($value))
                                                    {{ number_format($value, 1) }}
                                                @else
                                                    {{ $value }}
                                                @endif

                                                @if($loop->first && isset($winPercentage))
                                                    @if($winPercentage > 0.7)
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                <svg class="w-4 h-4 mr-1" fill="none"
                                                                     stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2"
                                                                          d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                                                </svg>
                                                                Strong
                                                            </span>
                                                    @elseif($winPercentage > 0.5)
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <svg class="w-4 h-4 mr-1" fill="none"
                                                                     stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2"
                                                                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                                </svg>
                                                                Competitive
                                                            </span>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    @if($data instanceof LengthAwarePaginator)
                        <div class="mt-6">
                            {{ $data->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>