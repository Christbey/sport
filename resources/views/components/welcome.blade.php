<section>
    <div class="container mx-auto px-4 lg:px-0 py-12">
        <div class="grid h-full items-start lg:grid-cols-12 lg:gap-8">
            <!-- Left Column - Main Content -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-8 max-w-3xl">
                <h1 class="text-5xl lg:text-6xl xl:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Welcome to Picksports!
                </h1>

                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400">
                    Elevate your sports experience with Picksports. Whether you're joining the fun or competing against
                    your colleagues, Picksports offers an exciting way to enjoy every game.
                </p>

                @auth
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('show-chatgpt') }}"
                           class="inline-flex items-center justify-center py-4 px-6 font-medium text-center text-gray-900 rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"/>
                            </svg>
                            Pickly Sports Chat
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Right Column - Auth Box -->
            <div class="lg:col-span-5 xl:col-span-4 w-full max-w-md mt-12 lg:mt-0 self-start">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 shadow-lg">
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
                                <x-input id="email" class="block mt-2 w-full" type="email" name="email"
                                         :value="old('email')" required autofocus autocomplete="username"/>
                            </div>

                            <div>
                                <x-label for="password" value="{{ __('Password') }}"
                                         class="text-gray-700 dark:text-gray-300"/>
                                <x-input id="password" class="block mt-2 w-full" type="password" name="password"
                                         required autocomplete="current-password"/>
                            </div>

                            <div class="flex items-center">
                                <label for="remember_me" class="inline-flex items-center">
                                    <x-checkbox id="remember_me" name="remember"/>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                                </label>
                            </div>

                            <x-button class="w-full justify-center py-3">{{ __('Log in') }}</x-button>

                            <div class="flex flex-col space-y-4 text-center text-sm">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                       class="text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif

                                <a href="{{ route('register') }}"
                                   class="text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                    {{ __('Don\'t have an account? Sign up') }}
                                </a>
                            </div>
                        </form>
                    @else
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-medium text-gray-900 dark:text-white">
                                    Welcome Back {{ auth()->user()->name }}!
                                </h2>
                                @if(auth()->user()->hasActiveSubscription())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                        Active
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('subscription.manage') }}"
                               class="block w-full py-4 px-6 font-medium text-center text-white rounded-lg bg-[#4284F4] hover:bg-[#3372df] focus:ring-4 focus:ring-[#4284F4]/50 transition-colors duration-200">
                                Subscription
                            </a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>