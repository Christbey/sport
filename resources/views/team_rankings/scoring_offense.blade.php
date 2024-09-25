<!-- resources/views/team_rankings/scoring_offense.blade.php -->
<x-app-layout>
    <div class="container">
        <h2>Scoring Offense and Defense Stats</h2>

        <form id="rankingForm">
            <label for="statSelect">Choose a Stat:</label>
            <select id="statSelect" class="form-control">
                <option value="">-- Select a Stat --</option>

                <!-- Scoring Offense -->
                <optgroup label="Scoring Offense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-game']) }}">Points per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'average-scoring-margin']) }}">Average Scoring Margin</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'yards-per-point']) }}">Yards per Point</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'yards-per-point-margin']) }}">Yards per Point Margin</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-play']) }}">Points per Play</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'points-per-play-margin']) }}">Points per Play Margin</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'touchdowns-per-game']) }}">Touchdowns per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scoring-attempts-per-game']) }}">Red Zone Scoring Attempts per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scores-per-game']) }}">Red Zone Scores per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-offense', 'stat' => 'red-zone-scoring-pct']) }}">Red Zone Scoring Percentage</option>
                </optgroup>

                <!-- Offense by Quarter -->
                <optgroup label="Offense by Quarter">
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '1st-quarter-points-per-game']) }}">1st Quarter Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '2nd-quarter-points-per-game']) }}">2nd Quarter Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '3rd-quarter-points-per-game']) }}">3rd Quarter Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '4th-quarter-points-per-game']) }}">4th Quarter Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => 'overtime-points-per-game']) }}">Overtime Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '1st-half-points-per-game']) }}">1st Half Points/Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'offense-by-quarter', 'stat' => '2nd-half-points-per-game']) }}">2nd Half Points/Game</option>
                </optgroup>

                <!-- Total Offense -->
                <optgroup label="Total Offense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'yards-per-game']) }}">Yards per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'plays-per-game']) }}">Plays per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'yards-per-play']) }}">Yards per Play</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'first-downs-per-game']) }}">First Downs per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'third-downs-per-game']) }}">Third Downs per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-offense', 'stat' => 'fourth-downs-per-game']) }}">Fourth Downs per Game</option>
                </optgroup>

                <!-- Rushing Offense -->
                <optgroup label="Rushing Offense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-attempts-per-game']) }}">Rushing Attempts per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'rushing-offense', 'stat' => 'rushing-yards-per-game']) }}">Rushing Yards per Game</option>
                    <!-- More rushing offense stats can be added similarly -->
                </optgroup>

                <!-- Passing Offense -->
                <optgroup label="Passing Offense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'pass-attempts-per-game']) }}">Pass Attempts per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'passing-offense', 'stat' => 'completions-per-game']) }}">Completions per Game</option>
                    <!-- More passing offense stats can be added similarly -->
                </optgroup>

                <!-- Scoring Defense -->
                <optgroup label="Scoring Defense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opponent-points-per-game']) }}">Opponent Points per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'scoring-defense', 'stat' => 'opp-yards-per-point']) }}">Opp Yards per Point</option>
                    <!-- More scoring defense stats can be added similarly -->
                </optgroup>

                <!-- Defense by Quarter -->
                <optgroup label="Defense by Quarter">
                    <option value="{{ route('team-rankings.stat', ['category' => 'defense-by-quarter', 'stat' => 'opp-1st-quarter-points-per-game']) }}">Opp 1st Quarter Points/Game</option>
                    <!-- More defense by quarter stats can be added similarly -->
                </optgroup>

                <!-- Total Defense -->
                <optgroup label="Total Defense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'total-defense', 'stat' => 'opponent-yards-per-game']) }}">Opponent Yards per Game</option>
                    <!-- More total defense stats can be added similarly -->
                </optgroup>

                <!-- Rushing Defense -->
                <optgroup label="Rushing Defense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'rushing-defense', 'stat' => 'opponent-rushing-attempts-per-game']) }}">Opponent Rushing Attempts per Game</option>
                    <!-- More rushing defense stats can be added similarly -->
                </optgroup>

                <!-- Passing Defense -->
                <optgroup label="Passing Defense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'passing-defense', 'stat' => 'opponent-pass-attempts-per-game']) }}">Opponent Pass Attempts per Game</option>
                    <!-- More passing defense stats can be added similarly -->
                </optgroup>

                <!-- Special Teams Defense -->
                <optgroup label="Special Teams Defense">
                    <option value="{{ route('team-rankings.stat', ['category' => 'special-teams-defense', 'stat' => 'opponent-other-touchdowns-per-game']) }}">Opponent Non-Offensive Touchdowns per Game</option>
                    <!-- More special teams defense stats can be added similarly -->
                </optgroup>

                <!-- Turnovers -->
                <optgroup label="Turnovers">
                    <option value="{{ route('team-rankings.stat', ['category' => 'turnovers', 'stat' => 'interceptions-thrown-per-game']) }}">Interceptions Thrown per Game</option>
                    <!-- More turnover stats can be added similarly -->
                </optgroup>

                <!-- Penalties -->
                <!-- Penalties -->
                <optgroup label="Penalties">
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalties-per-game']) }}">Penalties per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-yards-per-game']) }}">Penalty Yards per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-first-downs-per-game']) }}">Penalty First Downs per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalties-per-game']) }}">Opponent Penalties per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-yards-per-game']) }}">Opponent Penalty Yards per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-first-downs-per-game']) }}">Opponent Penalty First Downs per Game</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalty-yards-per-penalty']) }}">Penalty Yards per Penalty</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'penalties-per-play']) }}">Penalties per Play</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalty-yards-per-penalty']) }}">Opponent Penalty Yards per Penalty</option>
                    <option value="{{ route('team-rankings.stat', ['category' => 'penalties', 'stat' => 'opponent-penalties-per-play']) }}">Opponent Penalties per Play</option>
                </optgroup>

            </select>
        </form>
        <form id="rankingTypeForm" action="{{ route('team-rankings.fetch', ['rankingType' => 'default']) }}" method="GET">
            <div class="form-group">
                <label for="rankingType">Choose a Ranking:</label>
                <select id="rankingType" name="rankingType" class="form-control">
                    <option value="">-- Select a Ranking --</option>

                    <!-- Predictive Rankings -->
                    <optgroup label="Predictive Rankings">
                        <option value="predictive-by-other">Predictive Rating</option>
                        <option value="luck-by-other">Luck Rating</option>
                        <option value="consistency-by-other">Consistency Rating</option>
                    </optgroup>

                    <!-- Home & Away Rankings -->
                    <optgroup label="Home & Away Rankings">
                        <option value="home-by-other">Home Rating</option>
                        <option value="away-by-other">Away Rating</option>
                        <option value="home-adv-by-other">Home Advantage</option>
                    </optgroup>

                    <!-- Strength of Schedule -->
                    <optgroup label="Strength of Schedule">
                        <option value="schedule-strength-by-other">Strength of Schedule</option>
                        <option value="future-sos-by-other">Future SOS</option>
                        <option value="season-sos-by-other">Season SOS</option>
                        <option value="sos-basic-by-other">SOS - Basic</option>
                        <option value="in-division-sos-by-other">In-Div SOS</option>
                        <option value="non-division-sos-by-other">Non-Div SOS</option>
                    </optgroup>

                    <!-- Recent Performance -->
                    <optgroup label="Recent Performance">
                        <option value="last-5-games-by-other">Last 5 Rating</option>
                        <option value="last-10-games-by-other">Last 10 Rating</option>
                    </optgroup>

                    <!-- Versus Rankings -->
                    <optgroup label="Versus Rankings">
                        <option value="vs-1-5-by-other">Vs. 1-5 Rating</option>
                        <option value="vs-6-10-by-other">Vs. 6-10 Rating</option>
                        <option value="vs-11-16-by-other">Vs. 11-16 Rating</option>
                        <option value="vs-17-22-by-other">Vs. 17-22 Rating</option>
                        <option value="vs-23-32-by-other">Vs. 23-32 Rating</option>
                    </optgroup>

                    <!-- Half Performance -->
                    <optgroup label="Half Performance">
                        <option value="first-half-by-other">First Half Rating</option>
                        <option value="second-half-by-other">Second Half Rating</option>
                    </optgroup>
                </select>
            </div>
        </form>
    </div>

    <!-- JavaScript for redirecting on option change -->
    <script>
        document.getElementById('statSelect').addEventListener('change', function () {
            const selectedValue = this.value;
            if (selectedValue) {
                window.location.href = selectedValue;
            }
        });
    </script>
    <!-- JavaScript for redirecting on option change -->
    <script>
        document.getElementById('rankingType').addEventListener('change', function () {
            const selectedValue = this.value;
            if (selectedValue) {
                // Update the form action dynamically
                const formAction = '{{ route("team-rankings.fetch", ["rankingType" => ":rankingType"]) }}'.replace(':rankingType', selectedValue);
                document.getElementById('rankingTypeForm').action = formAction;
                document.getElementById('rankingTypeForm').submit();
            }
        });
    </script>
</x-app-layout>


