<div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-900">Featured NFL Trends</h2>
    @php
        $randomTrends = [
            "The Kansas City Chiefs have won 8 of their last 10 home games.",
            "The Buffalo Bills have covered the spread in 5 of their last 6 games as underdogs.",
            "The Dallas Cowboys have gone over the total in 7 of their last 9 games.",
            "The Green Bay Packers have lost 4 of their last 5 away games by 10+ points.",
        ];
    @endphp

    <div class="space-y-3">
        @foreach($randomTrends as $trend)
            <div class="rounded-lg border bg-blue-50 border-blue-200 p-3">
                <p class="text-sm text-blue-700">{{ $trend }}</p>
            </div>
        @endforeach
    </div>
</div>