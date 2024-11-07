<!-- resources/views/components/nfl-sheet-table.blade.php -->
<div class="mt-10">
    <table class="min-w-full table-auto bg-white shadow-md rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Inputted
                Notes
            </th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        @foreach ($nflSheets as $sheet)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $sheet->nflTeam->team_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $sheet->game_id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $sheet->user_inputted_notes }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
