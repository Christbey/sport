<section class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto min-h-screen px-4 py-12 lg:py-16">
        <div class="grid h-full place-items-center lg:grid-cols-12 lg:gap-8">
            <!-- Hero Content -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-8 max-w-3xl">
                <h1 class="text-5xl lg:text-6xl xl:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Welcome to Picksports!
                </h1>

                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400">
                    Elevate your sports experience with Picksports. Whether you're joining the fun or competing against
                    your colleagues, Picksports offers an exciting way to enjoy every game.
                </p>

                <!-- Action Buttons -->
                @auth
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('pickem.schedule') }}"
                           class="inline-flex items-center justify-center py-4 px-6 font-medium text-center text-gray-900 rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 transition-colors duration-200">
                            Submit Picks
                            <svg class="ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </a>

                        <a href="{{ route('pickem.leaderboard') }}"
                           class="inline-flex items-center justify-center py-4 px-6 font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"></path>
                            </svg>
                            Leaderboard
                        </a>
                    </div>
                @endauth

                <!-- Stats Grid -->
                <div class="hidden md:grid grid-cols-1 sm:grid-cols-3 gap-8 pt-12 mt-8 border-t border-gray-300 dark:border-gray-700">
                    <div class="flex items-center">
                        <span class="text-4xl lg:text-5xl font-extrabold dark:text-white">42k</span>
                        <div class="ml-4 text-lg text-gray-500 dark:text-gray-400">
                            <div>Active</div>
                            <div>Players</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-4xl lg:text-5xl font-extrabold dark:text-white">3k</span>
                        <div class="ml-4 text-lg text-gray-500 dark:text-gray-400">
                            <div>Weekly</div>
                            <div>Games</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-4xl lg:text-5xl font-extrabold dark:text-white">560k</span>
                        <div class="ml-4 text-lg text-gray-500 dark:text-gray-400">
                            <div>Total</div>
                            <div>Picks</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Auth Box -->
            <div class="lg:col-span-5 xl:col-span-4 w-full max-w-md mt-12 lg:mt-0">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 shadow-lg">
                    @guest
                        <x-validation-errors class="mb-4"/>

                        @session('status')
                        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ $value }}
                        </div>
                        @endsession

                        <form method="POST" action="{{ route('login') }}" class="space-y-6">
                            @csrf

                            <div>
                                <x-label for="email" value="{{ __('Email') }}"
                                         class="text-gray-700 dark:text-gray-300"/>
                                <x-input id="email" class="block mt-2 w-full" type="email" name="email"
                                         :value="old('email')" required autofocus autocomplete="username"/>
                            </div>

                            <div>
                                <x-label for="password" value="{{ __('Password') }}"
                                         class="text-gray-700 dark:text-gray-300"/>
                                <x-input id="password" class="block mt-2 w-full" type="password" name="password"
                                         required autocomplete="current-password"/>
                            </div>

                            <div class="flex items-center justify-between">
                                <label for="remember_me" class="inline-flex items-center">
                                    <x-checkbox id="remember_me" name="remember"/>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                                </label>
                            </div>

                            <div class="flex flex-col space-y-4">
                                <x-button class="w-full justify-center py-3">
                                    {{ __('Log in') }}
                                </x-button>

                                @if (Route::has('password.request'))
                                    <a class="text-sm text-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200"
                                       href="{{ route('password.request') }}">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif
                            </div>

                            <div class="text-center">
                                <a href="{{ route('register') }}"
                                   class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200">
                                    {{ __('Don\'t have an account? Sign up') }}
                                </a>
                            </div>
                        </form>
                    @else
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                            Welcome Back!
                        </h2>

                        <div class="flex flex-col space-y-4">
                            <a href="{{ route('ask-chatgpt') }}"
                               class="w-full py-4 px-6 font-medium text-center text-white rounded-lg bg-[#4284F4] hover:bg-[#3372df] focus:ring-4 focus:ring-[#4284F4]/50 transition-colors duration-200">
                                PickPal
                            </a>

                            <a href="{{ route('billing.portal') }}"
                               class="w-full py-4 px-6 font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700 transition-colors duration-200">
                                Subscription
                            </a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>