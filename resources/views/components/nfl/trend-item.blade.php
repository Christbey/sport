@props(['trend'])

@php
    $trend = (string)$trend;

    // Define terms that indicate positive/negative trends
    $positiveTerms = ['won', 'scored', 'covered', 'over'];
    $negativeTerms = ['lost', 'under', 'fewer'];

    // Check for positive trend
    $isPositive = false;
    foreach ($positiveTerms as $term) {
        if (str_contains(strtolower($trend), $term)) {
            $isPositive = true;
            break;
        }
    }

    // Extract numbers from trend (e.g., "7 of their last 10")
    preg_match('/(\d+) of their last (\d+)/', $trend, $matches);

    // Calculate percentage if matches found
    $percentage = $matches ? ($matches[1] / $matches[2]) * 100 : 0;
    $isOverFifty = $percentage >= 50;

    // Set colors based on trend analysis
    $trendColor = $isOverFifty ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    $textColor = $isOverFifty ? 'text-green-700' : 'text-red-700';
@endphp

<div class="rounded-lg border {{ $trendColor }} p-3">
    <p class="text-sm {{ $textColor }}">{{ $trend }}</p>
</div>
<div>

</div>