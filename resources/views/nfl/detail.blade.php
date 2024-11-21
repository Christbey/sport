<x-app-layout>
    <div class="max-w-2xl mx-auto p-4">
        <!-- Team Selection Form -->
        <div class="bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
            <form action="{{ route('nfl.detail') }}" method="GET" class="mb-6">
                <label for="team_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    Select Team
                </label>
                <select
                        name="team_id"
                        id="team_id"
                        onchange="this.form.submit()"
                        class="w-full p-2.5 rounded-lg bg-gray-50 border text-gray-900 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">Choose a team...</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" @selected($selectedTeamId == $team->id)>
                            {{ $team->team_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- Notes Form -->
        <form action="{{ route('nfl.sheet.store') }}" method="POST">
            @csrf
            <input type="hidden" name="team_id" value="{{ $selectedTeamId }}">

            <div class="space-y-4">
                <!-- Game Select -->
                <div>
                    <label for="game_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Select Game
                    </label>
                    <select
                            name="game_id"
                            id="game_id"
                            @disabled(!$selectedTeamId)
                            class="w-full p-2.5 rounded-lg bg-gray-50 border text-gray-900 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">Choose a game...</option>
                        @foreach($games as $game)
                            <option value="{{ $game->game_id }}" @selected($selectedGameId == $game->game_id)>
                                Week {{ $game->week }} - {{ $game->short_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('game_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes Input -->
                <div>
                    <label for="user_inputted_notes"
                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Game Notes
                    </label>
                    <textarea
                            name="user_inputted_notes"
                            id="user_inputted_notes"
                            rows="4"
                            @disabled(!$selectedGameId)
                            placeholder="Enter your game notes here..."
                            class="w-full p-2.5 rounded-lg bg-gray-50 border text-gray-900 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >{{ old('user_inputted_notes') }}</textarea>
                    @error('user_inputted_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                            type="submit"
                            @disabled(!$selectedGameId)
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
                    >
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                        </svg>
                        Save Notes
                    </button>
                </div>
            </div>
        </form>

        <!-- Existing Notes Section -->
        @if($nflSheets->isNotEmpty())
            <div class="mt-8 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Recent Team Notes
                </h3>
                <div class="space-y-4">
                    @foreach($nflSheets as $sheet)
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Game: Week {{ $sheet->game->week }} - {{ $sheet->game->short_name }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $sheet->user_inputted_notes }}
                            </p>
                            <div class="mt-2 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Added {{ $sheet->created_at->diffForHumans() }}</span>
                                <span>By {{ $sheet->user->name }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Success Message Toast -->
        @if(session('success'))
            <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 3000)"
                    class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg"
            >
                {{ session('success') }}
            </div>
        @endif
    </div>
</x-app-layout>
