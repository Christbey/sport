@props(['date'])

<div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-blue-700 font-medium">
                Showing games for {{ Carbon::parse($date)->format('l, F j, Y') }}
            </span>
        </div>
        <a href="{{ route('cbb.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Clear Filter</a>
    </div>
</div>