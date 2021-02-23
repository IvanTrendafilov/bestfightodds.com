<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once 'config/inc.config.php';
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';
require_once 'lib/bfocore/general/class.FighterHandler.php';
require_once 'lib/bfocore/general/class.OddsHandler.php';
require_once 'lib/bfocore/general/class.GraphHandler.php';
require_once 'lib/bfocore/general/class.TeamHandler.php';
require_once 'lib/bfocore/general/caching/class.CacheControl.php';
require_once 'lib/bfocore/general/class.StatsHandler.php';

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
        $view_data = [];

        $response->getBody()->write($this->plates->render('home', $view_data));
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
        foreach ($events as $event)
        {
            $matchups = EventHandler::getAllFightsForEvent($event->getID(), true);
            if (count($matchups) > 0)
            {
                $view_data['events'][] = ['event_obj' => $event, 'matchups' => $matchups];
            }
        }
        $response->getBody()->write($this->plates->render('widget', $view_data));
        return $response;
    }

    public function archive(Request $request, Response $response)
    {
        $view_data = ['recent_events' => EventHandler::getRecentEvents(20, 0)];
        $response->getBody()->write($this->plates->render('archive', $view_data));
        return $response;
    }

    public function alerts(Request $request, Response $response)
    {
        $view_data = [];
        $view_events = [];

        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event)
        {
            $matchups = EventHandler::getAllFightsForEventWithoutOdds($event->getID());
            if (count($matchups) > 0) //Only add the event if matchups were found
            {
                //If non bellator, ufc or future events we limit to just the main event (first fight)
                if (substr(strtoupper($event->getName()), 0, 3) != 'UFC' 
                    && substr(strtoupper($event->getName()), 0, 8) != 'BELLATOR' 
                    && substr(strtoupper($event->getName()), 0, 13) != 'FUTURE EVENTS')
                {
                    $view_events[] = ['event_obj' => $event, 'matchups' => [$matchups[0]]];
                }
                else
                {
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

        if (strlen($search_query) >= 3)
        {
            $teams = FighterHandler::searchFighter($search_query);
            $events = EventHandler::searchEvent($search_query);
            if ($teams != null || $events != null)
            {
                //If we only get one result we will redirect to that page right away
                if ((count($teams) + count($events)) == 1)
                {
                    if (count($teams) == 1)
                    {
                        return $response
                            ->withHeader('Location', '/fighters/' . $teams[0]->getFighterAsLinkString())
                            ->withStatus(302);
                    }
                    else
                    {
                        return $response
                            ->withHeader('Location', '/events/' . $events[0]->getEventAsLinkString())
                            ->withStatus(302);
                    }
                }
                else if (count($teams) + count($events) > 1)
                {
                    //Reduce teams lists if exceeding 25
                    $view_data['teams_totalsize'] = count($teams);
                    if (count($teams) > 25)
                    {
                        $teams = array_slice($teams, 0, 25);
                    }
                    //Reduce events lists if exceeding 25
                    $view_data['events_totalsize'] = count($events);
                    if (count($events) > 25)
                    {
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
        if (!isset($args['id']))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Get ID from id attribute
        $team_id = substr($args['id'], strrpos($args['id'], '-') + 1);
        
        if (!intval($team_id))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        $team = FighterHandler::getFighterByID((int) $team_id);
        if ($team == null)
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        //Verify that requested team slug matches the expected one. This is to reduce scrapers trying to autogenerate URLs
        if (strtolower($team->getFighterAsLinkString()) != strtolower($args['id']))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Check if page is cached or not. If so, fetch from cache and include
        $last_change = TeamHandler::getLastChangeDate($team->getID());
        if (CacheControl::isPageCached('team-' . $team->getID() . '-' . strtotime($last_change)))
        {
            //Retrieve cached page
            $cached_contents = CacheControl::getCachedPage('team-' . $team->getID() . '-' . strtotime($last_change));
            $response->getBody()->write($cached_contents);
            return $response;
        }
        
        $view_data = [];
        $view_data['team'] = $team;
        $view_data['team_title'] = $team->getNameAsString() . '\'s MMA Odds History';
        $view_data['meta_desc'] = $team->getNameAsString() . ' betting odds history.';
        $view_data['meta_keywords'] = $team->getNameAsString();
        $view_data['matchups'] = [];

        $matchups = EventHandler::getAllFightsForFighter($team->getID());
        foreach ($matchups as $matchup)
        {
            $view_matchup = [];

            $view_matchup['event'] = EventHandler::getEvent($matchup->getEventID());
            $view_matchup['event_date'] = '';
            if (strtoupper($view_matchup['event']->getID()) != PARSE_FUTURESEVENT_ID)
            {
                $view_matchup['event_date'] = date('M jS Y', strtotime($view_matchup['event']->getDate()));
            }

            $view_matchup['odds_opening'] = OddsHandler::getOpeningOddsForMatchup($matchup->getID());

            //Determine range for this fight
            $matchup_odds = EventHandler::getAllLatestOddsForFight($matchup->getID());
            $view_matchup['team1_low'] = null;
            $view_matchup['team2_low'] = null;
            $view_matchup['team1_high'] = null;
            $view_matchup['team2_high'] = null;
            foreach ($matchup_odds as $odds)
            {
                if ($view_matchup['team1_low'] == null || $odds->getFighterOddsAsDecimal(1, true) < $view_matchup['team1_low']->getFighterOddsAsDecimal(1, true))
                {
                    $view_matchup['team1_low'] = $odds;
                }
                if ($view_matchup['team2_low'] == null || $odds->getFighterOddsAsDecimal(2, true) < $view_matchup['team2_low']->getFighterOddsAsDecimal(2, true))
                {
                    $view_matchup['team2_low'] = $odds;
                }
                if ($view_matchup['team1_high'] == null || $odds->getFighterOddsAsDecimal(1, true) > $view_matchup['team1_high']->getFighterOddsAsDecimal(1, true))
                {
                    $view_matchup['team1_high'] = $odds;
                }
                if ($view_matchup['team2_high'] == null || $odds->getFighterOddsAsDecimal(2, true) > $view_matchup['team2_high']->getFighterOddsAsDecimal(2, true))
                {
                    $view_matchup['team2_high'] = $odds;
                }
            }
            
            $team_pos = ((int) $matchup->getFighterID(2) == $team->getID()) + 1;
            $view_matchup['team_pos'] = $team_pos;
            $view_matchup['other_pos'] = ($team_pos == 1 ? 2 : 1);
            $latest_index = EventHandler::getCurrentOddsIndex($matchup->getID(), $team_pos);
            
            //Calculate % change from opening to mean
            $view_matchup['percentage_change'] = 0;
            if ($latest_index != null && $view_matchup['odds_opening'] != null)
            {
                $view_matchup['percentage_change'] = round((($latest_index->getFighterOddsAsDecimal($team_pos, true) - $view_matchup['odds_opening']->getFighterOddsAsDecimal($team_pos, true)) / $latest_index->getFighterOddsAsDecimal($team_pos, true)) * 100, 1);
            }
            
            $view_matchup['graph_data'] = GraphHandler::getMedianSparkLine($matchup->getID(), ($matchup->getFighterID(1) == $team->getID() ? 1 : 2));
            $view_matchup['matchup_obj'] = $matchup;
            $view_data['matchups'][] = $view_matchup;
        }

        $page_content = $this->plates->render('team', $view_data);

        //Cache page
        CacheControl::cleanPageCacheWC('team-' . $team->getID() . '-*');
        CacheControl::cachePage($page_content, 'team-' . $team->getID() . '-' . strtotime($last_change) . '.php');
       
        $response->getBody()->write($page_content);
        return $response;
    }

    public function viewEvent(Request $request, Response $response, array $args)
    {
        if (!isset($args['id']))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Get ID from id attribute
        $event_id = substr($args['id'], strrpos($args['id'], '-') + 1);
        if (!intval($event_id))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        $event = EventHandler::getEvent((int) $event_id);

        if ($event == null)
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        //Verify that requested event slug matches the expected one. This is to reduce scrapers trying to autogenerate URLs
        if (strtolower($event->getEventAsLinkString()) != strtolower($args['id']))
        {
            //URL does not match, check partial match to ensure we dont break links if an event is renamed from e.g UFC Fight Night 185: Jonas vs. Silva to UFC Fight Night 185: Griffin vs. Jones
            $mark_pos = strpos($event->getName(), ':') != null ? strpos($event->getName(), ':') : strlen($event->getName()); //Find position of ':'
            $shortened_event = strtolower(LinkTools::slugString(substr($event->getName(), 0, $mark_pos)));
            if ($shortened_event == strtolower(substr($args['id'], 0, strlen($shortened_event))))
            {
                //Slug matches partially, redirect with 301 to real URL
                //error_log('Incorrect slug URL, correcting with 301: ' . $_SERVER['REQUEST_URI'] . ' - New: /events/' . $event->getEventAsLinkString()); //TODO: Can probably be removed later on when stable
                return $response->withHeader('Location', '/events/' . $event->getEventAsLinkString())->withStatus(301);
            }
            else
            {
                //Slug does not match partially, redirect to main page with a 302
                return $response->withHeader('Location', '/')->withStatus(302);
            }
        }

        //Check if page is cached or not. If so, fetch from cache and include
        $last_change = EventHandler::getLatestChangeDate($event->getID());
        if (CacheControl::isPageCached('event-' . $event->getID() . '-' . strtotime($last_change)))
        {
            //Retrieve cached page
            $cached_contents = CacheControl::getCachedPage('event-' . $event->getID() . '-' . strtotime($last_change));
            $response->getBody()->write($cached_contents);
            return $response;
        }
        

        $bookies = BookieHandler::getAllBookies();
        $matchups = EventHandler::getAllFightsForEvent($event->getID(), true);

        //Convert matchups array to associative
        $matchups_assoc = [];
        foreach ($matchups as $matchup)
        {
            $matchups_assoc[$matchup->getID()] = $matchup;
        }

        $prop_odds = OddsHandler::getLatestPropOddsV2($event->getID());
        $matchup_odds = OddsHandler::getLatestMatchupOddsV2($event->getID());


        //Loop through prop odds and update prop bet descriptions with team names
        foreach ($prop_odds as $prop_odds_entry)
        {

        }

        $view_data = [];

        //Loop through prop odds and count the number of props available for each matchup
        $view_data['matchup_prop_count'] = [];
        foreach ($prop_odds as $event_entry)
        {
            foreach ($event_entry as $matchup_key => $matchup_entry)
            {
                foreach ($matchup_entry as $proptype_entry)
                {
                    foreach ($proptype_entry as $team_num_key => $team_num_entry)
                    {
                        //Count entries per matchup
                        if (!isset($view_data['matchup_prop_count'][$matchup_key]))
                        {
                            $view_data['matchup_prop_count'][$matchup_key] = 0;
                        }
                        $view_data['matchup_prop_count'][$matchup_key]++;

                        foreach ($team_num_entry as $bookie_odds)
                        {
                            //Adjust prop name description
                            $prop_desc = $bookie_odds['odds_obj']->getPropName();
                            $prop_desc = str_replace(['<T>', '<T2>'], 
                                            [$matchups_assoc[$matchup_key]->getTeamLastNameAsString($team_num_key),
                                            $matchups_assoc[$matchup_key]->getTeamLastNameAsString(($team_num_key % 2) + 1)]
                                            , $prop_desc);
                            $prop_desc = $bookie_odds['odds_obj']->setPropName($prop_desc);

                            $prop_desc = $bookie_odds['odds_obj']->getNegPropName();
                            $prop_desc = str_replace(['<T>', '<T2>'], 
                                            [$matchups_assoc[$matchup_key]->getTeamLastNameAsString($team_num_key),
                                            $matchups_assoc[$matchup_key]->getTeamLastNameAsString(($team_num_key % 2) + 1)]
                                            , $prop_desc);
                            $prop_desc = $bookie_odds['odds_obj']->setNegPropName($prop_desc);
                        }

                    }
                }
            }
        }
        
        $view_data['event'] = $event;
        $view_data['bookies'] = $bookies;
        $view_data['matchups'] = $matchups;
        $view_data['prop_odds'] = $prop_odds;
        $view_data['matchup_odds'] = $matchup_odds;


        //Add swing chart data (= change since opening, last 24h, last h)
        $data = [];
        $series_names = ['Change since opening', 'Change in the last 24 hours', 'Change in the last hour'];
        for ($x = 0; $x <= 2; $x++)
        {
            $swings = StatsHandler::getAllDiffsForEvent($event->getID(), $x);
            $row_data = [];
            
            foreach ($swings as $swing)
            {
                if ($swing[2]['swing'] < 0.01 && $swing[2]['swing'] > 0.00)
                {
                    $swing[2]['swing'] = 0.01;
                }
                if (round($swing[2]['swing'] * 100) != 0)
                {
                    
                    $row_data[]  = [$swing[0]->getTeamAsString($swing[1]), -round($swing[2]['swing'] * 100)];
                }
            }
            if (count($row_data) == 0)
            {
                $row_data[] = ['No ' . strtolower($series_names[$x]), null];
            }
            $data[]  = ["name" => $series_names[$x], "data" => $row_data, "visible" => ($x == 0 ? true : false)];
        }
        $view_data['swing_chart_data'] = $data;


        //Add expected outcome data
        //TODO: This should be refactored to use the generic getExpectedOutcomes instead
        $outcomes = StatsHandler::getExpectedOutcomesForEvent($event->getID());
        $row_data = [];
        foreach ($outcomes as $outcome)
        {
            $labels = [$outcome[0]->getTeamAsString(1), $outcome[0]->getTeamAsString(2)];

            $points = [$outcome[1]['team1_dec'],
                        $outcome[1]['team1_itd'],
                        $outcome[1]['draw'],
                        $outcome[1]['team2_itd'],
                        $outcome[1]['team2_dec']];
            $row_data[] = [$labels, $points];

        }
        if (count($row_data) == 0)
        {
            $points = [0,0,0,0,0];
            $row_data[] = [['N/A','N/A'], $points];
        }
        $view_data['expected_outcome_data']  = ["name" => 'Outcomes', "data" => $row_data];

        





        //Add page title and metadata
        $view_data['team_title'] = $event->getName() . ' Odds & Betting Lines';
        $view_data['meta_desc'] = $event->getName() . ' odds & betting lines.';
        $view_data['meta_keywords'] = $event->getName();
        
        $page_content = $this->plates->render('event', $view_data);

        //Cache page
        CacheControl::cleanPageCacheWC('event-' . $event->getID() . '-*');
        CacheControl::cachePage($page_content, 'event-' . $event->getID() . '-' . strtotime($last_change) . '.php');
       
        $response->getBody()->write($page_content);
        return $response;
    }

}