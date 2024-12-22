<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header Section --}}
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">NFL Game Predictions</h1>
                <p class="mt-2 text-sm text-gray-600">Powered by Elo Ratings System</p>
            </div>

            {{-- Week Selection --}}
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form method="GET" action="{{ route('nfl.elo.table') }}" class="max-w-xs">
                    <label for="week" class="block text-sm font-medium text-gray-700">Game Week</label>
                    <div class="mt-2 flex items-center space-x-4">
                        <select name="week" id="week"
                                class="block w-full rounded-md border-gray-300 pr-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">All Weeks</option>
                            @foreach($weeks as $wk)
                                <option value="{{ $wk }}" @selected(isset($week) && $week == $wk)>{{ $wk }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Submit
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table View --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                @if($eloPredictions->isEmpty())
                    <div class="text-center py-12">
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No predictions available</h3>
                        <p class="mt-1 text-sm text-gray-500">No games found for the selected week</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Game Time
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Teams
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Score
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Predicted Spread
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Market Odds
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Result
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($eloPredictions as $prediction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

                                        {{ $prediction->gameTime ?? 'Time TBD' }}

                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span @class([
                                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                'bg-yellow-100 text-yellow-800' => $prediction->gameStatusDetail === 'Live - In Progress',
                                                'bg-gray-100 text-gray-800' => $prediction->gameStatusDetail === 'Completed',
                                                'bg-blue-100 text-blue-800' => !in_array($prediction->gameStatusDetail, ['Live - In Progress', 'Completed'])
                                            ])>
{{--                                                @dd($prediction->gameStatusDetail)--}}
                                                {{ $prediction->gameStatusDetail }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $prediction->opponent }} vs {{ $prediction->team }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @isset($prediction->awayPts, $prediction->homePts)
                                            {{ $prediction->awayPts }} - {{ $prediction->homePts }}
                                        @else
                                            -
                                        @endisset
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($prediction->predicted_spread > 0)
                                            {{ $prediction->team }}
                                            by {{ number_format($prediction->predicted_spread, 1) }}
                                        @elseif($prediction->predicted_spread < 0)
                                            {{ $prediction->opponent }}
                                            by {{ abs(number_format($prediction->predicted_spread, 1)) }}
                                        @else
                                            Even
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @isset($nflBettingOdds[$prediction->game_id])
                                            <div>
                                                <div>
                                                    Spread: {{ $nflBettingOdds[$prediction->game_id]->spread_home > 0 ? '+' : '' }}{{ number_format($nflBettingOdds[$prediction->game_id]->spread_home, 1) }}</div>
                                                <div>
                                                    O/U: {{ number_format($nflBettingOdds[$prediction->game_id]->total_over, 1) }}</div>
                                            </div>
                                        @else
                                            -
                                        @endisset
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($prediction->homeResult)
                                            <span>{{ $prediction->homeTeam }} {{ $prediction->homeResult }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <a href="{{ route('nfl.elo.show', ['gameId' => $prediction->game_id]) }}"
                                           class="text-blue-600 hover:text-blue-900">View Analysis</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>