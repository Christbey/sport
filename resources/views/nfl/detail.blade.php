<x-app-layout>
    <div class="max-w-3xl mx-auto py-12">
        <h1 class="text-3xl font-semibold mb-8 text-gray-800 text-center">NFL Sheet Management</h1>

        <!-- Filter Form with Add Note Button -->
        <form action="{{ route('nfl.detail') }}" method="GET" class="mb-8 space-y-4">
            <div class="flex items-center space-x-4">
                <!-- Team and Game Select Dropdowns -->
                <x-nfl.team-select-dropdown :teams="$teams" :selectedTeamId="$selectedTeamId"/>
                <x-nfl.game-select-dropdown :games="$games" :selectedGameId="$selectedGameId"/>

                <!-- Add Notes Button -->
                <button id="defaultModalButton" data-modal-target="defaultModal" data-modal-toggle="defaultModal"
                        class="text-white bg-blue-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                        type="button">
                    Add Notes
                </button>
            </div>
        </form>

        <!-- Main modal -->
        <div id="defaultModal" tabindex="-1" aria-hidden="true"
             class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
            <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
                <div class="relative p-6 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-8">
                    <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Add Notes for Selected Game
                        </h3>
                        <button type="button"
                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                data-modal-toggle="defaultModal">
                            <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414 1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <form action="{{ route('nfl.sheet.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="user_inputted_notes"
                                   class="block text-sm font-medium text-gray-900">User Inputted
                                Notes</label>
                            <textarea name="user_inputted_notes" id="user_inputted_notes" rows="4"
                                      class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                      placeholder="Add your notes here"></textarea>
                        </div>

                        <input type="hidden" name="team_id" value="{{ $selectedTeamId }}">
                        <input type="hidden" name="game_id" id="hidden_game_id" value="{{ $selectedGameId }}">

                        <button type="submit"
                                class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Save Notes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Conditional Table Display -->
        @if($selectedTeamId)
            <div class="mt-10">
                <x-nfl.nfl-sheet-table :nflSheets="$nflSheets"/>
            </div>
        @endif

        <!-- Script to set hidden game_id based on selection in modal -->
        <script>
            document.getElementById('game_id').addEventListener('change', function () {
                document.getElementById('hidden_game_id').value = this.value;
            });
        </script>
    </div>
</x-app-layout>
