@props(['title', 'trends'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ $title }}</h2>
    <div class="space-y-3">
        @foreach($trends as $trend)
            <x-nfl.trend-item :trend="$trend"/>
        @endforeach
    </div>
</div>