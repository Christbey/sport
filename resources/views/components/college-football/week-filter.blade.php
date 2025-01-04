{{-- resources/views/components/college-football/week-filter.blade.php --}}
<div class="mb-6">
    <form action="" method="GET" class="flex flex-wrap gap-2">
        <select name="year"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            @foreach($years as $year)
                <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>

        <select name="week"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            <option value="">Select Week</option>
            @for($i = 1; $i <= 16; $i++)
                <option value="{{ $i }}" {{ request('week', 1) == $i ? 'selected' : '' }}>Week {{ $i }}</option>
            @endfor
        </select>

        <select name="elo_filter"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
            <option value="">All</option>
            <option value="high" {{ request('elo_filter', 'high') == 'high' ? 'selected' : '' }}>Elite</option>
            <option value="medium" {{ request('elo_filter') == 'medium' ? 'selected' : '' }}>Mid</option>
            <option value="low" {{ request('elo_filter') == 'low' ? 'selected' : '' }}>Scurbs</option>
        </select>

        <button type="submit"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
            Filter
        </button>

        @if(request()->hasAny(['week', 'elo_filter', 'year']) && (request('elo_filter') !== 'high' || request('week') != 1 || request('year') != date('Y')))
            <a href="{{ url()->current() }}"
               class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                Reset
            </a>
        @endif
    </form>
</div>