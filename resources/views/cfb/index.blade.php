<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('College Football Predictions') }}
            <x-badge color="blue">Week {{ $week }}</x-badge>

        </h2>
    </x-slot>
    <div class="container px- mx-auto max-w-6xl">
        <div class="flex items-center justify-between mb-8">

            <!-- Week Selection Form -->
            <x-week-selection-form
                    :weeks="$weeks"
                    :selectedWeek="$week"
            />

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
