<!-- resources/views/nfl/elo_predictions.blade.php -->
<x-app-layout>
    <div class="container">
        <h1>NFL Elo Predictions</h1>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Team</th>
                <th>Opponent</th>
                <th>Year</th>
                <th>Week</th>
                <th>Team Elo</th>
                <th>Opponent Elo</th>
                <th>Expected Outcome</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
            </thead>
            <tbody>
            @foreach($eloPredictions as $prediction)
                <tr>
                    <td>{{ $prediction->team }}</td>
                    <td>{{ $prediction->opponent }}</td>
                    <td>{{ $prediction->year }}</td>
                    <td>{{ $prediction->week }}</td>
                    <td>{{ $prediction->team_elo }}</td>
                    <td>{{ $prediction->opponent_elo }}</td>
                    <td>{{ $prediction->expected_outcome }}</td>
                    <td>{{ $prediction->created_at }}</td>
                    <td>{{ $prediction->updated_at }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>