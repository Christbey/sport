@props(['trends', 'totalGames'])

<div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Record Summary (Last {{ $totalGames }} Games)</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Overall Record --}}
        <x-nfl.record-card
                title="Overall Record"
                :wins="$trends['general']['record']['wins']"
                :losses="$trends['general']['record']['losses']"
                :percentage="$trends['general']['record']['percentage']"
        />

        {{-- ATS Record --}}
        <x-nfl.record-card
                title="Against The Spread"
                :wins="$trends['general']['ats']['wins']"
                :losses="$trends['general']['ats']['losses']"
                :percentage="$trends['general']['ats']['percentage']"
        />

        {{-- Over/Under Record --}}
        <x-nfl.record-card
                title="Over/Under"
                :wins="$trends['general']['over_under']['overs']"
                :losses="$trends['general']['over_under']['unders']"
                :percentage="$trends['general']['over_under']['percentage']"
        />
    </div>
</div>