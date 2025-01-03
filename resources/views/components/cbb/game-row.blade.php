@props(['game'])

<tr class="hover:bg-gray-50 transition-colors duration-150">
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex flex-col">
            <div class="text-sm font-medium text-gray-900">
                {{ $game->home_team }}
                <span class="text-xs text-green-600 ml-1">(Home)</span>
            </div>
            <div class="text-sm text-gray-600 mt-1">
                vs {{ $game->away_team }}
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-center">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium
            {{ $game->hypothetical_spread > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            {{ $game->hypothetical_spread > 0 ? '+' : '' }}{{ $game->hypothetical_spread }}
        </span>
    </td>
</tr>