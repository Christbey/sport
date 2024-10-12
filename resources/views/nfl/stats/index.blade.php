<x-app-layout>
    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-4">Select a Query and Filter</h1>

        <form method="GET" action="{{ route('nfl.stats.results') }}" class="space-y-4">
            <!-- Query Selection -->
            <div>
                <label for="query" class="block text-sm font-medium text-gray-700">Choose a query:</label>
                <select id="query" name="query"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        required>
                    @foreach($queries as $key => $queryName)
                        <option value="{{ $key }}">{{ $queryName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="team" class="block text-sm font-medium text-gray-700">Filter by team (optional):</label>
                <input type="text" id="team" name="team" placeholder="Enter team abbreviation"
                       class="mt-1 block w-full pl-3 pr-3 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Run Query
            </button>
        </form>
    </div>
</x-app-layout>