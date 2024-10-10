<x-app-layout>
    <div class="max-w-3xl mx-auto py-12">
        <h1 class="text-3xl font-semibold mb-8 text-gray-800">NFL Sheet Management</h1>

        <!-- Filter Form -->
        <form action="{{ route('nfl.detail') }}" method="GET" class="mb-8 space-y-4">
            <div class="flex items-center space-x-4">
                <div class="w-1/2">
                    <label for="team_id" class="block text-sm font-medium text-gray-700">Select Team</label>
                    <select name="team_id" id="team_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                            onchange="this.form.submit()">
                        <option value="">-- Select Team --</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" {{ $selectedTeamId == $team->id ? 'selected' : '' }}>
                                {{ $team->team_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (!empty($games))
                    <div class="w-1/2">
                        <label for="game_id" class="block text-sm font-medium text-gray-700">Select Game</label>
                        <select name="game_id" id="game_id"
                                class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                required>
                            <option value="">-- Select Game --</option>
                            @foreach ($games as $game)
                                <option value="{{ $game->id }}" {{ $selectedGameId == $game->id ? 'selected' : '' }}>
                                    Game ID: {{ $game->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </form>

        <!-- Form to add values to nfl_sheet table -->
        <form action="{{ route('nfl.sheet.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
            @csrf
            <!-- User inputted notes -->
            <div>
                <label for="user_inputted_notes" class="block text-sm font-medium text-gray-700">User Inputted
                    Notes</label>
                <textarea name="user_inputted_notes" id="user_inputted_notes" rows="4"
                          class="mt-1 block w-full shadow-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>

            <input type="hidden" name="team_id" value="{{ $selectedTeamId }}">
            <input type="hidden" name="game_id" value="{{ $selectedGameId }}">

            <button type="submit"
                    class="inline-flex justify-center mt-3 py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save
            </button>
        </form>

        <!-- Table displaying nfl_sheet records -->
        <div class="mt-10">
            <table class="min-w-full table-auto bg-white shadow-md rounded-lg overflow-hidden">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User
                        Inputted Notes
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
    </div>
</x-app-layout>
