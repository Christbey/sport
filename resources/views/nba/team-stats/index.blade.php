<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">NBA Team Statistics</h1>

        <form method="get" action="{{ route('nba.team-stats.index') }}" class="mb-6">
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Column Selection --}}
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Select Column</label>
                        <div class="flex items-center space-x-2">
                            <select
                                    name="load_column"
                                    class="flex-grow block rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="">Select a Column</option>
                                @foreach($allColumns as $column)
                                    @if($column === 'team_ref' || $column === 'team_name')
                                        @continue
                                    @endif
                                    <option
                                            value="{{ $column }}"
                                            {{ isset($selectedColumn) && $selectedColumn === $column ? 'selected' : '' }}
                                    >
                                        {{ ucwords(str_replace('_', ' ', $column)) }}
                                    </option>
                                @endforeach
                            </select>

                            <button
                                    type="submit"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Load
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if($selectedColumn && count($teamStats) > 0)
            {{-- Results Table --}}
            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="w-full bg-white">
                    <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <tr>
                        @foreach($selectedColumns as $column)
                            <th class="py-3 px-6 text-left">
                                <a href="{{ route('nba.team-stats.index', array_merge(request()->except('sort', 'direction'), [
                                            'load_column' => $selectedColumn,
                                            'sort' => $column,
                                            'direction' => isset($sortColumn) && $sortColumn === $column && $sortDirection === 'asc' ? 'desc' : 'asc'
                                        ])) }}">
                                    {{ ucwords(str_replace('_', ' ', $column)) }}
                                    @if(isset($sortColumn) && $sortColumn === $column)
                                        {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                                    @endif
                                </a>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                    @foreach($teamStats as $stat)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            @foreach($selectedColumns as $column)
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    @if(is_numeric($stat->$column))
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

                @if($aggregations)
                    <div class="mt-4 bg-gray-100 p-4">
                        <h3 class="font-bold mb-2">{{ ucwords(str_replace('_', ' ', $selectedColumn)) }}
                            Aggregations</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <span class="font-semibold">Average:</span>
                                {{ $aggregations['avg'] }}
                            </div>
                            <div>
                                <span class="font-semibold">Minimum:</span>
                                {{ $aggregations['min'] }}
                            </div>
                            <div>
                                <span class="font-semibold">Maximum:</span>
                                {{ $aggregations['max'] }}
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Pagination --}}
                <div class="mt-4 px-4 pb-4">
                    {{ $teamStats->appends(request()->input())->links() }}
                </div>
            </div>
        @else
            <div class="bg-gray-100 p-4 rounded text-center text-gray-600">
                Select a column and click "Load" to view team statistics.
            </div>
        @endif
    </div>
</x-app-layout>