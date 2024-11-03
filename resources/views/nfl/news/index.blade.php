<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <h1 class="text-3xl font-bold mb-8">NFL News</h1>
        <ol class="relative border-l border-gray-200 ">
            @foreach ($newsItems as $news)
                <li class="mb-10 ml-6">

                    <time class="block mb-2 text-sm font-normal leading-none text-gray-400 ">
                        {{ $news->created_at->format('F j, Y, g:i a') }}
                    </time>
                    <div class="flex items-center justify-between">
                        <h3 class=" font-semibold text-gray-800 ">
                            <a href="{{ $news->link }}" target="_blank" class="hover:underline">
                                {{ $news->title }}
                            </a>
                        </h3>
                    </div>
                </li>
            @endforeach
        </ol>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $newsItems->links() }}
        </div>
    </div>
</x-app-layout>