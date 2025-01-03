@php
    use Carbon\Carbon;
@endphp
@props(['dates', 'selectedDate'])

<form action="{{ route('cbb.index') }}" method="GET" class="mt-4 lg:mt-0">
    <div class="flex items-center space-x-4">
        <div class="min-w-[200px]">
            <label for="game_date" class="block text-sm font-medium text-gray-700 mb-1">Game Date</label>
            <select name="game_date" id="game_date"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">All Dates</option>
                @foreach($dates as $date)
                    <option value="{{ $date->game_date }}"
                            {{ $selectedDate == $date->game_date ? 'selected' : '' }}>
                        {{ Carbon::parse($date->game_date)->format('l, F j, Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-cbb.filter-button/>
    </div>
</form>