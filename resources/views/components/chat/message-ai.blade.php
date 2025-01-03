@props(['message'])

<div class="bg-blue-50 p-3 rounded-lg self-start max-w-[75%] shadow-sm">
    <div class="flex items-center gap-2 mb-1">
        <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
            </svg>
        </div>
        <span class="font-medium text-blue-700">AI Assistant</span>
    </div>
    <div class="prose prose-sm max-w-none pl-8 text-gray-700">
        {!! $message !!}
    </div>
</div>