@props(['efficiencyMetrics', 'homeTeam', 'awayTeam'])

<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Team Efficiency Metrics</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Offensive Efficiency -->
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
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['offensive_efficiency']['away'] > $efficiencyMetrics['offensive_efficiency']['home'] ? 'text-blue-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['offensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($efficiencyMetrics['offensive_efficiency']['home'] - $efficiencyMetrics['offensive_efficiency']['away']) > 0 ? 'text-green-600' : 'text-blue-600' }}">
                        {{ number_format($efficiencyMetrics['offensive_efficiency']['home'] - $efficiencyMetrics['offensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Defensive Efficiency -->
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
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['defensive_efficiency']['away'] > $efficiencyMetrics['defensive_efficiency']['home'] ? 'text-blue-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['defensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($efficiencyMetrics['defensive_efficiency']['home'] - $efficiencyMetrics['defensive_efficiency']['away']) > 0 ? 'text-green-600' : 'text-blue-600' }}">
                        {{ number_format($efficiencyMetrics['defensive_efficiency']['home'] - $efficiencyMetrics['defensive_efficiency']['away'], 3) }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Overall Efficiency -->
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Overall Efficiency</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $homeTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['overall_efficiency']['home'] > $efficiencyMetrics['overall_efficiency']['away'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['overall_efficiency']['home'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">{{ $awayTeam->school }}</td>
                    <td class="py-2 text-sm font-medium {{ $efficiencyMetrics['overall_efficiency']['away'] > $efficiencyMetrics['overall_efficiency']['home'] ? 'text-blue-600' : 'text-gray-600' }}">
                        {{ number_format($efficiencyMetrics['overall_efficiency']['away'], 3) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Differential</td>
                    <td class="py-2 text-sm font-medium {{ ($efficiencyMetrics['overall_efficiency']['home'] - $efficiencyMetrics['overall_efficiency']['away']) > 0 ? 'text-green-600' : 'text-blue-600' }}">
                        {{ number_format($efficiencyMetrics['overall_efficiency']['home'] - $efficiencyMetrics['overall_efficiency']['away'], 3) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-600">
            <p class="font-medium mb-2">Metric Explanations:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Offensive Efficiency: Combines PPA, success rate, and explosiveness metrics</li>
                <li>Defensive Efficiency: Measures ability to prevent successful plays and limit big gains</li>
                <li>Overall Efficiency: Combined performance across all phases</li>
            </ul>
        </div>
    </div>
</div>