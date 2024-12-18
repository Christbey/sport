<!-- Footer Container -->
<footer class="p-4 bg-white md:p-8 lg:p-10 dark:bg-gray-800">
    <div class="mx-auto max-w-screen-xl text-center">
        <!-- Logo -->
        {{--        <a href="#" class="flex justify-center items-center text-2xl font-semibold text-gray-900 dark:text-white">--}}
        {{--            <x-application-logo class="mr-2 h-8"/>--}}
        {{--            {{ config('app.name') }}--}}
        {{--        </a>--}}

        <!-- Description -->
        {{--        <p class="my-6 text-gray-500 dark:text-gray-400">--}}
        {{--            {{ $description ?? 'Open-source library of over 400+ web components and interactive elements built for better web.' }}--}}
        {{--        </p>--}}


        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">
            © {{ date('Y') }}
            <a href="{{ url('/') }}" class="hover:underline">{{ config('app.name') }}™</a>.
            All Rights Reserved.
        </span>
    </div>
</footer>