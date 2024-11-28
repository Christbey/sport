<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('nfl.stats.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Predictions
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <div class="mb-8">
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

                    <!-- Add Filter Form -->
                    <form method="GET" action="{{ route('nfl.stats.show', ['queryType' => $queryType]) }}"
                          class="mb-4 space-y-4">
                        <!-- Filter by Team -->
                        <div>
                            <label for="teamFilter" class="text-sm font-medium text-gray-700">Filter by Team:</label>
                            <select name="teamFilter" id="teamFilter" class="ml-2 p-2 border border-gray-300 rounded">
                                <option value="">All Teams</option>
                                @foreach($teamsList as $team)
                                    <option value="{{ $team }}" {{ request('teamFilter') == $team ? 'selected' : '' }}>
                                        {{ $team }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter by Conference -->
                        <div>
                            <label for="conferenceFilter" class="text-sm font-medium text-gray-700">Filter by
                                Conference:</label>
                            <select name="conferenceFilter" id="conferenceFilter"
                                    class="ml-2 p-2 border border-gray-300 rounded">
                                <option value="">All Conferences</option>
                                <option value="AFC" {{ request('conferenceFilter') == 'AFC' ? 'selected' : '' }}>AFC
                                </option>
                                <option value="NFC" {{ request('conferenceFilter') == 'NFC' ? 'selected' : '' }}>NFC
                                </option>
                            </select>
                        </div>

                        <!-- Filter by Division -->
                        <div>
                            <label for="divisionFilter" class="text-sm font-medium text-gray-700">Filter by
                                Division:</label>
                            <select name="divisionFilter" id="divisionFilter"
                                    class="ml-2 p-2 border border-gray-300 rounded">
                                <option value="">All Divisions</option>
                                <option value="North" {{ request('divisionFilter') == 'North' ? 'selected' : '' }}>
                                    North
                                </option>
                                <option value="South" {{ request('divisionFilter') == 'South' ? 'selected' : '' }}>
                                    South
                                </option>
                                <option value="East" {{ request('divisionFilter') == 'East' ? 'selected' : '' }}>East
                                </option>
                                <option value="West" {{ request('divisionFilter') == 'West' ? 'selected' : '' }}>West
                                </option>
                            </select>
                        </div>

                        <!-- Filter by Location -->
                        <div>
                            <label for="locationFilter" class="text-sm font-medium text-gray-700">Filter by
                                Location:</label>
                            <select name="locationFilter" id="locationFilter"
                                    class="ml-2 p-2 border border-gray-300 rounded">
                                <option value="">All Locations</option>
                                <option value="home" {{ request('locationFilter') == 'home' ? 'selected' : '' }}>Home
                                </option>
                                <option value="away" {{ request('locationFilter') == 'away' ? 'selected' : '' }}>Away
                                </option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded">Filter</button>
                        </div>
                    </form>

                    <!-- Stats Table -->
                    <div class="overflow-x-auto">
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
                                    // Filtering conditions based on the data fields available
                                    $matchesTeam = !request('teamFilter') || $row->team_abv == request('teamFilter');
                                    $matchesConference = !request('conferenceFilter') || $row->conference == request('conferenceFilter'); // Use 'conference' here
                                    $matchesDivision = !request('divisionFilter') || $row->division == request('divisionFilter');
                                    $matchesLocation = !request('locationFilter') || strtolower($row->location_type) == strtolower(request('locationFilter'));
                                @endphp

                                @if($matchesTeam && $matchesConference && $matchesDivision && $matchesLocation)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        @foreach($row as $value)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if(is_numeric($value))
                                                    {{ number_format($value, 1) }}
                                                @else
                                                    {{ $value }}
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
                    @if($data instanceof Paginator)
                        <div class="mt-6">
                            {{ $data->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
