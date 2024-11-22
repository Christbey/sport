<div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
    {{-- Game Header --}}
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-500">
                {{ $prediction->gameStatusDetail ?? 'Time TBD' }}
            </span>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full
                {{ $prediction->gameStatus === 'Live - In Progress' ? 'bg-yellow-100 text-yellow-800' : 
                   ($prediction->gameStatus === 'Completed' ? 'bg-gray-100 text-gray-800' : 
                   'bg-blue-100 text-blue-800') }}">
                {{ $prediction->gameStatus }}
            </span>
        </div>
    </div>

    {{-- Rest of your card content --}}
</div>