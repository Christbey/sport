@php use Illuminate\Contracts\Pagination\Paginator; @endphp
<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <div class="sm:flex sm:items-center sm:justify-between mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Stats Analysis</h1>
                            <p class="mt-2 text-sm text-gray-600">
                                Showing results for {{ request('query') }}
                                @if(request('team'))
                                    filtered by {{ request('team') }}
                                @endif
                            </p>
                        </div>
                        <div class="mt-4 sm:mt-0">
                            <a href="{{ route('nfl.stats.index') }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                New Query
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                @foreach($tableHeadings as $heading)
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($data as $row)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    @foreach($row as $value)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(isset($players) && $players instanceof Paginator)
                        <div class="mt-6">
                            {{ $players->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>