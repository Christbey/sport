<x-app-layout>
    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-4">Select a Query and Filter</h1>

        <form method="GET" action="{{ route('nfl.stats.results') }}" class="space-y-4">
            <!-- Query Selection -->
            <div>
                <label for="query" class="block text-sm font-medium text-gray-700">Choose a query:</label>
                <select id="query" name="query" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                    @foreach($queries as $key => $queryName)
                        <option value="{{ $key }}">{{ $queryName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Position Filter -->
{{--            <div>--}}
{{--                <label for="position" class="block text-sm font-medium text-gray-700">Filter by position (optional):</label>--}}
{{--                <select id="position" name="position" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">--}}
{{--                    <option value="">All Positions</option>--}}
{{--                    <option value="WR">Wide Receiver</option>--}}
{{--                    <option value="RB">Running Back</option>--}}
{{--                    <option value="TE">Tight End</option>--}}
{{--                    <!-- Add more positions as needed -->--}}
{{--                </select>--}}
{{--            </div>--}}

            <!-- Team Filter -->
            <div>
                <label for="team" class="block text-sm font-medium text-gray-700">Filter by team (optional):</label>
                <input type="text" id="team" name="team" placeholder="Enter team abbreviation" class="mt-1 block w-full pl-3 pr-3 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            </div>

            <!-- Week Filter -->
{{--            <div>--}}
{{--                <label for="week" class="block text-sm font-medium text-gray-700">Choose a week:</label>--}}
{{--                <select id="week" name="week" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">--}}
{{--                    @foreach($weeks as $weekName => $week)--}}
{{--                        <option value="{{ $weekName }}">{{ $weekName }}</option>--}}
{{--                    @endforeach--}}
{{--                </select>--}}
{{--            </div>--}}

            <!-- Submit Button -->
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Run Query
            </button>
        </form>
    </div>
</x-app-layout>
