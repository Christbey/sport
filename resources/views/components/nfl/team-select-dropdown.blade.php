<!-- resources/views/components/team-select-dropdown.blade.php -->
<div class="w-1/3">
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
