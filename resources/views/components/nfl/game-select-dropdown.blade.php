<!-- resources/views/components/game-select-dropdown.blade.php -->
@if (!empty($games))
    <div class="w-1/3">
        <select name="game_id" id="game_id"
                class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                required>
            <option value="">-- Select Game --</option>
            @foreach ($games as $game)
                <option value="{{ $game->id }}" {{ $selectedGameId == $game->id ? 'selected' : '' }}>
                    {{ $game->name }}
                </option>
            @endforeach
        </select>
    </div>
@endif
<script>
    document.getElementById('game_id').addEventListener('change', function () {
        document.getElementById('hidden_game_id').value = this.value;
    });
</script>
