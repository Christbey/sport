<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Game Details -->
    <div class="bg-red-600 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">prediction</h2>
    </div>
    <div class="mb-6">
        <p class="text-lg text-gray-700"><span class="font-semibold">Game ID:</span> {{ $teamSchedule->game_id }}</p>
        <p class="text-lg text-gray-700"><span class="font-semibold">Home Team:</span> {{ $teamSchedule->home_team }}
        </p>
        <p class="text-lg text-gray-700"><span class="font-semibold">Away Team:</span> {{ $teamSchedule->away_team }}
        </p>
    </div>

    <!-- Team Prediction -->
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Team Prediction</h2>
    @if($teamPrediction)
        <div class="mb-6">
            <p class="text-lg text-gray-700"><span class="font-semibold">Team:</span> {{ $teamPrediction['team'] }}</p>
            <p class="text-lg text-gray-700"><span
                        class="font-semibold">Opponent:</span> {{ $teamPrediction['opponent'] }}</p>
            <p class="text-lg text-gray-700"><span
                        class="font-semibold">Win Probability:</span> {{ $teamPrediction['win_probability'] }}%</p>
            <p class="text-lg text-gray-700 font-semibold">Predicted Scores:</p>
            <ul class="list-disc list-inside text-gray-700 ml-4">
                <li>
                    <span class="font-semibold">{{ $teamPrediction['team'] }}</span>: {{ $teamPrediction['predicted_score'][$teamPrediction['team']] }}
                </li>
                <li>
                    <span class="font-semibold">{{ $teamPrediction['opponent'] }}</span>: {{ $teamPrediction['predicted_score'][$teamPrediction['opponent']] }}
                </li>
            </ul>
            <p class="text-lg text-gray-700"><span
                        class="font-semibold">Confidence Level:</span> {{ $teamPrediction['confidence_level'] }}</p>
            <p class="text-lg text-gray-700"><span class="font-semibold">Summary:</span> {{ $summary }}</p>
        </div>
    @else
        <p class="text-lg text-red-600">No prediction data available for this game.</p>
    @endif
</div>
