@php use App\Models\Post; @endphp

<section>
    <div class="container mx-auto max-w-screen-xl px-4 py-12">
        <div class="grid h-full items-start lg:grid-cols-12 gap-8">
            <!-- Left Column - Main Content -->
            <div class="lg:col-span-7 xl:col-span-8 max-w-3xl space-y-8">
                <h1 class="text-5xl lg:text-6xl xl:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Welcome to Picksports!
                </h1>
                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400">
                    Elevate your sports experience with Picksports. Whether you're joining the fun or competing against
                    your colleagues, Picksports offers an exciting way to enjoy every game.
                </p>

                @auth
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a
                                href="{{ route('show-chatgpt') }}"
                                class="inline-flex items-center justify-center px-6 py-4 text-gray-900 transition-colors duration-200 border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                        >
                            <svg
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                            >
                                <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"
                                />
                            </svg>
                            Pickly Sports Chat
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Right Column - Auth Box -->
            <div class="lg:col-span-5 xl:col-span-4 mt-12 lg:mt-0 self-start">
                <div class="p-8 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 shadow-lg">
                    @guest
                        <x-validation-errors class="mb-4"/>

                        @session('status')
                        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">{{ $value }}</div>
                        @endsession

                        <form method="POST" action="{{ route('login') }}" class="space-y-6">
                            @csrf
                            <div>
                                <x-label for="email" value="{{ __('Email') }}"
                                         class="text-gray-700 dark:text-gray-300"/>
                                <x-input
                                        id="email"
                                        class="block w-full mt-2"
                                        type="email"
                                        name="email"
                                        :value="old('email')"
                                        required
                                        autofocus
                                        autocomplete="username"
                                />
                            </div>

                            <div>
                                <x-label for="password" value="{{ __('Password') }}"
                                         class="text-gray-700 dark:text-gray-300"/>
                                <x-input
                                        id="password"
                                        class="block w-full mt-2"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                />
                            </div>

                            <div class="flex items-center">
                                <label for="remember_me" class="inline-flex items-center">
                                    <x-checkbox id="remember_me" name="remember"/>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Remember me') }}
                                    </span>
                                </label>
                            </div>

                            <x-button class="w-full py-3 justify-center">
                                {{ __('Log in') }}
                            </x-button>

                            <div class="flex flex-col text-center text-sm space-y-4">
                                @if (Route::has('password.request'))
                                    <a
                                            href="{{ route('password.request') }}"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                    >
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif

                                <a
                                        href="{{ route('register') }}"
                                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                >
                                    {{ __('Don\'t have an account? Sign up') }}
                                </a>
                            </div>
                        </form>
                    @else
                        @auth
                            @php
                                // Fetch 3 random posts
                                $randomPosts = Post::inRandomOrder()->limit(3)->get();
                            @endphp

                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-medium text-gray-900 dark:text-white">
                                        Welcome Back {{ auth()->user()->name }}!
                                    </h2>
                                    @if(auth()->user()->hasActiveSubscription())
                                        <span
                                                class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full"
                                        >
                                            <svg
                                                    class="w-4 h-4 mr-1"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                            >
                                                <path
                                                        fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z"
                                                        clip-rule="evenodd"
                                                />
                                            </svg>
                                            Active
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Show 3 random posts --}}
                            <div class="p-4 mt-6 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <h3 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">
                                    Recommended Posts
                                </h3>
                                <ul class="space-y-2">
                                    @foreach($randomPosts as $post)
                                        <li>
                                            <a
                                                    href="{{ route('posts.show', [
                                                    'season' => $post->season,
                                                    'week' => $post->week,
                                                    'game_date' => $post->game_date->format('Y-m-d'),
                                                    'slug' => $post->slug
                                                ]) }}"
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                {{ $post->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endauth
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>
