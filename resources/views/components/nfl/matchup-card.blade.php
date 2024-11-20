<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
    <div class="bg-gray-800 text-white p-6">
        <div class="flex justify-between items-center">
            <div class="text-center flex-1">
                <h2 class="text-2xl font-bold">{{ $awayTeam }}</h2>
                <p class="text-gray-400 text-sm mt-1">Away Team</p>
            </div>
            <div class="px-4">
                <span class="text-2xl font-bold text-gray-400">VS</span>
            </div>
            <div class="text-center flex-1">
                <h2 class="text-2xl font-bold">{{ $homeTeam }}</h2>
                <p class="text-gray-400 text-sm mt-1">Home Team</p>
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{ $slot }}
        </div>
    </div>
</div>
