<form action="{{ route('cfb.index') }}" method="GET" class="flex items-center space-x-2 mt-3">
    <div>
        <label for="week" class="sr-only">Week</label>
        <select id="week" name="week"
                class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
            @foreach($weeks as $wk)
                <option value="{{ $wk->week }}" {{ isset($selectedWeek) && $selectedWeek == $wk->week ? 'selected' : '' }}>
                    Week {{ $wk->week }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
        Go
    </button>
</form>
