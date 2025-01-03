@props([
    'title',
    'wins',
    'losses',
    'percentage'
])

@php
    $getStatusColor = function ($wins, $total) {
        if ($total === 0) {
            return 'text-gray-600'; // Default color if total is 0
        }

        $percentage = ($wins / $total) * 100;

        return $percentage >= 55
            ? 'text-green-600'
            : ($percentage <= 45 ? 'text-red-600' : 'text-gray-600');
    };

    $total = $wins + $losses;
    $statusColor = $getStatusColor($wins, $total);
@endphp

<div class="bg-white rounded-lg p-4 shadow-sm">
    <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
    <p class="mt-2 text-2xl font-bold {{ $statusColor }}">
        {{ $wins }}-{{ $losses }}
        <span class="text-sm">({{ $percentage }}%)</span>
    </p>
</div>