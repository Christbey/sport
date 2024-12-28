<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center">
                    Picksports
                </a>

                <!-- Navigation Menu -->
                @php
                    $navigationItems = [
                        'Pickem' => [
                            ['label' => 'Submit Picks', 'route' => 'pickem.schedule', 'permission' => 'view picks'],
                            ['label' => 'Leaderboard', 'route' => 'pickem.leaderboard', 'permission' => 'view leaderboard']
                        ],
                        'NFL' => [
                            ['label' => 'NFL Sheet', 'route' => 'nfl.detail', 'permission' => 'api tokens'],
                            ['label' => 'NFL News', 'route' => 'nfl.news.index', 'permission' => 'view nfl news'],
                            ['label' => 'Analytics', 'route' => 'nfl.stats.index', 'permission' => 'view nfl analytics'],
                            ['label' => 'Offense Defense', 'route' => 'team_rankings.scoring', 'permission' => 'api tokens'],
                            ['label' => 'Covers Games', 'route' => 'covers.games', 'permission' => 'api tokens'],
                            ['label' => 'ESPN QBR', 'route' => 'nfl.qbr', 'params' => ['week' => 1], 'permission' => 'api tokens'],
//                            ['label' => 'Elo Predictions', 'route' => 'nfl.elo.index', '' => ''],
                        ],
                        'Predictions' => [
                            ['label' => 'College Football', 'route' => 'cfb.index'],
                            ['label' => 'College Basketball', 'route' => 'cbb.index'],
                            ['label' => 'National Football League', 'route' => 'nfl.elo.table'],
                       ['label' => 'NFL Trends', 'route' => 'nfl.trends.config'],
                        ],
                    ];

    // Filter items based on permissions
    $navigationItems = collect($navigationItems)->map(function($items, $title) {
        $filteredItems = collect($items)->filter(function($item) {
            if (isset($item['permission'])) {
                return auth()->user()?->can($item['permission']);
            }
            if (isset($item['role'])) {
                return auth()->user()?->hasRole($item['role']);
            }
            return true;
        })->all();

        return $filteredItems;
    })->filter(function($items) {
        return !empty($items);
    })->all();
                @endphp

                        <!-- Desktop Navigation -->
                <div class="hidden sm:flex space-x-8 sm:ms-10">
                    @foreach($navigationItems as $title => $items)
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:bg-gray-50 transition">
                                {{ $title }}
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    @foreach($items as $item)
                                        <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Side Navigation -->
            <div class="hidden sm:flex items-center sm:ml-6 space-x-4">
                @auth
                    {{-- View Teams permission --}}
                    @haspermission('view teams')
                    @if (Laravel\Jetstream\Jetstream::hasTeamFeatures() && Auth::user()->currentTeam)
                        <!-- Teams Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:bg-gray-50 transition">
                                {{ Auth::user()->currentTeam->name }}
                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                </svg>
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute right-0 mt-2 w-60 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}"
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Team Settings
                                    </a>

                                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                        <a href="{{ route('teams.create') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Create New Team
                                        </a>
                                    @endcan

                                    @if (Auth::user()->allTeams()->count() > 1)
                                        <div class="border-t border-gray-200"></div>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Switch Teams
                                        </div>

                                        @foreach (Auth::user()->allTeams() as $team)
                                            <form method="POST" action="{{ route('current-team.update') }}"
                                                  class="block px-4 py-2">
                                                @method('PUT')
                                                @csrf
                                                <input type="hidden" name="team_id" value="{{ $team->id }}">

                                                <button type="submit"
                                                        class="w-full text-left flex items-center text-sm text-gray-700 hover:bg-gray-100">
                                                    @if ($team->id === Auth::user()->currentTeam->id)
                                                        <svg class="mr-2 h-5 w-5 text-green-400" fill="none"
                                                             stroke-linecap="round" stroke-linejoin="round"
                                                             stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @endif
                                                    {{ $team->name }}
                                                </button>
                                            </form>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    @endhaspermission
                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open"
                                class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}"
                                     class="w-8 h-8 rounded-full">
                            @else
                                {{ Auth::user()->name }}
                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                </svg>
                            @endif
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <div class="block px-4 py-2 text-xs text-gray-400">Manage Account</div>
                                <a href="{{ route('profile.show') }}"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                @haspermission('api tokens')
                                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                    <a href="{{ route('api-tokens.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">API Tokens</a>
                                @endif
                                @endhaspermission
                                <div class="border-t border-gray-200"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm text-gray-700 hover:text-gray-900">Register</a>
                    @endif
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button @click="open = !open" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="open" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($navigationItems as $title => $items)
                <div x-data="{ subOpen: false }" class="space-y-1">
                    <button @click="subOpen = !subOpen"
                            class="w-full flex items-center px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50">
                        {{ $title }}
                        <svg class="ml-auto h-5 w-5" :class="{ 'transform rotate-180': subOpen }" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="subOpen" class="pl-4">
                        @foreach($items as $item)
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                               class="block py-2 pl-4 pr-4 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @auth
            <!-- Mobile Team Management -->
            @if (Laravel\Jetstream\Jetstream::hasTeamFeatures() && Auth::user()->currentTeam)
                <div class="border-t border-gray-200 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">Team Management</div>
                        <div class="mt-3 space-y-1">
                            <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}"
                               class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                                Team Settings
                            </a>
                            @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                <a href="{{ route('teams.create') }}"
                                   class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                                    Create New Team
                                </a>
                            @endcan

                            @if (Auth::user()->allTeams()->count() > 1)
                                <div class="border-t border-gray-200 mt-2 pt-2">
                                    <div class="block px-4 py-2 text-xs text-gray-400">Switch Teams</div>
                                    @foreach (Auth::user()->allTeams() as $team)
                                        <form method="POST" action="{{ route('current-team.update') }}">
                                            @method('PUT')
                                            @csrf
                                            <input type="hidden" name="team_id" value="{{ $team->id }}">

                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    @if ($team->id === Auth::user()->currentTeam->id)
                                                        <svg class="mr-2 h-5 w-5 text-green-400" fill="none"
                                                             stroke-linecap="round" stroke-linejoin="round"
                                                             stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @endif
                                                    {{ $team->name }}
                                                </div>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Mobile User Profile Section -->
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center px-4">
                    <div class="flex-shrink-0">
                        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                            <img class="h-10 w-10 rounded-full" src="{{ Auth::user()->profile_photo_url }}"
                                 alt="{{ Auth::user()->name }}">
                        @endif
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="{{ route('profile.show') }}"
                       class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                        Profile
                    </a>
                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <a href="{{ route('api-tokens.index') }}"
                           class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                            API Tokens
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Mobile Guest Links -->
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="space-y-1">
                    <a href="{{ route('login') }}"
                       class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                        Login
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                            Register
                        </a>
                    @endif
                </div>
            </div>
        @endauth
    </div>
</nav>