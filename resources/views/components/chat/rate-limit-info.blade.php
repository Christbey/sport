@props(['remainingRequests', 'userLimit', 'daysUntilReset', 'hoursUntilReset'])

<div class="p-4 border-b bg-gray-50">
    <div class="flex justify-between items-center">
        <div class="text-gray-700">
            <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold">Weekly Requests:</span>
                <div class="flex items-center">
                    <span class="text-blue-500 font-bold text-lg">{{ $remainingRequests }}</span>
                    <span class="text-gray-400 mx-1">/</span>
                    <span class="text-gray-500">{{ $userLimit }}</span>
                </div>
            </div>
            <x-chat.reset-timer
                    :remainingRequests="$remainingRequests"
                    :userLimit="$userLimit"
                    :daysUntilReset="$daysUntilReset"
                    :hoursUntilReset="$hoursUntilReset"
            />
        </div>
        @if($remainingRequests === 0)
            <x-chat.upgrade-button/>
        @endif
    </div>
</div>