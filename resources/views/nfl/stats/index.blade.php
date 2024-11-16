<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-gray-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h1 class="text-2xl font-bold text-gray-900">NFL Stats Query Builder</h1>
                    </div>

                    <p class="mt-2 text-gray-600 text-sm">Select your query parameters below to analyze NFL
                        statistics.</p>

                    <form method="GET" action="{{ route('nfl.stats.results') }}" class="mt-8 space-y-6">
                        <!-- Query Selection -->
                        <div class="space-y-1">
                            <label for="query" class="block text-sm font-medium text-gray-700">Query Type</label>
                            <select id="query" name="query"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    required>
                                <option value="">Select a query type...</option>
                                @foreach($queries as $key => $queryName)
                                    <option value="{{ $key }}" {{ old('query') == $key ? 'selected' : '' }}>
                                        {{ $queryName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Team Filter -->
                        <div class="space-y-1">
                            <label for="team" class="block text-sm font-medium text-gray-700">Team Filter</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="text" id="team" name="team"
                                       value="{{ old('team') }}"
                                       placeholder="e.g., NE, GB, KC"
                                       class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Optional: Enter team abbreviation</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button type="submit"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Run Analysis
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>