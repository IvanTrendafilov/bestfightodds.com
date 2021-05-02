<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use BFO\General\EventHandler;
use BFO\General\BookieHandler;
use BFO\General\TeamHandler;
use BFO\General\OddsHandler;
use BFO\General\GraphHandler;
use BFO\General\StatsHandler;

use BFO\Caching\CacheControl;

class MainController
{
    private $plates;

    // constructor receives container instance
    public function __construct(\League\Plates\Engine $plates)
    {
        $this->plates = $plates;
    }

    public function __invoke(Request $request, Response $response)
    {
        return $response;
    }

    public function home(Request $request, Response $response)
    {
        //Retrieve pre-generated page
        $cached_contents = @file_get_contents(PARSE_PAGEDIR . 'oddspage.php');
        if ($cached_contents == null) {
            //Display maintenance page
            $response->getBody()->write($this->plates->render('maintenance', []));
            return $response;
        }

        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event) {
            //Dynamically replace last change placeholder
            $last_change = EventHandler::getLatestChangeDate($event->getID());
            if ($last_change == null) {
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_date%', 'n/a', $cached_contents);
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_diff%', 'n/a', $cached_contents);
            } else {
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_date%', date('M jS Y H:i', strtotime($last_change)) . ' UTC', $cached_contents);
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_diff%', $this->viewEventgetTimeDifference(strtotime($last_change), strtotime(GENERAL_TIMEZONE . ' hours')), $cached_contents);
            }
        }

        //Perform dynamic modifications to the content
        $cached_contents = preg_replace_callback('/changedate-([^\"]*)/', function ($matches) {
            $hour_diff = intval(floor((time() - strtotime($matches[1])) / 3600));
            if ($hour_diff >= 72) {
                return 'arage-3';
            } elseif ($hour_diff >= 24) {
                return 'arage-2';
            } else {
                return 'arage-1';
            }
        }, $cached_contents);

        $response->getBody()->write($this->plates->render('home', ['contents' => $cached_contents]));
        return $response;
    }

    public function terms(Request $request, Response $response)
    {
        $response->getBody()->write($this->plates->render('terms', []));
        return $response;
    }

    public function widget(Request $request, Response $response)
    {
        $view_data = [];
        $view_data['events'] = [];
        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event) {
            $matchups = EventHandler::getAllFightsForEvent($event->getID(), true);
            if (count($matchups) > 0) {
                $view_data['events'][] = ['event_obj' => $event, 'matchups' => $matchups];
            }
        }
        $response->getBody()->write($this->plates->render('widget', $view_data));
        return $response;
    }

    public function archive(Request $request, Response $response)
    {
        $events = EventHandler::getRecentEvents(20, 0);
        $view_data = [
            'recent_events' => $events,
            'event_matchups' => []
        ];
        //PBO Only - TODO: Move this to its own controller?
        foreach ($events as $event) {
            $matchups = EventHandler::getAllFightsForEvent($event->getID(), true);
            $view_data['event_matchups'][$event->getID()] = $matchups;
        }

        $response->getBody()->write($this->plates->render('archive', $view_data));
        return $response;
    }

    public function alerts(Request $request, Response $response)
    {
        $view_data = [];
        $view_events = [];

        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event) {
            $matchups = EventHandler::getAllFightsForEventWithoutOdds($event->getID());
            if (count($matchups) > 0) { //Only add the event if matchups were found
                //If non bellator, ufc or future events we limit to just the main event (first fight)
                if (
                    substr(strtoupper($event->getName()), 0, 3) != 'UFC'
                    && substr(strtoupper($event->getName()), 0, 8) != 'BELLATOR'
                    && substr(strtoupper($event->getName()), 0, 13) != 'FUTURE EVENTS'
                ) {
                    $view_events[] = ['event_obj' => $event, 'matchups' => [$matchups[0]]];
                } else {
                    $view_events[] = ['event_obj' => $event, 'matchups' => $matchups];
                }
            }
        }
        $view_data['events'] = $view_events;
        $view_data['bookies'] = BookieHandler::getAllBookies();

        //Add view data that contains the users email (populated when previously creating an alert and stored in cookie)
        $cookies = $request->getCookieParams();
        $view_data['in_alertmail'] = $cookies['bfo_alertmail'] ?? '';

        $response->getBody()->write($this->plates->render('alerts', $view_data));
        return $response;
    }

    public function search(Request $request, Response $response)
    {
        $view_data = [];

        $search_query = $request->getQueryParams()['query'] ?? '';
        $view_data['search_query'] = $search_query;

        if (strlen($search_query) >= 3) {
            $teams = TeamHandler::searchFighter($search_query);
            $events = EventHandler::searchEvent($search_query);
            if ($teams != null || $events != null) {
                //If we only get one result we will redirect to that page right away
                if ((count($teams) + count($events)) == 1) {
                    if (count($teams) == 1) {
                        return $response
                            ->withHeader('Location', '/fighters/' . $teams[0]->getFighterAsLinkString())
                            ->withStatus(302);
                    } else {
                        return $response
                            ->withHeader('Location', '/events/' . $events[0]->getEventAsLinkString())
                            ->withStatus(302);
                    }
                } elseif (count($teams) + count($events) > 1) {
                    //Reduce teams lists if exceeding 25
                    $view_data['teams_totalsize'] = count($teams);
                    if (count($teams) > 25) {
                        $teams = array_slice($teams, 0, 25);
                    }
                    //Reduce events lists if exceeding 25
                    $view_data['events_totalsize'] = count($events);
                    if (count($events) > 25) {
                        $events = array_slice($events, 0, 25);
                    }
                }
            }
            $view_data['teams_results'] = $teams ?? [];
            $view_data['events_results'] = $events ?? [];
        }

        $response->getBody()->write($this->plates->render('searchresults', $view_data));
        return $response;
    }


    public function viewTeam(Request $request, Response $response, array $args)
    {
        if (!isset($args['id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Get ID from id attribute
        $team_id = substr($args['id'], strrpos($args['id'], '-') + 1);

        if (!intval($team_id)) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $team = TeamHandler::getFighterByID((int) $team_id);

        if ($team == null) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Verify that requested team slug matches the expected one. This is to reduce scrapers trying to autogenerate URLs
        if (strtolower($team->getFighterAsLinkString()) != strtolower($args['id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $view_data = [];

        //Check if page is cached or not. If so, fetch from cache and include
        $last_change = TeamHandler::getLastChangeDate($team->getID());
        if (CacheControl::isPageCached('team-' . $team->getID() . '-' . strtotime($last_change))) {
            //Retrieve cached page
            $view_data['team_title'] = $team->getNameAsString();
            $view_data['meta_desc'] = $team->getNameAsString() . ' betting odds history.';
            $view_data['meta_keywords'] = $team->getNameAsString();
            $view_data['contents'] = CacheControl::getCachedPage('team-' . $team->getID() . '-' . strtotime($last_change));
            $response->getBody()->write($this->plates->render('team', $view_data));
            return $response;
        }

        $view_data['team'] = $team;
        $view_data['matchups'] = [];

        $matchups = EventHandler::getAllFightsForFighter($team->getID());
        foreach ($matchups as $matchup) {
            $team_odds = $this->populateTeamOdds($matchup, $team);
            if (!($team_odds['event']->getID() == PARSE_FUTURESEVENT_ID && $team_odds['odds_opening'] == null)) { //Filters out future events with no opening odds
                $view_data['matchups'][] = $this->populateTeamOdds($matchup, $team);
            }
        }

        $page_content = $this->plates->render('gen_teampage', $view_data);

        //Minify
        $page_content = preg_replace('/\>\s+\</m', '><', $page_content);

        $view_data = [];
        $view_data['contents'] = $page_content;
        $view_data['team_title'] = $team->getNameAsString();
        $view_data['meta_desc'] = $team->getNameAsString() . ' betting odds history.';
        $view_data['meta_keywords'] = $team->getNameAsString();

        //Cache page
        CacheControl::cleanPageCacheWC('team-' . $team->getID() . '-*');
        CacheControl::cachePage($page_content, 'team-' . $team->getID() . '-' . strtotime($last_change) . '.php');

        $response->getBody()->write($this->plates->render('team', $view_data));
        return $response;
    }

    private function populateTeamOdds($matchup, $team)
    {
        $view_matchup = [];

        $view_matchup['event'] = EventHandler::getEvent($matchup->getEventID());
        $view_matchup['event_date'] = '';
        if (strtoupper($view_matchup['event']->getID()) != PARSE_FUTURESEVENT_ID) {
            $view_matchup['event_date'] = date('M jS Y', strtotime($view_matchup['event']->getDate()));
        }

        $view_matchup['odds_opening'] = OddsHandler::getOpeningOddsForMatchup($matchup->getID());

        //Determine range for this fight
        $matchup_odds = EventHandler::getAllLatestOddsForFight($matchup->getID());
        $view_matchup['team1_low'] = null;
        $view_matchup['team2_low'] = null;
        $view_matchup['team1_high'] = null;
        $view_matchup['team2_high'] = null;
        foreach ($matchup_odds as $odds) {
            if ($view_matchup['team1_low'] == null || $odds->getFighterOddsAsDecimal(1, true) < $view_matchup['team1_low']->getFighterOddsAsDecimal(1, true)) {
                $view_matchup['team1_low'] = $odds;
            }
            if ($view_matchup['team2_low'] == null || $odds->getFighterOddsAsDecimal(2, true) < $view_matchup['team2_low']->getFighterOddsAsDecimal(2, true)) {
                $view_matchup['team2_low'] = $odds;
            }
            if ($view_matchup['team1_high'] == null || $odds->getFighterOddsAsDecimal(1, true) > $view_matchup['team1_high']->getFighterOddsAsDecimal(1, true)) {
                $view_matchup['team1_high'] = $odds;
            }
            if ($view_matchup['team2_high'] == null || $odds->getFighterOddsAsDecimal(2, true) > $view_matchup['team2_high']->getFighterOddsAsDecimal(2, true)) {
                $view_matchup['team2_high'] = $odds;
            }
        }

        $team_pos = ((int) $matchup->getFighterID(2) == $team->getID()) + 1;
        $view_matchup['team_pos'] = $team_pos;
        $view_matchup['other_pos'] = ($team_pos == 1 ? 2 : 1);
        $latest_index = EventHandler::getCurrentOddsIndex($matchup->getID(), $team_pos);

        //Calculate % change from opening to mean
        $view_matchup['percentage_change'] = 0;
        if ($latest_index != null && $view_matchup['odds_opening'] != null) {
            $view_matchup['percentage_change'] = round((($latest_index->getFighterOddsAsDecimal($team_pos, true) - $view_matchup['odds_opening']->getFighterOddsAsDecimal($team_pos, true)) / $latest_index->getFighterOddsAsDecimal($team_pos, true)) * 100, 1);
        }

        $view_matchup['graph_data'] = GraphHandler::getMedianSparkLine($matchup->getID(), ($matchup->getFighterID(1) == $team->getID() ? 1 : 2));
        $view_matchup['matchup_obj'] = $matchup;
        return $view_matchup;
    }

    public function viewEvent(Request $request, Response $response, array $args)
    {
        if (!isset($args['id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Get ID from id attribute
        $event_id = substr($args['id'], strrpos($args['id'], '-') + 1);
        if (!intval($event_id)) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        $event = EventHandler::getEvent((int) $event_id);

        if ($event == null) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Verify that requested event slug matches the expected one. This is to reduce scrapers trying to autogenerate URLs
        if (strtolower($event->getEventAsLinkString()) != strtolower($args['id'])) {
            //URL does not match, check partial match to ensure we dont break links if an event is renamed from e.g UFC Fight Night 185: Jonas vs. Silva to UFC Fight Night 185: Griffin vs. Jones
            $mark_pos = strpos($event->getName(), ':') != null ? strpos($event->getName(), ':') : strlen($event->getName()); //Find position of ':'
            $shortened_event = strtolower(LinkTools::slugString(substr($event->getName(), 0, $mark_pos)));
            if ($shortened_event == strtolower(substr($args['id'], 0, strlen($shortened_event)))) {
                //Slug matches partially, redirect with 301 to real URL
                return $response->withHeader('Location', '/events/' . $event->getEventAsLinkString())->withStatus(301);
            } else {
                //Slug does not match partially, redirect to main page with a 302
                return $response->withHeader('Location', '/')->withStatus(302);
            }
        }

        //Check if page is cached or not. If so, fetch from cache and include
        $last_change = EventHandler::getLatestChangeDate($event->getID());
        if (CacheControl::isPageCached('event-' . $event->getID() . '-' . strtotime($last_change))) {
            //Retrieve cached page
            $cached_contents = CacheControl::getCachedPage('event-' . $event->getID() . '-' . strtotime($last_change));

            //Dynamically replace last change placeholder
            $last_change = EventHandler::getLatestChangeDate($event->getID());
            if ($last_change == null) {
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_date%', 'n/a', $cached_contents);
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_diff%', 'n/a', $cached_contents);
            } else {
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_date%', date('M jS Y H:i', strtotime($last_change)) . ' UTC', $cached_contents);
                $cached_contents = str_replace('%' . $event->getID() . '_last_change_diff%', $this->viewEventgetTimeDifference(strtotime($last_change), strtotime(GENERAL_TIMEZONE . ' hours')), $cached_contents);
            }

            //Perform dynamic modifications to the content
            $cached_contents = preg_replace_callback('/changedate-([^\"]*)/', function ($matches) {
                $hour_diff = intval(floor((time() - strtotime($matches[1])) / 3600));
                if ($hour_diff >= 72) {
                    return 'arage-3';
                } elseif ($hour_diff >= 24) {
                    return 'arage-2"';
                } else {
                    return 'arage-1"';
                }
            }, $cached_contents);

            $view_data = [];
            $view_data['contents'] = $cached_contents;
            $view_data['team_title'] = $event->getName() . ' Odds & Betting Lines';
            $view_data['meta_desc'] = $event->getName() . ' odds & betting lines.';
            $view_data['meta_keywords'] = $event->getName();

            $response->getBody()->write($this->plates->render('single_event', $view_data));
            return $response;
        }

        $view_data = OddsHandler::getEventViewData($event->getID());
        $view_data['bookies'] = BookieHandler::getAllBookies();

        //Add swing chart data (= change since opening, last 24h, last h)
        $data = [];
        $series_names = ['Change since opening', 'Change in the last 24 hours', 'Change in the last hour'];
        for ($x = 0; $x <= 2; $x++) {
            $swings = StatsHandler::getAllDiffsForEvent($event->getID(), $x);
            $row_data = [];

            foreach ($swings as $swing) {
                if ($swing[2]['swing'] < 0.01 && $swing[2]['swing'] > 0.00) {
                    $swing[2]['swing'] = 0.01;
                }
                if (round($swing[2]['swing'] * 100) != 0) {
                    $row_data[]  = [$swing[0]->getTeamAsString($swing[1]), -round($swing[2]['swing'] * 100)];
                }
            }
            if (count($row_data) == 0) {
                $row_data[] = ['No ' . strtolower($series_names[$x]), null];
            }
            $data[]  = ["name" => $series_names[$x], "data" => $row_data, "visible" => ($x == 0 ? true : false)];
        }
        $view_data['swing_chart_data'] = $data;

        //Add expected outcome data
        //TODO: This should be refactored to use the generic getExpectedOutcomes instead
        $outcomes = StatsHandler::getExpectedOutcomesForEvent($event->getID());
        $row_data = [];
        foreach ($outcomes as $outcome) {
            $labels = [$outcome[0]->getTeamAsString(1), $outcome[0]->getTeamAsString(2)];

            $points = [
                $outcome[1]['team1_dec'],
                $outcome[1]['team1_itd'],
                $outcome[1]['draw'],
                $outcome[1]['team2_itd'],
                $outcome[1]['team2_dec']
            ];
            $row_data[] = [$labels, $points];
        }
        if (count($row_data) == 0) {
            $points = [0, 0, 0, 0, 0];
            $row_data[] = [['N/A', 'N/A'], $points];
        }
        $view_data['expected_outcome_data']  = ["name" => 'Outcomes', "data" => $row_data];

        $view_data['alerts_enabled'] = false;

        $page_content = $this->plates->render('gen_eventpage', $view_data);

        //Minify
        $page_content = preg_replace('/\>\s+\</m', '><', $page_content);

        //Cache page
        CacheControl::cleanPageCacheWC('event-' . $event->getID() . '-*');
        CacheControl::cachePage($page_content, 'event-' . $event->getID() . '-' . strtotime($last_change) . '.php');

        //Dynamically replace last change placeholder
        $last_change = EventHandler::getLatestChangeDate($event->getID());
        if ($last_change == null) {
            $page_content = str_replace('%' . $event->getID() . '_last_change_date%', 'n/a', $page_content);
            $page_content = str_replace('%' . $event->getID() . '_last_change_diff%', 'n/a', $page_content);
        } else {
            $page_content = str_replace('%' . $event->getID() . '_last_change_date%', date('M jS Y H:i', strtotime($last_change)) . ' UTC', $page_content);
            $page_content = str_replace('%' . $event->getID() . '_last_change_diff%', $this->viewEventgetTimeDifference(strtotime($last_change), strtotime(GENERAL_TIMEZONE . ' hours')), $page_content);
        }

        //Perform dynamic modifications to the content
        $page_content = preg_replace_callback('/changedate-([^\"]*)/', function ($matches) {
            $hour_diff = intval(floor((time() - strtotime($matches[1])) / 3600));
            if ($hour_diff >= 72) {
                return 'arage-3';
            } elseif ($hour_diff >= 24) {
                return 'arage-2';
            } else {
                return 'arage-1';
            }
        }, $page_content);

        //Add page title and metadata
        $view_data = [];
        $view_data['contents'] = $page_content;
        $view_data['team_title'] = $event->getName() . ' Odds & Betting Lines';
        $view_data['meta_desc'] = $event->getName() . ' odds & betting lines.';
        $view_data['meta_keywords'] = $event->getName();

        $response->getBody()->write($this->plates->render('single_event', $view_data));
        return $response;
    }

    /**
     *  Used by viewEvent function to get a readable format for difference between two dates
     */
    private function viewEventgetTimeDifference($a_sStart, $a_sEnd)
    {
        if ($a_sStart == '') {
            return 'n/a';
        }

        if ($a_sStart !== -1 && $a_sEnd !== -1) {
            if ($a_sEnd >= $a_sStart) {
                $sRetString = '';

                $diff = $a_sEnd - $a_sStart;
                if ($days = intval(floor($diff / 86400))) {
                    $diff = $diff % 86400;
                }
                if ($hours = intval(floor($diff / 3600))) {
                    $diff = $diff % 3600;
                }
                if ($minutes = intval(floor($diff / 60))) {
                    $diff = $diff % 60;
                }
                if ($days == 0 && $hours == 0 && $minutes == 0) {
                    $minutes = 1;
                }

                if ($days > 0) {
                    if ($days == 1) {
                        $sRetString .= '1 day';
                        if ($hours > 0) {
                            $sRetString .= ' ' . $hours . ' hr';
                        } else {
                            if ($minutes > 0) {
                                $sRetString .= ' ' . $minutes . ' min';
                            }
                        }
                    } else {
                        $sRetString .= $days . ' days';
                    }
                } else {
                    if ($hours > 0) {
                        $sRetString .= $hours . ' hr';
                        if ($minutes > 0) {
                            $sRetString .= ' ' . $minutes . ' min';
                        }
                    } else {
                        if ($minutes == 1) {
                            $sRetString .= '&lt; ';
                        }
                        $sRetString .= $minutes . ' min';
                    }
                }

                $sRetString .= ' ago';

                return $sRetString;
            }
        }
        return '';
    }
}
