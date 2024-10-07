<x-app-layout>
    <div class="container">
        <h1 class="text-center">NFL Elo Ratings for Teams</h1>

        @if($eloRatings->isEmpty())
            <p class="text-center">No Elo ratings available.</p>
        @else
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th scope="col">Team</th>
                    <th scope="col">Year</th>
                    <th scope="col">Final Elo</th>
                    <th scope="col">Expected Wins</th>
                    <th scope="col">Predicted Spread</th>
                </tr>
                </thead>
                <tbody>
                @foreach($eloRatings as $rating)
                    <tr>
                        <td>{{ $rating->team }}</td>
                        <td>{{ $rating->year }}</td>
                        <td>{{ $rating->final_elo }}</td>
                        <td>{{ $rating->expected_wins }}</td>
                        <td>{{ $rating->predicted_spread }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-app-layout>