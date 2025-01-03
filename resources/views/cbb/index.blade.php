@php use Carbon\Carbon; @endphp
<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        {{-- Header Section --}}
        <x-cbb.header/>

        {{-- Filter Section --}}
        <x-cbb.filter-form
                :dates="$dates"
                :selectedDate="$selectedDate"
        />

        {{-- Date Display Banner --}}
        @if($selectedDate)
            <x-cbb.date-banner :date="$selectedDate"/>
        @endif

        {{-- Games Table --}}
        <x-cbb.games-table :hypotheticals="$hypotheticals"/>
    </div>
</x-app-layout>