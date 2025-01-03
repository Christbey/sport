<form action="{{ route('nfl.trends.config') }}" method="GET">
    <div class="grid sm:grid-cols-4 gap-4">
        {{-- Team Selection --}}
        <div class="sm:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
            <select name="team"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select a team...</option>
                @foreach(['ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE', 'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC', 'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG', 'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WAS'] as $team)
                    <option value="{{ $team }}" {{ $team === ($selectedTeam ?? 'KC') ? 'selected' : '' }}>
                        {{ $team }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Week Selection --}}
        <div class="sm:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Week</label>
            <input type="number" name="games" value="{{ $games ?? 17 }}" min="1" max="18"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Submit Button --}}
        <div class="sm:col-span-1 flex items-end">
            <button type="submit"
                    class="w-full h-10 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                Analyze
            </button>
        </div>
    </div>
</form>