<div class="overflow-x-auto relative shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th class="py-3 px-6">Home Team</th>
            <th class="py-3 px-6">1st Half Win %</th>
            <th class="py-3 px-6">2nd Half Win %</th>
            <th class="py-3 px-6">Details</th>
        </tr>
        </thead>
        <tbody>
        @foreach($teamStats as $stat)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <td class="py-4 px-6">
                    {{ $stat->away_team_name }}<br>
                    <hr>
                    {{ $stat->home_team_name }}
                </td>
                <td class="py-4 px-6">
                    <span class="font-bold {{ $stat->away_first_half_win_percentage > $stat->home_first_half_win_percentage ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->away_first_half_win_percentage * 100, 2) }}%
                    </span><br>
                    <span class="font-bold {{ $stat->home_first_half_win_percentage > $stat->away_first_half_win_percentage ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->home_first_half_win_percentage * 100, 2) }}%
                    </span>
                </td>
                <td class="py-4 px-6">
                    <span class="font-bold {{ $stat->away_second_half_win_percentage > $stat->home_second_half_win_percentage ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->away_second_half_win_percentage * 100, 2) }}%
                    </span><br>
                    <span class="font-bold {{ $stat->home_second_half_win_percentage > $stat->away_second_half_win_percentage ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->home_second_half_win_percentage * 100, 2) }}%
                    </span>
                </td>
                <td class="py-4 px-6">
                    <button class="text-blue-600 dark:text-blue-500 hover:underline"
                            data-toggle-row="{{ $stat->event_id }}">View
                    </button>
                </td>
            </tr>
            <tr id="row-{{ $stat->event_id }}" class="hidden bg-gray-50 dark:bg-gray-800">
                <td colspan="8" class="py-6 px-8">
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Period Stats</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach(range(1, 4) as $period)
                                <div class="p-4 bg-white dark:bg-gray-700 shadow rounded-lg border border-gray-200 dark:border-gray-600">
                                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                        Period {{ $period }}
                                    </h3>
                                    <p class="mt-2 text-sm">
                            <span class="block font-medium {{ $stat->{"home_period_{$period}_win_percentage"} > $stat->{"away_period_{$period}_win_percentage"} ? 'text-green-600' : 'text-red-600' }}">
                                {{ $stat->home_team_name }} Win %: {{ number_format($stat->{"home_period_{$period}_win_percentage"} * 100, 2) }}%
                            </span>
                                        <span class="block font-medium {{ $stat->{"away_period_{$period}_win_percentage"} > $stat->{"home_period_{$period}_win_percentage"} ? 'text-green-600' : 'text-red-600' }}">
                                {{ $stat->away_team_name }} Win %: {{ number_format($stat->{"away_period_{$period}_win_percentage"} * 100, 2) }}%
                            </span>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- JavaScript for toggle rows --}}
<script>
    document.querySelectorAll('[data-toggle-row]').forEach(button => {
        button.addEventListener('click', () => {
            const rowId = button.getAttribute('data-toggle-row');
            const row = document.getElementById(`row-${rowId}`);
            row.classList.toggle('hidden');
        });
    });
</script>
