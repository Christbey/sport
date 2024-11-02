<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollegeFootballDataController extends Controller
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        // Initialize Guzzle client
        $this->client = new Client([
            'base_uri' => 'https://api.collegefootballdata.com/',
        ]);

        // Set your API key (you should store this in your .env file)
        $this->apiKey = config('services.college_football_data.key');
    }

    /**
     * Fetch game results for a specific year.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGames(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week', null);
        $seasonType = $request->input('seasonType', null);
        $classification = $request->input('classification', null);
        $team = $request->input('team', null);
        $home = $request->input('home', null);
        $away = $request->input('away', null);
        $conference = $request->input('conference', null);
        $id = $request->input('id', null);

        $query = array_filter([
            'year' => $year,
            'week' => $week,
            'seasonType' => $seasonType,
            'classification' => $classification,
            'team' => $team,
            'home' => $home,
            'away' => $away,
            'conference' => $conference,
            'id' => $id,
        ]);

        try {
            $response = $this->client->request('GET', 'games', [
                'query' => $query,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch season calendar for a specific year.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCalendar(Request $request)
    {
        $year = $request->input('year');

        try {
            $response = $this->client->request('GET', 'calendar', [
                'query' => [
                    'year' => $year,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch game media information for a specific year and week.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGameMedia(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $seasonType = $request->input('seasonType');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $mediaType = $request->input('mediaType');
        $classification = $request->input('classification');

        try {
            $response = $this->client->request('GET', 'games/media', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'seasonType' => $seasonType,
                    'team' => $team,
                    'conference' => $conference,
                    'mediaType' => $mediaType,
                    'classification' => $classification,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch game weather information for a specific game or year and week.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGameWeather(Request $request)
    {
        $gameId = $request->input('gameId');
        $year = $request->input('year');
        $week = $request->input('week');
        $seasonType = $request->input('seasonType');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $classification = $request->input('classification');

        try {
            $response = $this->client->request('GET', 'games/weather', [
                'query' => [
                    'gameId' => $gameId,
                    'year' => $year,
                    'week' => $week,
                    'seasonType' => $seasonType,
                    'team' => $team,
                    'conference' => $conference,
                    'classification' => $classification,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPlayerGameStats(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'games/players', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch team game stats.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamGameStats(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'games/teams', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch advanced box score.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdvancedBoxScore(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'game/box/advanced', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch drive data and results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDrives(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'drives', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch play by play data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlays(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'plays', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch live play by play data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLivePlays(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'live/plays', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch play types.
     *
     * @return JsonResponse
     */
    public function getPlayTypes()
    {
        try {
            $response = $this->client->request('GET', 'play/types', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch play stats by play.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayStats(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'play/stats', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch conferences.
     *
     * @return JsonResponse
     */
    public function getConferences()
    {
        try {
            $response = $this->client->request('GET', 'conferences', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch teams information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeams(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'teams', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch FBS teams.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFbsTeams(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'teams/fbs', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch team rosters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRoster(Request $request)
    {
        try {
            $response = $this->client->request('GET', 'roster', [
                'query' => $request->all(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTalent(Request $request)
    {
        $year = $request->input('year');

        try {
            $response = $this->client->request('GET', 'talent', [
                'query' => [
                    'year' => $year,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch team matchup history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamMatchup(Request $request)
    {
        $team1 = $request->input('team1');
        $team2 = $request->input('team2');
        $minYear = $request->input('minYear');
        $maxYear = $request->input('maxYear');

        try {
            $response = $this->client->request('GET', 'teams/matchup', [
                'query' => [
                    'team1' => $team1,
                    'team2' => $team2,
                    'minYear' => $minYear,
                    'maxYear' => $maxYear,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch arena and venue information.
     *
     * @return JsonResponse
     */
    public function getVenues()
    {
        try {
            $response = $this->client->request('GET', 'venues', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch coaching records and history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCoaches(Request $request)
    {
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $team = $request->input('team');
        $year = $request->input('year');
        $minYear = $request->input('minYear');
        $maxYear = $request->input('maxYear');

        try {
            $response = $this->client->request('GET', 'coaches', [
                'query' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'team' => $team,
                    'year' => $year,
                    'minYear' => $minYear,
                    'maxYear' => $maxYear,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch historical polls and rankings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRankings(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $seasonType = $request->input('seasonType', 'regular');

        try {
            $response = $this->client->request('GET', 'rankings', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'seasonType' => $seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch betting lines.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLines(Request $request)
    {
        $gameId = $request->input('gameId');
        $year = $request->input('year');
        $week = $request->input('week');
        $seasonType = $request->input('seasonType', 'regular');
        $team = $request->input('team');
        $home = $request->input('home');
        $away = $request->input('away');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'lines', [
                'query' => [
                    'gameId' => $gameId,
                    'year' => $year,
                    'week' => $week,
                    'seasonType' => $seasonType,
                    'team' => $team,
                    'home' => $home,
                    'away' => $away,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSPRatings(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');

        try {
            $response = $this->client->request('GET', 'ratings/sp', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch historical SRS ratings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSRSRatings(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'ratings/srs', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch historical SP+ ratings by conference.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConferenceSPRatings(Request $request)
    {
        $year = $request->input('year');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'ratings/sp/conferences', [
                'query' => [
                    'year' => $year,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch historical Elo ratings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEloRatings(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $seasonType = $request->input('seasonType');
        $team = $request->input('team');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'ratings/elo', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'seasonType' => $seasonType,
                    'team' => $team,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch historical FPI ratings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFPIRatings(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'ratings/fpi', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch predicted points (EP).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPredictedPoints(Request $request)
    {
        $down = $request->input('down');
        $distance = $request->input('distance');

        try {
            $response = $this->client->request('GET', 'ppa/predicted', [
                'query' => [
                    'down' => $down,
                    'distance' => $distance,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Predicted Points Added (PPA/EPA) data by team.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamPPA(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $excludeGarbageTime = $request->input('excludeGarbageTime');

        try {
            $response = $this->client->request('GET', 'ppa/teams', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                    'excludeGarbageTime' => $excludeGarbageTime,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Predicted Points Added (PPA/EPA) by game.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGamePPA(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $excludeGarbageTime = $request->input('excludeGarbageTime');
        $seasonType = $request->input('seasonType', 'regular');

        try {
            $response = $this->client->request('GET', 'ppa/games', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'team' => $team,
                    'conference' => $conference,
                    'excludeGarbageTime' => $excludeGarbageTime,
                    'seasonType' => $seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Player Predicted Points Added (PPA/EPA) by game.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerGamePPA(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $team = $request->input('team');
        $position = $request->input('position');
        $playerId = $request->input('playerId');
        $threshold = $request->input('threshold');
        $excludeGarbageTime = $request->input('excludeGarbageTime');
        $seasonType = $request->input('seasonType', 'regular');

        try {
            $response = $this->client->request('GET', 'ppa/players/games', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'team' => $team,
                    'position' => $position,
                    'playerId' => $playerId,
                    'threshold' => $threshold,
                    'excludeGarbageTime' => $excludeGarbageTime,
                    'seasonType' => $seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch Player Predicted Points Added (PPA/EPA) by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerSeasonPPA(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $position = $request->input('position');
        $playerId = $request->input('playerId');
        $threshold = $request->input('threshold');
        $excludeGarbageTime = $request->input('excludeGarbageTime');

        try {
            $response = $this->client->request('GET', 'ppa/players/season', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                    'position' => $position,
                    'playerId' => $playerId,
                    'threshold' => $threshold,
                    'excludeGarbageTime' => $excludeGarbageTime,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getFGEP()
    {
        try {
            $response = $this->client->request('GET', 'metrics/fg/ep', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch win probability data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWinProbabilityData(Request $request)
    {
        $gameId = $request->input('gameId');

        try {
            $response = $this->client->request('GET', 'metrics/wp', [
                'query' => [
                    'gameId' => $gameId,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch pregame win probabilities.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPregameWinProbabilities(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $team = $request->input('team');
        $seasonType = $request->input('seasonType');

        try {
            $response = $this->client->request('GET', 'metrics/wp/pregame', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'team' => $team,
                    'seasonType' => $seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch team statistics by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamSeasonStats(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $startWeek = $request->input('startWeek');
        $endWeek = $request->input('endWeek');

        try {
            $response = $this->client->request('GET', 'stats/season', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch advanced team metrics by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdvancedTeamSeasonStats(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $excludeGarbageTime = $request->input('excludeGarbageTime');
        $startWeek = $request->input('startWeek');
        $endWeek = $request->input('endWeek');

        try {
            $response = $this->client->request('GET', 'stats/season/advanced', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'excludeGarbageTime' => $excludeGarbageTime,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch advanced team metrics by game.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdvancedTeamGameStats(Request $request)
    {
        $year = $request->input('year');
        $week = $request->input('week');
        $team = $request->input('team');
        $opponent = $request->input('opponent');
        $excludeGarbageTime = $request->input('excludeGarbageTime');
        $seasonType = $request->input('seasonType');

        try {
            $response = $this->client->request('GET', 'stats/game/advanced', [
                'query' => [
                    'year' => $year,
                    'week' => $week,
                    'team' => $team,
                    'opponent' => $opponent,
                    'excludeGarbageTime' => $excludeGarbageTime,
                    'seasonType' => $seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch team stat categories.
     *
     * @return JsonResponse
     */
    public function getStatCategories()
    {
        try {
            $response = $this->client->request('GET', 'stats/categories', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search for player information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function playerSearch(Request $request)
    {
        $searchTerm = $request->input('searchTerm');
        $position = $request->input('position');
        $team = $request->input('team');
        $year = $request->input('year');

        try {
            $response = $this->client->request('GET', 'player/search', [
                'query' => [
                    'searchTerm' => $searchTerm,
                    'position' => $position,
                    'team' => $team,
                    'year' => $year,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch player usage metrics by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerUsage(Request $request)
    {
        $year = $request->input('year', 2022);
        $team = $request->input('team');
        $conference = $request->input('conference');
        $position = $request->input('position');
        $playerId = $request->input('playerId');
        $excludeGarbageTime = $request->input('excludeGarbageTime');

        try {
            $response = $this->client->request('GET', 'player/usage', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                    'position' => $position,
                    'playerId' => $playerId,
                    'excludeGarbageTime' => $excludeGarbageTime,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch returning production metrics by team.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getReturningProduction(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');

        try {
            $response = $this->client->request('GET', 'player/returning', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch player stats by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerSeasonStats(Request $request)
    {
        $year = $request->input('year');
        $team = $request->input('team');
        $conference = $request->input('conference');
        $startWeek = $request->input('startWeek');
        $endWeek = $request->input('endWeek');
        $seasonType = $request->input('seasonType');
        $category = $request->input('category');

        try {
            $response = $this->client->request('GET', 'stats/player/season', [
                'query' => [
                    'year' => $year,
                    'team' => $team,
                    'conference' => $conference,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                    'seasonType' => $seasonType,
                    'category' => $category,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch transfer portal data by season.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransferPortal(Request $request)
    {
        $year = $request->input('year');

        try {
            $response = $this->client->request('GET', 'player/portal', [
                'query' => [
                    'year' => $year,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

// Add additional methods for other endpoints as needed
}
