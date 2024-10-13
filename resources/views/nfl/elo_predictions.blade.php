<!-- resources/views/nfl/elo_predictions.blade.php -->
<x-app-layout>
    <div class="container mx-auto max-w-5xl">
        <h1>NFL Elo Predictions</h1>

        <!-- Week Selection Form -->
        <form method="GET" action="{{ route('nfl.elo.predictions') }}" class="mb-4">
            <label for="week" class="mr-2">Select Week:</label>
            <select name="week" id="week" onchange="this.form.submit()">
                <option value="">All Weeks</option>
                @foreach($weeks as $wk)
                    <option value="{{ $wk }}" {{ isset($week) && $week == $wk ? 'selected' : '' }}>
                        Week {{ $wk }}</option>
                @endforeach
            </select>
        </form>

        <x-table>
            <!-- Table Header Slot -->
            <x-slot name="header">
                <tr class="text-center">
                    <th>Team</th>
                    <th>Opponent</th>
                    <th>Year</th>
                    <th>Week</th>
                    <th>Team Elo</th>
                    <th>Opponent Elo</th>
                    <th>Predicted Spread</th>
                </tr>
            </x-slot>

            <!-- Table Body Slot -->
            <x-slot name="body">
                @forelse($eloPredictions as $prediction)
                    <tr class="text-center">
                        <td>{{ $prediction->team }}</td>
                        <td>{{ $prediction->opponent }}</td>
                        <td>{{ $prediction->year }}</td>
                        <td>{{ $prediction->week }}</td>
                        <td>{{ number_format($prediction->team_elo, 2) }}</td>
                        <td>{{ number_format($prediction->opponent_elo, 2) }}</td>
                        <td>{{ number_format($prediction->predicted_spread, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No predictions found for the selected week.</td>
                    </tr>
                @endforelse
            </x-slot>
        </x-table>
    </div>
</x-app-layout>
