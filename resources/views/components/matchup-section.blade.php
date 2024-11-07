@php use Carbon\Carbon; @endphp
        <!-- resources/views/components/matchup-section.blade.php -->
@props(['title', 'games'])

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ $title }}</h2>
    <ul class="space-y-4">
        @forelse($games as $game)
            <li class="text-gray-600 flex justify-between items-center">
                <div>
                    <span class="font-semibold">{{ $game->homeTeam->school ?? 'Unknown Team' }}</span>
                    vs
                    <span class="font-semibold">{{ $game->awayTeam->school ?? 'Unknown Team' }}</span>
                </div>
                <span class="text-blue-600">{{ Carbon::parse($game->start_date)->format('M d, Y') }}</span>
            </li>
        @empty
            <li class="text-gray-500">No matchups found.</li>
        @endforelse
    </ul>
</div>
