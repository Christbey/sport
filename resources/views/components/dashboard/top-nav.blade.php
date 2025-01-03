<nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
    <div class="flex flex-wrap justify-between items-center">
        <!-- Left side -->
        <div class="flex justify-start items-center">
            <!-- Mobile menu button -->
            <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                    aria-controls="drawer-navigation"
                    class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd"
                          d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                          clip-rule="evenodd"></path>
                </svg>
            </button>

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center justify-between mr-4">
                <x-application-mark class="block h-9 w-auto"/>
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white ml-3">
                    {{ config('app.name') }}
                </span>
            </a>

            <!-- Search -->
        </div>

        <!-- Right side -->
        <div class="flex items-center lg:order-2">
            {{--                    <x-dashboard.notifications/>--}}
            {{--                    <x-dashboard.apps-menu/>--}}
            <x-dashboard.user-menu/>
        </div>
    </div>
</nav>