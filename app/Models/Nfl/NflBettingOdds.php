<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflBettingOdds extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'game_date',
        'away_team',
        'home_team',
        'away_team_id',
        'home_team_id',
        'source',
        'spread_home',
        'spread_away',
        'total_over',
        'total_under',
        'moneyline_home',
        'moneyline_away',
        'implied_total_home',
        'implied_total_away',
    ];

    protected $casts = [
        'game_date' => 'datetime',
        'spread_home' => 'float',
        'spread_away' => 'float',
        'total_over' => 'float',
        'total_under' => 'float',
        'moneyline_home' => 'float',
        'moneyline_away' => 'float',
        'implied_total_home' => 'float',
        'implied_total_away' => 'float',
    ];

    public function hasSignificantChanges(array $newOdds): bool
    {
        return $this->detectOddsChanges($newOdds) !== [];
    }

    public function detectOddsChanges(array $newOdds): array
    {
        $changes = [];
        $thresholds = [
            'spread' => 0.5,
            'total' => 0.5,
            'moneyline' => 10,
        ];

        // Check spread changes
        if (abs(($newOdds['spread_home'] ?? 0) - ($this->spread_home ?? 0)) >= $thresholds['spread']) {
            $changes['spread'] = [
                'old' => $this->formatSpread($this->spread_home),
                'new' => $this->formatSpread($newOdds['spread_home']),
                'change' => number_format($newOdds['spread_home'] - $this->spread_home, 1)
            ];
        }

        // Check total changes
        if (abs(($newOdds['total_over'] ?? 0) - ($this->total_over ?? 0)) >= $thresholds['total']) {
            $changes['total'] = [
                'old' => number_format($this->total_over, 1),
                'new' => number_format($newOdds['total_over'], 1),
                'change' => number_format($newOdds['total_over'] - $this->total_over, 1)
            ];
        }

        // Check moneyline changes
        if ($this->hasSignificantMoneylineChange($this->moneyline_home, $newOdds['moneyline_home'] ?? 0, $thresholds['moneyline'])) {
            $changes['home_ml'] = [
                'old' => $this->formatMoneyline($this->moneyline_home),
                'new' => $this->formatMoneyline($newOdds['moneyline_home']),
                'change' => $newOdds['moneyline_home'] - $this->moneyline_home
            ];
        }

        if ($this->hasSignificantMoneylineChange($this->moneyline_away, $newOdds['moneyline_away'] ?? 0, $thresholds['moneyline'])) {
            $changes['away_ml'] = [
                'old' => $this->formatMoneyline($this->moneyline_away),
                'new' => $this->formatMoneyline($newOdds['moneyline_away']),
                'change' => $newOdds['moneyline_away'] - $this->moneyline_away
            ];
        }

        return $changes;
    }

    private function formatSpread(?float $spread): string
    {
        if ($spread === null) {
            return 'N/A';
        }
        return ($spread > 0 ? '+' : '') . $spread;
    }

    private function hasSignificantMoneylineChange($old, $new, $threshold): bool
    {
        if ($old === null || $new === null) {
            return false;
        }

        // For positive moneylines
        if ($old > 0 && $new > 0) {
            return abs($old - $new) >= $threshold;
        }

        // For negative moneylines
        if ($old < 0 && $new < 0) {
            return abs($old - $new) >= $threshold;
        }

        // If sign changed (one positive, one negative)
        return true;
    }

    private function formatMoneyline(?float $moneyline): string
    {
        if ($moneyline === null) {
            return 'N/A';
        }
        return ($moneyline > 0 ? '+' : '') . $moneyline;
    }

    public function getMatchupAttribute(): string
    {
        return "{$this->away_team} @ {$this->home_team}";
    }
}