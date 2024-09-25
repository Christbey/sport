<!-- resources/views/team_rankings/stat_data.blade.php -->
<x-app-layout>
    <div class="container">
        <h2>{{ ucfirst(str_replace('-', ' ', $stat)) }} Statistics</h2>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Rank</th>
                <th>Team</th>
                <th>2024</th>
                <th>Last 3</th>
                <th>Last 1</th>
                <th>Home</th>
                <th>Away</th>
                <th>2023</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['rank'] }}</td>
                    <td><a href="{{ $row['team_link'] }}">{{ $row['team'] }}</a></td>
                    <td>{{ $row['2024'] }}</td>
                    <td>{{ $row['last_3'] }}</td>
                    <td>{{ $row['last_1'] }}</td>
                    <td>{{ $row['home'] }}</td>
                    <td>{{ $row['away'] }}</td>
                    <td>{{ $row['2023'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
