<?php

namespace App\Http\Controllers;

use App\Models\NbaTeamStat;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class NbaTeamStatController extends Controller
{
    public function index(Request $request)
    {
        // Get all possible columns from the fillable array
        $allColumns = (new NbaTeamStat())->getFillable();

        // Filter out columns with >50% null or zero values
        $validColumns = $this->getValidColumns($allColumns);

        // Always start with team name
        $selectedColumns = ['team_name'];

        // Check if the load button was pressed
        $userSelectedColumn = $request->input('load_column');

        // Validate the selected column
        if ($userSelectedColumn &&
            in_array($userSelectedColumn, $validColumns) &&
            $userSelectedColumn !== 'team_name' &&
            $userSelectedColumn !== 'team_ref') {
            $selectedColumns[] = $userSelectedColumn;

            // Base query to get team averages
            $query = NbaTeamStat::select(
                'nba_teams.name as team_name',
                'nba_teams.espn_id'
            )
                ->leftJoin('nba_teams', 'nba_team_stats.team_id', '=', 'nba_teams.espn_id')
                ->addSelect(
                    DB::raw("AVG(nba_team_stats.{$userSelectedColumn}) as {$userSelectedColumn}")
                )
                ->groupBy('nba_teams.name', 'nba_teams.espn_id');

            // Apply ordering
            $sortColumn = $request->input('sort', 'team_name');
            $sortDirection = $request->input('direction', 'asc');

            // Handle sorting
            if ($sortColumn === 'team_name') {
                $query->orderBy('nba_teams.name', $sortDirection);
            } elseif ($sortColumn === $userSelectedColumn) {
                $query->orderBy($userSelectedColumn, $sortDirection);
            }

            // Paginate
            $perPage = $request->input('per_page', 25);
            $teamStats = $query->paginate($perPage);

            // Calculate aggregations
            $aggregations = [
                'avg' => round($query->clone()->avg($userSelectedColumn), 2),
                'min' => round($query->clone()->min($userSelectedColumn), 2),
                'max' => round($query->clone()->max($userSelectedColumn), 2)
            ];
        } else {
            // If no column is loaded, show an empty result
            $teamStats = collect();
            $aggregations = null;
        }

        // Prepare columns for view
        $selectableColumns = array_diff($validColumns, ['team_name', 'team_ref']);

        return view('nba.team-stats.index', [
            'teamStats' => $teamStats,
            'allColumns' => $selectableColumns,
            'selectedColumns' => $selectedColumns,
            'selectedColumn' => $userSelectedColumn,
            'aggregations' => $aggregations
        ]);
    }

    // Existing getValidColumns method remains the same
    private function getValidColumns(array $columns)
    {
        $excludedColumns = [
            'event_id',
            'team_id',
            'opponent_id',
            'splits_json',
            'competition_ref',
            'event_date'
        ];

        $validColumns = [];

        // Get total number of records
        $totalRecords = NbaTeamStat::count();

        foreach ($columns as $column) {
            // Skip excluded columns
            if (in_array($column, $excludedColumns)) {
                continue;
            }

            try {
                // Count null or zero values, but handle potential SQL errors
                $nullOrZeroCount = NbaTeamStat::whereRaw("($column IS NULL OR $column = 0)")
                    ->count();

                // If less than 50% are null/zero, consider the column valid
                if ($nullOrZeroCount / $totalRecords <= 0.5) {
                    $validColumns[] = $column;
                }
            } catch (Exception $e) {
                // Log the error and skip this column
                Log::error("Error processing column $column: " . $e->getMessage());
                continue;
            }
        }

        return $validColumns;
    }
}