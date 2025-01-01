<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('College Football Predictions') }}
            <x-badge color="blue">Week {{ $week }}</x-badge>

        </h2>
    </x-slot>
    <div class="container px-3 lg:px-0 mx-auto max-w-6xl">
        <div class="flex items-center justify-between mb-8">

            <!-- Week Selection Form -->
            {{--            <x-week-selection-form--}}
            {{--                    :weeks="$weeks"--}}
            {{--                    :selectedWeek="$week"--}}
            {{--            />--}}
            {{--            <form action="{{ route('cfb.index') }}" method="GET" class="ml-4">--}}
            {{--                <select name="season_type" onchange="this.form.submit()"--}}
            {{--                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">--}}
            {{--                    --}}{{--                    <option value="regular" {{ $seasonType == 'regular' ? 'selected' : '' }}>Regular Season</option>--}}
            {{--                    <option value="postseason" {{ $seasonType == 'postseason' ? 'selected' : '' }}>Postseason</option>--}}
            {{--                </select>--}}
            {{--                <input type="hidden" name="week" value="{{ $week }}">--}}
            {{--            </form>--}}

        </div>
        <x-prediction-stats-card :weeklyStats="$weeklyStats"/>

        @if($hypotheticals->isEmpty())
            <!-- resources/views/components/alert.blade.php -->
            <x-alert color="gray">
                <p>No predictions available for Week {{ $week }}</p>
                <p class="text-sm mt-2">Check back later for updates</p>
            </x-alert>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($hypotheticals as $game)
                    <x-cfb.cards :game="$game"/>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
