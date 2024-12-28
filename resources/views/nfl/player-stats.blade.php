<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Stats</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">NFL Player Stats</h1>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('nfl.player-stats') }}" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="long_name" class="form-control" placeholder="Player Name"
                       value="{{ $longName }}">
            </div>
            <div class="col-md-3">
                <input type="text" name="team_abv" class="form-control" placeholder="Team Abbreviation"
                       value="{{ $teamAbv }}">
            </div>
            <div class="col-md-3">
                <select name="year" class="form-control">
                    @foreach(range(date('Y'), date('Y') - 10) as $y)
                        <option value="{{ $y }}" {{ (isset($year) && $year == $y) || (!isset($year) && $y == config('app.default_nfl_year')) ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('nfl.player-stats') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Display Receiving Stats -->
    @if($receivingStats['success'])
        <h2>Receiving Stats</h2>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Player</th>
                <th>Team</th>
                <th>Avg Yards</th>
                <th>Max Yards</th>
                <th>Min Yards</th>
                <th>Avg Touchdowns</th>
                <th>Total Touchdowns</th>
                <th>Total Receptions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($receivingStats['data'] as $stat)
                <tr>
                    <td>{{ $stat['name'] }}</td>
                    <td>{{ $stat['team'] }}</td>
                    <td>{{ number_format($stat['receiving_stats']['avg_yards'], 2) }}</td>
                    <td>{{ $stat['receiving_stats']['max_yards'] }}</td>
                    <td>{{$stat['receiving_stats']['min_yards']}}</td>
                    <td>{{$stat['receiving_stats']['avg_touchdowns']}}</td>
                    <td>{{ $stat['receiving_stats']['total_touchdowns'] }}</td>
                    <td>{{ $stat['receiving_stats']['total_receptions'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>{{ $receivingStats['message'] }}</p>
    @endif
</div>
</body>
</html>
