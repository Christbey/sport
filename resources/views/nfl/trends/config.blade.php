<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-8">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-gray-900">NFL Trends Analysis</h1>
                <div class="text-sm text-gray-500">
                    @if(isset($selectedTeam))
                        Analyzing: <span class="font-semibold">{{ $selectedTeam }}</span>
                    @else
                        Viewing Random Trends
                    @endif
                </div>
            </div>

            {{-- Filters --}}
            <x-nfl.trends-filter :selectedTeam="$selectedTeam ?? null" :games="$games ?? null"/>

            {{-- Content --}}
            @if(isset($trends))
                <div class="space-y-6">
                    {{-- Record Summary --}}
                    <x-nfl.record-summary :trends="$trends" :totalGames="$totalGames"/>

                    {{-- Trend Sections --}}
                    @foreach(['scoring', 'quarters', 'halves', 'margins', 'totals', 'first_score'] as $sectionKey)
                        @if(isset($trends[$sectionKey]) && !empty($trends[$sectionKey]))
                            <x-nfl.trend-section
                                    :title="ucfirst($sectionKey) . ' Trends'"
                                    :trends="$trends[$sectionKey]"
                            />
                        @endif
                    @endforeach
                </div>
            @else
                <x-nfl.random-trends/>
            @endif
        </div>
    </div>
</x-app-layout>