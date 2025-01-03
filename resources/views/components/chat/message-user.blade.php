@props(['message'])

<div class="bg-gray-100 p-3 rounded-lg self-end max-w-[75%] mb-2 shadow-sm">
    <div class="flex items-center gap-2 mb-1">
        <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
            </svg>
        </div>
        <span class="font-medium text-gray-700">You</span>
    </div>
    <p class="text-gray-800 pl-8">{{ $message }}</p>
</div>