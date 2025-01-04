<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">NBA Team Stats</h1>

        <form method="get" action="{{ route('nba.team-stats.index') }}" class="mb-6">
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Column Selection --}}
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Select Columns</label>
                        <select name="columns[]" multiple class="form-multiselect block w-full mt-1" size="10">
                            @foreach($allColumns as $column)
                                <option value="{{ $column }}"
                                        {{ in_array($column, $selectedColumns) ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $column)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filters --}}
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filters</label>
                        <div class="space-y-2">
                            <input type="text" name="team_ref" placeholder="Team"
                                   value="{{ $filters['team_ref'] ?? '' }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">

                            <input type="number" name="points" placeholder="Min Points"
                                   value="{{ $filters['points'] ?? '' }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">

                            <input type="number" name="nba_rating" placeholder="Min NBA Rating"
                                   value="{{ $filters['nba_rating'] ?? '' }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                    </div>

                    {{-- Additional Options --}}
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Display Options</label>
                        <select name="per_page"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 per page</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 per page</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 per page</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>

        {{-- Results Table --}}
        <div class="overflow-x-auto">
            <table class="w-full bg-white shadow-md rounded">
                <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    @foreach($selectedColumns as $column)
                        <th class="py-3 px-6 text-left">
                            <a href="{{ route('nba.team-stats.index', array_merge(request()->except('sort', 'direction'), [
                                    'sort' => $column,
                                    'direction' => $sortColumn === $column && $sortDirection === 'asc' ? 'desc' : 'asc'
                                ])) }}">
                                {{ ucwords(str_replace('_', ' ', $column)) }}
                                @if($sortColumn === $column)
                                    {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                                @endif
                            </a>
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                @foreach($teamStats as $stat)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        @foreach($selectedColumns as $column)
                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                @if(is_array($stat->$column))
                                    {{ json_encode($stat->$column) }}
                                @elseif(is_numeric($stat->$column))
                                    {{ number_format($stat->$column, 2) }}
                                @else
                                    {{ $stat->$column }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $teamStats->appends(request()->input())->links() }}
        </div>
    </div>
</x-app-layout>