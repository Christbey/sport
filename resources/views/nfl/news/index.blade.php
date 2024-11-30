<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- Header Section -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">NFL News</h1>
                    <p class="mt-2 text-gray-600">Stay updated with the latest NFL headlines</p>
                </div>
                <!-- Optional: Add a filter or search button here -->
                <div class="hidden sm:block">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                        </svg>
                        Latest First
                    </button>
                </div>
            </div>

            <!-- News Feed -->
            <div class="space-y-6">
                @foreach ($newsItems as $news)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full">
                                        News
                                    </span>
                                    <time class="text-sm text-gray-500">
                                        {{ $news->created_at->format('M j, Y') }}
                                    </time>
                                </div>

                                <h3 class="text-lg font-semibold text-gray-900 group">
                                    <a href="{{ $news->link }}" target="_blank"
                                       class="hover:text-indigo-600 transition-colors duration-200 flex items-center gap-2">
                                        {{ $news->title }}
                                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </h3>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $newsItems->links() }}
            </div>
        </div>
    </div>
</x-app-layout>