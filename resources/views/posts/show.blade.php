@php
    use App\Helpers\MarkdownHelper;
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <!-- Inject Schema Markup into the head section -->
    <x-slot name="head">
        <x-schema-markup :schema="$schema"/>
    </x-slot>

    <div class="container mx-auto max-w-5xl px-4 py-8 flex flex-col lg:flex-row">
        <!-- Main Content -->
        <div class="lg:w-full lg:pr-8">
            <!-- Post Title -->
            <h1 class="text-4xl font-bold mb-4">{{ $post->title }}</h1>

            <!-- Post Meta Information -->
            <div class="flex items-center text-gray-500 text-xs mb-6 space-x-4">
                <!-- Calendar Icon -->
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-gray-400" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2
                               2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ Carbon::parse($post->game_date)->format('F j, Y') }}</span>
                </div>

                <!-- Clock Icon -->
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-gray-400" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $post->game_time }}</span>
                </div>

                <!-- Flag Icon -->
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-gray-400" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ $post->published ? 'Published' : 'Draft' }}</span>
                </div>
            </div>

            <!-- Post Details -->
            <div class="text-gray-700 text-xs mb-6">
                <span><strong>Week:</strong> {{ $post->week }}</span> |
                <span><strong>Season:</strong> {{ $post->season }}</span> |
                <span><strong>Away Team:</strong> {{ $post->away_team }}</span> |
                <span><strong>Home Team:</strong> {{ $post->home_team }}</span>
                {{-- Uncomment and adjust if you use predictions --}}
                {{-- <span><strong>Prediction:</strong> {!! MarkdownHelper::convert($post->prediction) !!}</span> --}}
            </div>

            <!-- Divider -->
            <hr class="mb-6">

            <!-- Post Content -->
            <article class="prose prose-lg max-w-none">
                {!! MarkdownHelper::convert($post->content) !!}
            </article>

            <!-- Back Button -->
            <div class="mt-8">
                <a href="{{ route('posts.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white
                    rounded hover:bg-blue-700 transition">
                    <!-- Arrow Left Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Posts
                </a>
            </div>
        </div>

        <!-- Sidebar -->
        {{--        <div class="lg:w-1/3 mt-8 lg:mt-0">--}}
        {{--            <div class="bg-gray-100 p-6 rounded-lg shadow">--}}
        {{--                <h2 class="text-xl font-semibold mb-4">About the Author</h2>--}}
        {{--                <div class="flex items-center mb-4">--}}
        {{--                    @if($post->author->profile_photo_path)--}}
        {{--                        <img src="{{ asset('storage/' . $post->author->profile_photo_path) }}"--}}
        {{--                             alt="{{ $post->author->name }}" class="w-12 h-12 rounded-full mr-4">--}}
        {{--                    @else--}}
        {{--                        <img src="https://via.placeholder.com/50" alt="Author" class="w-12 h-12 rounded-full mr-4">--}}
        {{--                    @endif--}}
        {{--                    <div>--}}
        {{--                        <p class="font-semibold">{{ $post->author->name }}</p>--}}
        {{--                        <p class="text-gray-600 text-sm">{{ $post->author->role ?? 'Author' }}</p>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--                <p class="text-gray-700 text-sm">--}}
        {{--                    {{ $post->author->bio ?? 'Passionate about NFL analysis and providing insightful predictions.' }}--}}
        {{--                </p>--}}
        {{--            </div>--}}
        {{--        </div>--}}
    </div>

    <!-- Prism.js for Syntax Highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
</x-app-layout>
