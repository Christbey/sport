{{-- resources/views/components/cfb/elo-table.blade.php --}}
<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th scope="col" class="px-6 py-3">Team</th>
            <th scope="col" class="px-6 py-3">ELO Rating</th>
            @if(request('week') > 1)
                <th scope="col" class="px-6 py-3">Weekly Change</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($teams as $team)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ $team->team }}
                </td>
                <td class="px-6 py-4">{{ number_format($team->elo / 100,2) }}</td>
                @if(request('week') > 1)
                    <td class="px-6 py-4">
                        @if($team->elo_change !== null)
                            <span class="{{ $team->elo_change > 0 ? 'text-green-600 dark:text-green-400' : ($team->elo_change < 0 ? 'text-red-600 dark:text-red-400' : '') }}">
                                    {{ $team->elo_change > 0 ? '+' : '' }}{{ number_format($team->elo_change / 100, 2) }}
                                </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">-</span>
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>