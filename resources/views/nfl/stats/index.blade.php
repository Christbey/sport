<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Navigation -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li>
                        <a href="#"
                           class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">NFL Stats Analysis</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Main Content Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 lg:p-8">
                    <!-- Header Section -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            <span class="inline-flex items-center">
                                NFL Stats Analysis
                                <svg class="w-6 h-6 ml-2 text-gray-500" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="M5 4 1 8l4 4m10-8 4 4-4 4M11 1 9 15"/>
                                </svg>
                            </span>
                        </h1>
                        <p class="text-base text-gray-600">
                            Select a query type below to analyze NFL statistics and generate insights.
                        </p>
                    </div>

                    <!-- Analysis Form -->
                    <form id="statsForm" method="GET" onsubmit="handleSubmit(event)" class="space-y-6">
                        <div class="space-y-2">
                            <label for="queryType" class="block text-sm font-medium text-gray-900">Query Type</label>
                            <select id="queryType"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    required>
                                <option value="">Choose analysis type...</option>
                                @foreach($queries as $key => $queryName)
                                    <option value="{{ $key }}">{{ $queryName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center">
                            <div class="inline-flex items-center">
                                <svg class="w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                                Run Analysis
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleSubmit(event) {
            event.preventDefault();
            const queryType = document.getElementById('queryType').value;

            if (!queryType) {
                // Show error message using Flowbite
                const targetEl = document.getElementById('alert-error');
                const toast = new Flowbite.Toast(targetEl, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();
                return;
            }

            window.location.href = '/nfl/stats/analysis/' + queryType;
        }
    </script>

    <!-- Error Toast (hidden by default) -->
    <div id="alert-error" class="fixed bottom-5 right-5 hidden items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50"
         role="alert">
        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
             viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
        </svg>
        <span class="sr-only">Error</span>
        <div class="ml-3 text-sm font-medium">
            Please select a query type before proceeding.
        </div>
        <button type="button"
                class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8"
                data-dismiss-target="#alert-error" aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
        </button>
    </div>
</x-app-layout>