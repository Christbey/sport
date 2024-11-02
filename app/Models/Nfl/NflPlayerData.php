<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflPlayerData extends Model
{
    use HasFactory;

    protected $table = 'nfl_player_data';

    protected $fillable = [
        'playerID',
        'fantasyProsLink',
        'jerseyNum',
        'espnName',
        'cbsLongName',
        'yahooLink',
        'sleeperBotID',
        'fantasyProsPlayerID',
        'lastGamePlayed',
        'espnLink',
        'yahooPlayerID',
        'isFreeAgent',
        'pos',
        'school',
        'teamID',
        'cbsShortName',
        'injury_return_date',
        'injury_description',
        'injury_date',
        'injury_designation',
        'rotoWirePlayerIDFull',
        'rotoWirePlayerID',
        'exp',
        'height',
        'espnHeadshot',
        'fRefID',
        'weight',
        'team',
        'espnIDFull',
        'bDay',
        'age',
        'longName'
    ];
}
