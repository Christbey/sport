<x-app-layout>

    <x-nfl.game-layout>

        @if($predictions->isNotEmpty())
            <x-nfl.matchup-card :awayTeam="$predictions->first()->opponent" :homeTeam="$predictions->first()->team">
                <x-nfl.stat-card
                        bgColor="bg-red-50"
                        textColor="text-red-800"
                        title="Game Prediction | Confidence: {{ $teamPrediction['confidence_level'] }}"
                        :value="$summary"/>

                <x-nfl.stat-card
                        bgColor="bg-purple-50"
                        textColor="text-purple-800"
                        title="Game Date"
                        :value="Carbon\Carbon::parse($teamSchedule->game_date)->format('D, M j, Y')"
                        :subtitle="Carbon\Carbon::parse($teamSchedule->game_date)->format('g:i A T')"
                />
                <x-nfl.stat-card
                        bgColor="bg-yellow-50"
                        textColor="text-yellow-800"
                        title="Over/Under"
                        :value="$totalPoints ?? 'N/A'"
                        :subtitle="$overUnderResult . ' (Total: ' . ($totalOver ?? 'N/A') . ')'"
                        :subtitle="($bettingOdds->spread_home ?? 'N/A')"
                />
            </x-nfl.matchup-card>

            <!-- Tables for Recent Games -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Away Team Recent Games -->
                <x-nfl.game-table headerColor="bg-blue-700"
                                  title="{{ $predictions->first()->opponent }} Recent Games">
                    <x-slot name="thead">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Opponent
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            O/U
                            Result
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Margin of Victory
                        </th>
                    </x-slot>
                    @foreach($awayTeamLastGames as $game)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $game->home_team_id === $awayTeamId ? $game->away_team : $game->home_team }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ Carbon\Carbon::parse($game->game_date)->format('M j') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $game->overUnderResult ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ is_numeric($game->marginOfVictory) ? ($game->marginOfVictory > 0 ? '+' : '') . $game->marginOfVictory : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </x-nfl.game-table>

                <!-- Home Team Recent Games -->
                <x-nfl.game-table headerColor="bg-green-700" title="{{ $predictions->first()->team }} Recent Games">
                    <x-slot name="thead">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Opponent
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            O/U
                            Result
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Margin of Victory
                        </th>
                    </x-slot>
                    @foreach($homeTeamLastGames as $game)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $game->home_team_id === $homeTeamId ? $game->away_team : $game->home_team }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ Carbon\Carbon::parse($game->game_date)->format('M j') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $game->overUnderResult ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ is_numeric($game->marginOfVictory) ? ($game->marginOfVictory > 0 ? '+' : '') . $game->marginOfVictory : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </x-nfl.game-table>
            </div>

            {{-- Injuries --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Away Team Injuries --}}
                <x-nfl.nfl-injury-card :team="$predictions->first()->opponent" :injuries="$awayTeamInjuries"/>
                {{-- Home Team Injuries --}}
                <x-nfl.nfl-injury-card :team="$predictions->first()->team" :injuries="$homeTeamInjuries"/>
            </div>
        @else
            <x-nfl.empty-state/>
        @endif

    </x-nfl.game-layout>
</x-app-layout>
