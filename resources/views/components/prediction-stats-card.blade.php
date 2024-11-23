@props(['weeklyStats'])

<div class="bg-white shadow-lg rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Week {{ request()->input('week', 1) }} Prediction Stats</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Predictions -->
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Total Predictions</div>
            <div class="text-2xl font-bold text-gray-800">
                {{ number_format($weeklyStats['total']) }}
            </div>
        </div>

        <!-- Correct Predictions -->
        <div class="bg-green-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Correct</div>
            <div class="text-2xl font-bold text-green-600">
                {{ number_format($weeklyStats['correct']) }}
            </div>
        </div>

        <!-- Incorrect Predictions -->
        <div class="bg-red-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Incorrect</div>
            <div class="text-2xl font-bold text-red-600">
                {{ number_format($weeklyStats['incorrect']) }}
            </div>
        </div>

        <!-- Accuracy Rate -->
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Accuracy Rate</div>
            <div class="text-2xl font-bold text-blue-600">
                {{ $weeklyStats['accuracy_rate'] }}%
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full"
                 style="width: {{ $weeklyStats['accuracy_rate'] }}%">
            </div>
        </div>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Based on completed games and hypothetical predictions for Week {{ request()->input('week', 1) }}
    </div>
</div>