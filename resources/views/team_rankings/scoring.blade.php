<x-app-layout>

    <div class="container mx-auto">
        <h2>Scoring Offense and Defense Stats</h2>

        <form id="rankingForm">
            <label for="statSelect">Choose a Stat:</label>
            <select id="statSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">-- Select a Stat --</option>

                <!-- Scoring Offense -->
                <optgroup label="Scoring Offense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-game']) }}">Points per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'average-scoring-margin']) }}">Average Scoring Margin</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'yards-per-point']) }}">Yards per Point</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'yards-per-point-margin']) }}">Yards per Point Margin</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-play']) }}">Points per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-play-margin']) }}">Points per Play Margin</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'touchdowns-per-game']) }}">Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scoring-attempts-per-game']) }}">Red Zone Scoring Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scores-per-game']) }}">Red Zone Scores per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scoring-pct']) }}">Red Zone Scoring Percentage</option>
                </optgroup>

                <!-- Offense by Quarter -->
                <optgroup label="Offense by Quarter">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '1st-quarter-points-per-game']) }}">1st Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '2nd-quarter-points-per-game']) }}">2nd Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '3rd-quarter-points-per-game']) }}">3rd Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '4th-quarter-points-per-game']) }}">4th Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => 'overtime-points-per-game']) }}">Overtime Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '1st-half-points-per-game']) }}">1st Half Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '2nd-half-points-per-game']) }}">2nd Half Points/Game</option>
                </optgroup>

                <!-- Total Offense -->
                <optgroup label="Total Offense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'yards-per-game']) }}">Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'plays-per-game']) }}">Plays per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'yards-per-play']) }}">Yards per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'first-downs-per-game']) }}">First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'third-downs-per-game']) }}">Third Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-offense', 'stat' => 'fourth-downs-per-game']) }}">Fourth Downs per Game</option>
                </optgroup>

                <!-- Rushing Offense -->
                <optgroup label="Rushing Offense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-attempts-per-game']) }}">Rushing Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-yards-per-game']) }}">Rushing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-first-downs-per-game']) }}">Rushing First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-touchdowns-per-game']) }}">Rushing Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'yards-per-rush-attempt']) }}">Yards per Rush Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-play-pct']) }}">Rushing Play Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-touchdown-pct']) }}">Rushing Touchdown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-first-down-pct']) }}">Rushing First Down Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-yards-pct']) }}">Rushing Yards Percentage</option>
                </optgroup>

                <!-- Passing Offense -->
                <optgroup label="Passing Offense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'pass-attempts-per-game']) }}">Pass Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'completions-per-game']) }}">Completions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'incompletions-per-game']) }}">Incompletions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'completion-pct']) }}">Completion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-yards-per-game']) }}">Passing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'gross-passing-yards-per-game']) }}">Gross Passing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'yards-per-pass-attempt']) }}">Yards per Pass Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'yards-per-completion']) }}">Yards per Completion</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-touchdowns-per-game']) }}">Passing Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-touchdown-pct']) }}">Passing Touchdown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'qb-sacked-per-game']) }}">QB Sacked per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'qb-sacked-pct']) }}">QB Sacked Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-first-downs-per-game']) }}">Passing First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-first-down-pct']) }}">Passing First Down Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'average-team-passer-rating']) }}">Average Team Passer Rating</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-play-pct']) }}">Passing Play Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'passing-yards-pct']) }}">Passing Yards Percentage</option>
                </optgroup>

                <!-- Scoring Defense -->
                <optgroup label="Scoring Defense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-points-per-game']) }}">Opponent Points per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opp-yards-per-point']) }}">Opp Yards per Point</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-points-per-play']) }}">Opponent Points per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-touchdowns-per-game']) }}">Opponent Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-red-zone-scoring-attempts-per-game']) }}">Opponent Red Zone Scoring Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-red-zone-scores-per-game']) }}">Opponent Red Zone Scores per Game (TDs only)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-red-zone-scoring-pct']) }}">Opponent Red Zone Scoring Percentage (TD only)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-extra-point-attempts-per-game']) }}">Opponent Extra Point Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-extra-points-made-per-game']) }}">Opponent Extra Points Made per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-two-point-conversion-attempts-per-game']) }}">Opponent Two Point Conversion Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-two-point-conversions-per-game']) }}">Opponent Two Point Conversions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-points-per-field-goal-attempt']) }}">Opponent Points per Field Goal Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-extra-point-conversion-pct']) }}">Opponent Extra Point Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-two-point-conversion-pct']) }}">Opponent Two Point Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-offensive-touchdowns-per-game']) }}">Opponent Offensive Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-defensive-touchdowns-per-game']) }}">Opponent Defensive Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-special-teams-touchdowns-per-game']) }}">Opponent Special Teams Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-offensive-points-per-game']) }}">Opponent Offensive Points per Game (Estimated)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-defensive-points-per-game']) }}">Opponent Defensive Points per Game (Estimated)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-special-teams-points-per-game']) }}">Opponent Special Teams Points per Game (Estimated)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-offensive-point-share-pct']) }}">Opponent Offensive Point Share Percentage (Estimated)</option>
                </optgroup>

                <!-- Defense by Quarter -->
                <optgroup label="Defense by Quarter">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-1st-quarter-points-per-game']) }}">Opp 1st Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-2nd-quarter-points-per-game']) }}">Opp 2nd Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-3rd-quarter-points-per-game']) }}">Opp 3rd Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-4th-quarter-points-per-game']) }}">Opp 4th Quarter Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-overtime-points-per-game']) }}">Opp Overtime Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opponent-1st-half-points-per-game']) }}">Opponent 1st Half Points/Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opponent-2nd-half-points-per-game']) }}">Opponent 2nd Half Points/Game</option>
                </optgroup>

                <!-- Total Defense -->
                <optgroup label="Total Defense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-yards-per-game']) }}">Opponent Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-plays-per-game']) }}">Opponent Plays per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-yards-per-play']) }}">Opponent Yards per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-first-downs-per-game']) }}">Opponent First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-third-downs-per-game']) }}">Opponent Third Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-third-down-conversions-per-game']) }}">Opponent Third Down Conversions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-fourth-downs-per-game']) }}">Opponent Fourth Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-fourth-down-conversions-per-game']) }}">Opponent Fourth Down Conversions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-average-time-of-possession-net-of-ot']) }}">Opponent Average Time of Possession (Excluding OT)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-time-of-possession-pct-net-of-ot']) }}">Opponent Time of Possession Percentage (Excluding OT)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-seconds-per-play']) }}">Opponent Seconds per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-first-downs-per-play']) }}">Opponent First Downs per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-third-down-conversion-pct']) }}">Opponent Third Down Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-fourth-down-conversion-pct']) }}">Opponent Fourth Down Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-punts-per-play']) }}">Opponent Punts per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-punts-per-offensive-score']) }}">Opponent Punts per Offensive Score</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'tackles-per-game']) }}">Tackles per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'solo-tackles-per-game']) }}">Solo Tackles per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'total-defense', 'stat' => 'assisted-tackles-per-game']) }}">Assisted Tackles per Game</option>
                </optgroup>

                <!-- Rushing Defense -->
                <optgroup label="Rushing Defense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-attempts-per-game']) }}">Opponent Rushing Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-yards-per-game']) }}">Opponent Rushing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-first-downs-per-game']) }}">Opponent Rushing First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-touchdowns-per-game']) }}">Opponent Rushing Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-yards-per-rush-attempt']) }}">Opponent Yards per Rush Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-play-pct']) }}">Opponent Rushing Play Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-touchdown-pct']) }}">Opponent Rushing Touchdown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-first-down-pct']) }}">Opponent Rushing First Down Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-yards-pct']) }}">Opponent Rushing Yards Percentage</option>
                </optgroup>

                <!-- Passing Defense -->
                <optgroup label="Passing Defense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-pass-attempts-per-game']) }}">Opponent Pass Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-completions-per-game']) }}">Opponent Completions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-incompletions-per-game']) }}">Opponent Incompletions per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-completion-pct']) }}">Opponent Completion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-yards-per-game']) }}">Opponent Passing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-gross-passing-yards-per-game']) }}">Opponent Gross Passing Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-yards-per-pass-attempt']) }}">Opponent Yards per Pass Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-yards-per-completion']) }}">Opponent Yards per Completion</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-first-downs-per-game']) }}">Opponent Passing First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-touchdowns-per-game']) }}">Opponent Passing Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-touchdown-pct']) }}">Opponent Passing Touchdown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-average-team-passer-rating']) }}">Opponent Average Team Passer Rating</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'sack-pct']) }}">Sack Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-play-pct']) }}">Opponent Passing Play Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-yards-pct']) }}">Opponent Passing Yards Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'sacks-per-game']) }}">Sacks per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-passing-first-down-pct']) }}">Opponent Passing First Down Percentage</option>
                </optgroup>

                <!-- Special Teams Defense -->
                <optgroup label="Special Teams Defense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-other-touchdowns-per-game']) }}">Opponent Non-Offensive Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-field-goal-attempts-per-game']) }}">Opponent Field Goal Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-field-goals-made-per-game']) }}">Opponent Field Goals Made per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'field-goals-blocked-per-game']) }}">Field Goals Blocked per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-kicking-points-per-game']) }}">Opponent Kicking Points per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-punt-attempts-per-game']) }}">Opponent Punt Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'punts-blocked-per-game']) }}">Punts Blocked per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-gross-punt-yards-per-game']) }}">Opponent Gross Punt Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-net-punt-yards-per-game']) }}">Opponent Net Punt Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-kickoffs-per-game']) }}">Opponent Kickoffs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-touchbacks-per-game']) }}">Opponent Touchbacks per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-kickoff-touchback-pct']) }}">Opponent Kickoff Touchback Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-field-goal-conversion-pct']) }}">Opponent Field Goal Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'block-field-goal-pct']) }}">Block Field Goal Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-field-goal-conversion-pct-net-of-blocks']) }}">Opponent Field Goal Conversion Percentage (Net of Blocks)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'block-punt-pct']) }}">Block Punt Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-net-yards-per-punt-attempt']) }}">Opponent Net Yards per Punt Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-gross-yards-per-successful-punt']) }}">Opponent Gross Yards per Successful Punt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-net-yards-per-successful-punt']) }}">Opponent Net Yards per Successful Punt</option>
                </optgroup>

                <!-- Special Teams Offense -->
                <optgroup label="Special Teams Offense">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'other-touchdowns-per-game']) }}">Non-Offensive Touchdowns per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goal-attempts-per-game']) }}">Field Goal Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goals-made-per-game']) }}">Field Goals Made per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goals-got-blocked-per-game']) }}">Field Goals Got Blocked per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'kicking-points-per-game']) }}">Kicking Points per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'punt-attempts-per-game']) }}">Punt Attempts per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'punts-got-blocked-per-game']) }}">Punts Got Blocked per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'gross-punt-yards-per-game']) }}">Gross Punt Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'net-punt-yards-per-game']) }}">Net Punt Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'kickoffs-per-game']) }}">Kickoffs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'touchbacks-per-game']) }}">Touchbacks per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'kickoff-touchback-pct']) }}">Kickoff Touchback Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goal-conversion-pct']) }}">Field Goal Conversion Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goal-got-blocked-pct']) }}">Field Goal Got Blocked Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'field-goal-conversion-pct-net-of-blocks']) }}">Field Goal Conversion Percentage (Excluding Blocks)</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'punt-blocked-pct']) }}">Punt Blocked Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'net-yards-per-punt-attempt']) }}">Net Yards per Punt Attempt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'gross-yards-per-successful-punt']) }}">Gross Yards per Successful Punt</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'special-teams-offense', 'stat' => 'net-yards-per-successful-punt']) }}">Net Yards per Successful Punt</option>
                </optgroup>

                <!-- Turnovers -->
                <optgroup label="Turnovers">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'interceptions-thrown-per-game']) }}">Interceptions Thrown per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'percent-of-games-with-an-interception-thrown']) }}">Percent of Games With Interception Thrown</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'fumbles-per-game']) }}">Fumbles per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'fumbles-lost-per-game']) }}">Fumbles Lost per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'fumbles-not-lost-per-game']) }}">Fumbles Not Lost per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'safeties-per-game']) }}">Safeties per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'giveaways-per-game']) }}">Giveaways per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'turnover-margin-per-game']) }}">Turnover Margin per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'interceptions-per-game']) }}">Opponent Interceptions Thrown per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'percent-of-games-with-an-interception']) }}">Opponent Percent of Games With Interception Thrown</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-fumbles-per-game']) }}">Opponent Fumbles per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-fumbles-lost-per-game']) }}">Opponent Fumbles Lost per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-fumbles-not-lost-per-game']) }}">Opponent Fumbles Not Lost per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-safeties-per-game']) }}">Opponent Safeties per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'takeaways-per-game']) }}">Takeaways per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'pass-intercepted-pct']) }}">Interceptions Thrown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'fumble-recovery-pct']) }}">Fumble Recovery Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'giveaway-fumble-recovery-pct']) }}">Giveaway Fumble Recovery Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'takeaway-fumble-recovery-pct']) }}">Takeaway Fumble Recovery Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'interception-pct']) }}">Opponent Interceptions Thrown Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-fumble-recovery-pct']) }}">Opponent Fumble Recovery Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-giveaway-fumble-recovery-pct']) }}">Opponent Giveaway Fumble Recovery Percentage</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'turnovers', 'stat' => 'opponent-takeaway-fumble-recovery-pct']) }}">Opponent Takeaway Fumble Recovery Percentage</option>
                </optgroup>

                <!-- Penalties -->
                <optgroup label="Penalties">
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalties-per-game']) }}">Penalties per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-yards-per-game']) }}">Penalty Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-first-downs-per-game']) }}">Penalty First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalties-per-game']) }}">Opponent Penalties per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-yards-per-game']) }}">Opponent Penalty Yards per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-first-downs-per-game']) }}">Opponent Penalty First Downs per Game</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-yards-per-penalty']) }}">Penalty Yards per Penalty</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalties-per-play']) }}">Penalties per Play</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-yards-per-penalty']) }}">Opponent Penalty Yards per Penalty</option>
                    <option value="{{ route('api.team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalties-per-play']) }}">Opponent Penalties per Play</option>
                </optgroup>
            </select>
        </form>

        <!-- Section to dynamically display the stat data -->
        <div id="statDataTable" class="mt-4">
            <!-- Table will be rendered here dynamically -->
        </div>
    </div>
    <script>
        document.getElementById('statSelect').addEventListener('change', function () {
            const selectedValue = this.value;
            console.log("Selected Value URL:", selectedValue); // Log the selected URL

            if (selectedValue) {
                fetch(selectedValue)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("API Response:", data); // Log the API response
                        renderStatTable(data.data);
                    })
                    .catch(error => {
                        console.error('Error fetching stat data:', error);
                    });
            }
        });

        // Render the stat data as a table
        function renderStatTable(rows) {
            const tableContainer = document.getElementById('statDataTable');
            let tableHTML = `
                <div class="overflow-x-auto">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2">Rank</th>
                                <th class="px-4 py-2">Team</th>
                                <th class="px-4 py-2">2024</th>
                                <th class="px-4 py-2">Last 3</th>
                                <th class="px-4 py-2">Last 1</th>
                                <th class="px-4 py-2">Home</th>
                                <th class="px-4 py-2">Away</th>
                                <th class="px-4 py-2">2023</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
            `;

            rows.forEach(row => {
                tableHTML += `
                    <tr class="hover:bg-gray-100">
                        <td class="px-4 py-2">${row.rank}</td>
                        <td class="px-4 py-2">${row.team}</td>
                        <td class="px-4 py-2">${row['2024']}</td>
                        <td class="px-4 py-2">${row.last_3}</td>
                        <td class="px-4 py-2">${row.last_1}</td>
                        <td class="px-4 py-2">${row.home}</td>
                        <td class="px-4 py-2">${row.away}</td>
                        <td class="px-4 py-2">${row['2023']}</td>
                    </tr>
                `;
            });

            tableHTML += `
                        </tbody>
                    </table>
                </div>
            `;

            tableContainer.innerHTML = tableHTML;
        }
    </script>
</x-app-layout>
