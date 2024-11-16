<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Team Efficiency Metrics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Offensive Efficiency</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['offensive_efficiency']['home'] > $efficiencyMetrics['offensive_efficiency']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['offensive_efficiency']['home'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['offensive_efficiency']['away'] > $efficiencyMetrics['offensive_efficiency']['home'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['offensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['offensive_efficiency']['differential'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($efficiencyMetrics['offensive_efficiency']['differential'], 3) }}
                    </td>
                </tr>
            </table>
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Defensive Efficiency</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['defensive_efficiency']['home'] > $efficiencyMetrics['defensive_efficiency']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['defensive_efficiency']['home'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['defensive_efficiency']['away'] > $efficiencyMetrics['defensive_efficiency']['home'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['defensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['defensive_efficiency']['differential'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($efficiencyMetrics['defensive_efficiency']['differential'], 3) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-600">
            <p class="mb-2"><span class="font-medium">Offensive Efficiency:</span> Combined measure of success rate,
                explosiveness, and points per play</p>
            <p class="mb-2"><span class="font-medium">Defensive Efficiency:</span> Team's ability to prevent successful
                plays and limit scoring</p>
            <p class="mb-2"><span class="font-medium">Differential:</span> Positive values indicate advantage, negative
                values indicate disadvantage</p>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium text-gray-700">Advantages:</p>
                <p class="mb-1">Offense: <span
                            class="font-medium {{ $efficiencyMetrics['offensive_efficiency']['home'] > $efficiencyMetrics['offensive_efficiency']['away'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $efficiencyMetrics['offensive_efficiency']['home'] > $efficiencyMetrics['offensive_efficiency']['away'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
                <p>Defense: <span
                            class="font-medium {{ $efficiencyMetrics['defensive_efficiency']['home'] > $efficiencyMetrics['defensive_efficiency']['away'] ? 'text-green-600' : 'text-blue-600' }}">
                {{ $efficiencyMetrics['defensive_efficiency']['home'] > $efficiencyMetrics['defensive_efficiency']['away'] ? $homeTeam->school : $awayTeam->school }}
            </span></p>
            </div>
        </div>
    </div>
</div>
