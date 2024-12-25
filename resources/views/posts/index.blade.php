<x-app-layout>
    <div class="space-y-4 max-w-5xl mx-auto my-4">

        @foreach($posts as $post)
            <a href="{{ $post->custom_url }}"
               class="block bg-white rounded-lg shadow hover:bg-gray-50 transition duration-200 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">{{ $post->title }}</h2>
                <div class="text-sm text-gray-500 mb-4">
                    Week {{ $post->week }}, Season {{ $post->season }} |
                    Away: <span class="font-medium">{{ $post->away_team }}</span> |
                    Home: <span class="font-medium">{{ $post->home_team }}</span>
                </div>
                <p class="text-gray-700">
                    {{ Str::limit(strip_tags($post->content), 150, '...') }}
                </p>
            </a>
        @endforeach
    </div>

    <!-- Pagination Links -->
    <div class="mt-6 flex justify-center">
        {{ $posts->links()  }}
    </div>

    @if($posts->isEmpty())
        <div class="mt-6 text-center">
            <p class="text-gray-500">No posts found for this week.</p>
        </div>
    @endif
</x-app-layout>
