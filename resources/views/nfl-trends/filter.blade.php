<x-app-layout>
    
    <div class="container">
        <h1>NFL Trends</h1>
        @if (!empty($error))
            <div class="alert alert-danger">{{ $error }}</div>
        @elseif (!empty($results))
            <!-- Filter Form -->
            <form method="GET" action="{{ route('nfl.trends.filter') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="team" class="form-label">Team</label>
                        <input type="text" name="team" id="team" class="form-control" value="{{ old('team', $team) }}"
                               placeholder="Enter team abbreviation (e.g., NE)">
                    </div>
                    <div class="col-md-4">
                        <label for="season" class="form-label">Season</label>
                        <input type="number" name="season" id="season" class="form-control"
                               value="{{ old('season', $season) }}" placeholder="Enter season (e.g., 2023)">
                    </div>
                    <div class="col-md-4">
                        <label for="week" class="form-label">Week</label>
                        <input type="number" name="week" id="week" class="form-control" value="{{ old('week', $week) }}"
                               placeholder="Enter week (1-17)">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
            <table class="table">
                <thead>
                <tr>
                    <th>Category</th>
                    <th>Details</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($results['trends'] as $category => $trends)
                    <tr>
                        <td>{{ ucfirst($category) }}</td>
                        <td>
                            @foreach ($trends as $trend)
                                <p>{{ json_encode($trend) }}</p>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-info">No trends found for the selected filters.</div>
        @endif
    </div>
</x-app-layout>