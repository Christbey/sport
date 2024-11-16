@php use Carbon\Carbon; @endphp

<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Historical Matchups</h2>

    @if ($previousResults->isNotEmpty())
        <div class="overflow-hidden">
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Winner</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Score</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($previousResults as $result)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ Carbon::parse($result->start_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($result->home_points > $result->away_points)
                                <span class="font-medium {{ $result->homeTeam->id === $homeTeam->id ? 'text-green-600' : 'text-blue-600' }}">
                                        {{ $result->homeTeam->school }}
                                    </span>
                            @elseif ($result->home_points < $result->away_points)
                                <span class="font-medium {{ $result->awayTeam->id === $homeTeam->id ? 'text-green-600' : 'text-blue-600' }}">
                                        {{ $result->awayTeam->school }}
                                    </span>
                            @else
                                <span class="text-gray-600">Tie</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                                <span class="font-medium {{ $result->home_points > $result->away_points ? 'text-green-600' : '' }}">
                                    {{ $result->home_points }}
                                </span>
                            -
                            <span class="font-medium {{ $result->away_points > $result->home_points ? 'text-blue-600' : '' }}">
                                    {{ $result->away_points }}
                                </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-600">
                <p class="mb-2">
                    <span class="font-medium">Series Record:</span>
                    @php
                        $homeWins = $previousResults->filter(fn($result) => 
                            ($result->home_team_id === $homeTeam->id && $result->home_points > $result->away_points) ||
                            ($result->away_team_id === $homeTeam->id && $result->away_points > $result->home_points)
                        )->count();
                        
                        $awayWins = $previousResults->filter(fn($result) => 
                            ($result->home_team_id === $awayTeam->id && $result->home_points > $result->away_points) ||
                            ($result->away_team_id === $awayTeam->id && $result->away_points > $result->home_points)
                        )->count();
                        
                        $ties = $previousResults->filter(fn($result) => $result->home_points === $result->away_points)->count();
                    @endphp
                    <span class="font-medium {{ $homeWins > $awayWins ? 'text-green-600' : 'text-gray-600' }}">{{ $homeTeam->school }}</span>
                    leads
                    <span class="font-medium {{ $awayWins > $homeWins ? 'text-blue-600' : 'text-gray-600' }}">{{ $awayTeam->school }}</span>
                    {{ $homeWins }} - {{ $awayWins }}
                    @if($ties > 0)
                        with {{ $ties }} {{ Str::plural('tie', $ties) }}
                    @endif
                </p>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="font-medium text-gray-700">Last Meeting:</p>
                    @php $lastGame = $previousResults->first(); @endphp
                    <p class="mt-1">
                        {{ Carbon::parse($lastGame->start_date)->format('F j, Y') }} -
                        <span class="font-medium {{ $lastGame->home_points > $lastGame->away_points ? 'text-green-600' : 'text-blue-600' }}">
                            {{ $lastGame->home_points > $lastGame->away_points ? $lastGame->homeTeam->school : $lastGame->awayTeam->school }}
                        </span>
                        won {{ $lastGame->home_points }} - {{ $lastGame->away_points }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">No previous matchups found between these teams.</p>
            <p class="text-sm text-gray-400 mt-1">This will be their first meeting.</p>
        </div>
    @endif
</div>
