{{-- resources/views/components/matchup-advantages.blade.php --}}
<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Matchup Advantages</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Rushing Game --}}
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Rushing Game</h3>
            <table class="min-w-full ">
                <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Offensive Advantage</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['rushing_advantage']['offense'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['rushing_advantage']['offense'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">Defensive Advantage</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['rushing_advantage']['defense'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['rushing_advantage']['defense'], 3) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        {{-- Passing Game --}}
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Passing Game</h3>
            <table class="min-w-full">
                <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Offensive Advantage</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['passing_advantage']['offense'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['passing_advantage']['offense'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">Defensive Advantage</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['passing_advantage']['defense'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['passing_advantage']['defense'], 3) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        {{-- Situational Advantages --}}
        <div class="col-span-2">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Situational Advantages</h3>
            <table class="min-w-full">
                <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Standard Downs</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['situational_advantages']['standard_downs'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['situational_advantages']['standard_downs'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">Passing Downs</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['situational_advantages']['passing_downs'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['situational_advantages']['passing_downs'], 3) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        {{-- Line Play --}}
        <div class="col-span-2">
            <h3 class="text-lg font-medium text-gray-700 mb-2">Line Play Metrics</h3>
            <table class="min-w-full ">
                <tbody class="divide-y divide-gray-200">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Offensive Line Rating</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['line_play']['offensive_line'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['line_play']['offensive_line'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">Defensive Line Rating</td>
                    <td class="py-2 text-sm text-gray-600 {{ $matchupAdvantages['line_play']['defensive_line'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($matchupAdvantages['line_play']['defensive_line'], 3) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-600">
            <p class="mb-2"><span class="font-medium">Rushing/Passing Advantage:</span> Combined measure of success
                rate, explosiveness, and efficiency</p>
            <p class="mb-2"><span class="font-medium">Situational Advantages:</span> Team's performance on standard vs
                passing downs</p>
            <p class="mb-2"><span class="font-medium">Line Play:</span> Combined metric of line yards, power success,
                and stuff rate</p>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium text-gray-700">Advantages:</p>
                <p class="mb-1">Rushing Game: <span
                            class="font-medium {{ $matchupAdvantages['rushing_advantage']['offense'] > 0 ? 'text-green-600' : 'text-blue-600' }}">
                {{ $matchupAdvantages['rushing_advantage']['offense'] > 0 ? $homeTeam->school : $awayTeam->school }}
            </span></p>
                <p class="mb-1">Passing Game: <span
                            class="font-medium {{ $matchupAdvantages['passing_advantage']['offense'] > 0 ? 'text-green-600' : 'text-blue-600' }}">
                {{ $matchupAdvantages['passing_advantage']['offense'] > 0 ? $homeTeam->school : $awayTeam->school }}
            </span></p>
                <p>Line Play: <span
                            class="font-medium {{ $matchupAdvantages['line_play']['offensive_line'] > $matchupAdvantages['line_play']['defensive_line'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $matchupAdvantages['line_play']['offensive_line'] > $matchupAdvantages['line_play']['defensive_line'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
            </div>
        </div>
    </div>

</div>
