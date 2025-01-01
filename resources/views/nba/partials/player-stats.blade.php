<div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900">Player</th>
            {{--            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900">Team</th>--}}
            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Prop Total</th>
            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Over %</th>
            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Under %</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
        @foreach($playerOverStats as $stat)
            <tr>
                <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900">{{ $stat->athlete_name }}
                    <br><span class="text-xs italic text-gray-500">{{ $stat->team_name }}</span></td>
                {{--                <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900">{{ $stat->team_name }}</td>--}}
                <td class="px-3 py-4 text-sm text-gray-500">{{ number_format($stat->prop_total, 1) }}</td>
                <td class="px-3 py-4 text-sm">
                    <span class="font-bold {{ $stat->average_over_percentage > 50 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->average_over_percentage, 2) }}%
                    </span>
                </td>
                <td class="px-3 py-4 text-sm">
                    <span class="font-bold {{ $stat->average_under_percentage > 50 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($stat->average_under_percentage, 2) }}%
                    </span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
