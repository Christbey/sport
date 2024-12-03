<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Back Button -->

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">NFL Stats Analysis</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Select a query type to analyze NFL statistics
                        </p>
                    </div>
                    <form id="statsForm" method="GET" onsubmit="handleSubmit(event)" class="space-y-6">
                        <!-- Query Selection -->
                        <div class="space-y-1">
                            <label for="queryType" class="block text-sm font-medium text-gray-700">Query Type</label>
                            <select id="queryType"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    required>
                                <option value="">Select a query type...</option>
                                @foreach($queries as $key => $queryName)
                                    <option value="{{ $key }}">
                                        {{ $queryName }}
                                    </option>
                                @endforeach
                            </select>
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

    <script>
        function handleSubmit(event) {
            event.preventDefault();
            const queryType = document.getElementById('queryType').value;
            if (queryType) {
                window.location.href = '/nfl/stats/analysis/' + queryType;
            }
        }
    </script>
</x-app-layout>