<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Predicted Scoring Ranges</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">{{ $homeTeam->school }}</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Low Range</td>
                    <td class="py-2 text-sm font-medium">
                        {{ $scoringPrediction['home_predicted_range']['low'] }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">High Range</td>
                    <td class="py-2 text-sm font-medium">
                        {{ $scoringPrediction['home_predicted_range']['high'] }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Average</td>
                    <td class="py-2 text-sm font-medium">
                        {{ round(($scoringPrediction['home_predicted_range']['high'] + $scoringPrediction['home_predicted_range']['low']) / 2, 1) }}
                    </td>
                </tr>
            </table>
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">{{ $awayTeam->school }}</h3>
            <table class="min-w-full">
                <tr>
                    <td class="py-2 text-sm text-gray-600">Low Range</td>
                    <td class="py-2 text-sm font-medium">
                        {{ $scoringPrediction['away_predicted_range']['low'] }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm text-gray-600">High Range</td>
                    <td class="py-2 text-sm font-medium">
                        {{ $scoringPrediction['away_predicted_range']['high'] }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-sm font-medium">Average</td>
                    <td class="py-2 text-sm font-medium">
                        {{ round(($scoringPrediction['away_predicted_range']['high'] + $scoringPrediction['away_predicted_range']['low']) / 2, 1) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-sm text-gray-600">
            <p class="mb-2"><span class="font-medium">Low Range:</span> Conservative estimate based on team's average
                efficiency</p>
            <p class="mb-2"><span class="font-medium">High Range:</span> Optimistic projection based on peak performance
                metrics</p>
            <p class="mb-2"><span class="font-medium">Prediction Model:</span> Based on PPA, success rate, and defensive
                adjustments</p>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="font-medium text-gray-700">Projected Winner:</p>
                <p class="mb-1"><span class="font-medium {{ ($scoringPrediction['home_predicted_range']['high'] + $scoringPrediction['home_predicted_range']['low'])/2 >
                                                      ($scoringPrediction['away_predicted_range']['high'] + $scoringPrediction['away_predicted_range']['low'])/2
                                                      ? 'text-green-600' : 'text-blue-600' }}">
                {{ ($scoringPrediction['home_predicted_range']['high'] + $scoringPrediction['home_predicted_range']['low'])/2 >
                   ($scoringPrediction['away_predicted_range']['high'] + $scoringPrediction['away_predicted_range']['low'])/2
                   ? $homeTeam->school : $awayTeam->school }}
            </span> by {{ abs(round((($scoringPrediction['home_predicted_range']['high'] + $scoringPrediction['home_predicted_range']['low'])/2) -
                                  (($scoringPrediction['away_predicted_range']['high'] + $scoringPrediction['away_predicted_range']['low'])/2), 1)) }}
                    points</p>
            </div>
        </div>
    </div>
</div>
