{{-- resources/views/college-football/elo.blade.php --}}
@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        {{-- Header Section --}}
        <x-college-football.header/>

        {{-- Filter Section --}}
        <x-college-football.week-filter
                :years="$years"
                :selectedYear="$selectedYear"
                :selectedWeek="$selectedWeek"
                :eloFilter="$eloFilter"
        />

        {{-- Week Display Banner --}}
        @if(request('week'))
            <x-college-football.week-banner :week="request('week')"/>
        @endif

        {{-- ELO Ratings Table --}}
        <x-college-football.elo-table :teams="$teams"/>

    </div>
</x-app-layout>