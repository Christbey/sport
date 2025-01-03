@props(['remainingRequests', 'userLimit', 'daysUntilReset', 'hoursUntilReset'])

@if($remainingRequests < $userLimit)
    <p class="text-sm text-gray-600">
        Resets in:
        @if($daysUntilReset > 0)
            <span class="font-semibold text-blue-600">{{ $daysUntilReset }}</span>
            {{ Str::plural('day', $daysUntilReset) }}
        @endif
        @if($hoursUntilReset > 0)
            @if($daysUntilReset > 0)
                and
            @endif
            <span class="font-semibold text-blue-600">{{ $hoursUntilReset }}</span>
            {{ Str::plural('hour', $hoursUntilReset) }}
        @endif
    </p>
@endif