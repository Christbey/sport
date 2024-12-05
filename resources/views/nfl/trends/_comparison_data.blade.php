<div class="grid md:grid-cols-2 gap-8">
    <!-- Team 1 -->
    <div>
        <h2 class="text-2xl font-bold mb-6">{{ $team1 }} Analysis</h2>

        <!-- Scoring Stats -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Scoring Analysis</h3>
            @if(isset($comparison['team1']['stats']['data']))
                <div class="space-y-3">
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                        <span class="font-medium">Avg Total Points:</span>
                        <span>{{ $comparison['team1']['stats']['data']->avg_total_points ?? 'N/A' }}</span>
                    </div>
                    <!-- Add more stats as needed -->
                </div>
            @endif
        </div>

        <!-- Add other sections similarly -->
    </div>

    <!-- Team 2 -->
    <div>
        <h2 class="text-2xl font-bold mb-6">{{ $team2 }} Analysis</h2>
        <!-- Mirror the Team 1 structure for Team 2 -->
    </div>
</div>