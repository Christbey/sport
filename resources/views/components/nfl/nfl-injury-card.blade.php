@php use Carbon\Carbon; @endphp
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="bg-red-600 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">{{ $team }} Injuries</h2>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @forelse($injuries as $injury)
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-red-700 font-semibold">{{ $injury->espnName }}</p>
                    @if(!empty($injury->injury_description))
                        <p class="text-red-700">{{ $injury->injury_description }}</p>
                    @endif
                    <p class="text-red-700 text-sm">Status: {{ $injury->injury_designation }}</p>
                    @if(!empty($injury->injury_return_date))
                        <p class="text-red-700 text-sm">
                            Return
                            Date: {{ Carbon::createFromFormat('Ymd', $injury->injury_return_date)->format('M d, Y') }}
                        </p>
                    @else
                        <p class="text-red-700 text-sm">Return Date: Unknown</p>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No injuries reported</p>
            @endforelse
        </div>
    </div>
</div>
