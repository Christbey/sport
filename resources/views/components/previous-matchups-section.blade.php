@php use Carbon\Carbon; @endphp
        <!-- resources/views/components/previous-matchups-section.blade.php -->
@props(['awayTeam', 'homeTeam', 'previousResults'])

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">
        Previous Matchups Between {{ $awayTeam->school }} and {{ $homeTeam->school }}
    </h2>

    @if($previousResults->isEmpty())
        <p class="text-gray-500">No previous matchups found between these two teams.</p>
    @else
        <ul class="space-y-4">
            @foreach($previousResults as $result)
                <li class="text-gray-600 flex justify-between items-center">
                    <span class="text-blue-600">{{ Carbon::parse($result['date'])->format('M d, Y') }}</span>
                    <span class="font-semibold">Winner: {{ $result['winner'] }}</span>
                    <span class="font-semibold">Score: {{ $result['score'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
