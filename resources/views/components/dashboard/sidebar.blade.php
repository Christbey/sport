<aside class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
       aria-label="Sidebar" id="drawer-navigation">
    <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">


        <ul class="space-y-2">
            <!-- NFL Section -->
            <x-dashboard.sidebar-item
                    icon="football"
                    label="NFL"
                    :hasDropdown="true"
                    dropdownId="dropdown-nfl">
                @can('api tokens')
                    <li>
                        <a href="{{ route('nfl.detail') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            NFL Sheet
                        </a>
                    </li>
                @endcan
                @can('view nfl news')
                    <li>
                        <a href="{{ route('nfl.news.index') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            News
                        </a>
                    </li>
                @endcan
                @can('view nfl analytics')
                    <li>
                        <a href="{{ route('nfl.stats.index') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            Analytics
                        </a>
                    </li>
                @endcan
                <li>
                    <a href="{{ route('nfl.trends.config') }}"
                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        Trends
                    </a>
                </li>
                <li>
                    <a href="{{ route('player.trends.index') }}"
                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        Player Trends
                    </a>
                </li>
                @can('api tokens')
                    <li>
                        <a href="{{ route('team_rankings.scoring') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            Team Rankings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('nfl.qbr', ['week' => 1]) }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            QB Rankings
                        </a>
                    </li>
                @endcan
            </x-dashboard.sidebar-item>

            <!-- College Football Section -->
            <x-dashboard.sidebar-item
                    icon="football"
                    label="College Football"
                    :hasDropdown="true"
                    dropdownId="dropdown-cfb">
                <li>
                    <a href="{{ route('cfb.index') }}"
                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        Overview
                    </a>
                </li>
            </x-dashboard.sidebar-item>

            <!-- NBA Section -->
            <x-dashboard.sidebar-item
                    icon="basketball"
                    label="NBA"
                    :hasDropdown="true"
                    dropdownId="dropdown-nba">
                <li>
                    <a href="{{ route('player-prop-bets.index') }}"
                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        Player Props
                    </a>
                </li>
            </x-dashboard.sidebar-item>

            <!-- College Basketball Section -->
            <x-dashboard.sidebar-item
                    icon="basketball"
                    label="College Basketball"
                    :hasDropdown="true"
                    dropdownId="dropdown-cbb">
                <li>
                    <a href="{{ route('cbb.index') }}"
                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        Overview
                    </a>
                </li>
            </x-dashboard.sidebar-item>

            <!-- Pick'em Games Section -->
            <x-dashboard.sidebar-item
                    icon="game"
                    label="Pick'em Games"
                    :hasDropdown="true"
                    dropdownId="dropdown-pickem">
                @can('view picks')
                    <li>
                        <a href="{{ route('pickem.schedule') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            Submit Picks
                        </a>
                    </li>
                @endcan
                @can('view leaderboard')
                    <li>
                        <a href="{{ route('pickem.leaderboard') }}"
                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            Leaderboard
                        </a>
                    </li>
                @endcan
            </x-dashboard.sidebar-item>
        </ul>
    </div>
</aside>