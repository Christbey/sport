<form method="GET" action="{{ $action }}" class="mb-8">
    <label for="week" class="mr-2 text-gray-600">Select Week:</label>
    <select name="week" id="week" class="border border-gray-300 rounded p-2" onchange="this.form.submit()">
        <option value="">All Weeks</option>
        @foreach($weeks as $wk)
            <option value="{{ $wk->week }}" {{ isset($selectedWeek) && $selectedWeek == $wk->week ? 'selected' : '' }}>
                Week {{ $wk->week }}
            </option>
        @endforeach
    </select>
</form>
