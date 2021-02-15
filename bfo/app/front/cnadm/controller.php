<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


require_once 'config/inc.config.php';
require_once 'lib/bfocore/general/class.ScheduleHandler.php';
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.OddsHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';
require_once 'lib/bfocore/general/class.FighterHandler.php';
require_once 'lib/bfocore/general/class.TeamHandler.php';
require_once 'lib/bfocore/general/class.TwitterHandler.php';
require_once 'lib/bfocore/general/class.Alerter.php';

class AdminController
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

        //Get alerts data
        $view_data['alertcount'] = Alerter::getAlertCount();

        //Get run status data
        $view_data['runstatus'] = BookieHandler::getAllRunStatuses();

        //Get unmatched data
        $unmatched_col = EventHandler::getUnmatched(1500);
        $unmatched_groups = [];
        foreach ($unmatched_col as $key => $unmatched)
        {
            $split = explode(' vs ', $unmatched['matchup']);
            $unmatched_col[$key]['view_indata1'] = $split[0];
            $unmatched_col[$key]['view_indata2'] = $split[1];
            if (isset($unmatched['metadata']['event_name']))
            {
                $cut_pos = strpos($unmatched['metadata']['event_name'], " -") != 0 ? strpos($unmatched['metadata']['event_name'], " -") : strlen($unmatched['metadata']['event_name']);
                $unmatched_col[$key]['view_extras']['event_name_reduced'] = substr($unmatched['metadata']['event_name'], 0, $cut_pos);
                
                $event_search = EventHandler::searchEvent($unmatched_col[$key]['view_extras']['event_name_reduced'], true);
                if ($event_search != null)
                {
                    $unmatched_col[$key]['view_extras']['event_match'] = ['id' => $event_search[0]->getID(), 'name' => $event_search[0]->getName(), 'date' => $event_search[0]->getDate()];
                }

                
            }
            if (isset($unmatched['metadata']['gametime']))
            {
                $unmatched_col[$key]['view_extras']['event_date_formatted'] = (new DateTime('@' . $unmatched['metadata']['gametime']))->format('Y-m-d');
            }

            //Add to group (only for matchups)
            if ($unmatched['type'] == 0)
            {
                $unmatched_groups[($unmatched_col[$key]['view_extras']['event_name_reduced'] ?? '') . ':' . ($unmatched_col[$key]['view_extras']['event_date_formatted'] ?? '')][] = $unmatched_col[$key];
            }
            
        }

        $view_data['unmatched'] = $unmatched_col;
        $view_data['unmatched_groups'] = $unmatched_groups;
        
        $bookies = BookieHandler::getAllBookies();
        $view_data['bookies'] = [];
        foreach ($bookies as $bookie)
        {
            $view_data['bookies'][$bookie->getID()] = $bookie->getName();
        }

        $response->getBody()->write($this->plates->render('home', $view_data));
        return $response;
    }

    public function viewManualActions(Request $request, Response $response)
    {
        $actions = ScheduleHandler::getAllManualActions();

        if ($actions != null && count($actions) > 0)
        {
            foreach($actions as $action)
            {
                $action['action_obj'] = json_decode($action['description']);
                switch ((int) $action['type'])
                {
                    case 1:
                        //Create event and matchups
                        break;
                    case 2:
                        //Rename event
                    case 3:
                        //Change date of event
                    case 4:
                        //Delete event
                    case 5:
                        //Create matchup
                        $action['view_extra']['new_event'] = EventHandler::getEvent($action['action_obj']->eventID);
                        break;
                    case 6:
                        //Move matchup
                        $action['view_extra']['matchup'] = EventHandler::getFightByID($action['action_obj']->matchupID);
                        $action['view_extra']['old_event'] = EventHandler::getEvent($oMatchup->getEventID());
                        $action['view_extra']['new_event'] = EventHandler::getEvent($action['action_obj']->eventID);
                    break;
                    case 7:
                        //Delete matchup
                        $action['view_extra']['matchup'] = EventHandler::getFightByID($action['action_obj']->matchupID);
                        //Check if matchup has odds and the indicate that 
                        $action['view_extra']['opening_odds'] = OddsHandler::getOpeningOddsForMatchup($action['action_obj']->matchupID);
        
                        //Check if either fighter has another matchup scheduled and indicate that
                        $matchups1 = EventHandler::getAllFightsForFighter($action['view_extra']['matchup']->getFighterID(1));
                        $action['view_extra']['found1'] = true;
                        foreach ($matchups1 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $action['view_extra']['matchup']->getFighterID(1) || $matchup->getFighterID(2) != $action['view_extra']['matchup']->getFighterID(2)))
                            {
                                $action['view_extra']['found1'] = true;
                            }
                        }
                        $matchups2 = EventHandler::getAllFightsForFighter($action['view_extra']['matchup']->getFighterID(2));
                        $action['view_extra']['found2'] = false;
                        foreach ($matchups2 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $action['view_extra']['matchup']->getFighterID(1) || $matchup->getFighterID(2) != $action['view_extra']['matchup']->getFighterID(2)))
                            {
                                $action['view_extra']['found2'] = true;
                            }
                        }
                        $action['view_extra']['old_event'] = EventHandler::getEvent($action['view_extra']['matchup']->getEventID());
                    break;
                    case 8:
                    //Move matchup to a non-existant event
                    
                        $action['view_extra']['new_event'] = EventHandler::getEventByName($action['action_obj']->eventTitle);
                        foreach ($action['action_obj']->matchupIDs as $sMatchup)
                        {
                            $oMatchup = EventHandler::getFightByID($sMatchup);
                            echo '&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2). '';
                            $newMA = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($action['view_extra']['new_event'] != null ? $action['view_extra']['new_event']->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
                            echo '</td><td><input type="submit" value="Accept" ' .  ($action['view_extra']['new_event'] == null ? ' disabled=true ' : '') . ' onclick="maMoveMatchup(' . $action['id'] . ', \'' . htmlspecialchars($newMA). '\')" /><br/>
        ';					
                        }
        
                    break;
                    default:
                        echo $action['description'];
                }
        
                 echo '</td></tr>';
            }
        }
        

        return $response;
    }

    public function createMatchup(Request $request, Response $response)
    {
        $view_data = [];
        $view_data['inteam1'] = $request->getQueryParams()['inteam1'] ?? '';
        $view_data['inteam2'] = $request->getQueryParams()['inteam2'] ?? '';
        $view_data['events'] = EventHandler::getAllUpcomingEvents();
        
        $response->getBody()->write($this->plates->render('matchup_new', $view_data));
        return $response;
    }

    public function eventsOverview(Request $request, Response $response, array $args)
    {
        $view_data = ['events' => []];
        $events = null;
        if (isset($args['show']) && $args['show'] == 'all')
        {
            $events = EventHandler::getAllEvents();
        }
        else if (isset($args['show']) && is_numeric($args['show']))
        {
            return $this->showEvent($request, $response, $args);    
        }
        else
        {
            $events = EventHandler::getAllUpcomingEvents();
        }

        foreach ($events as $event)
        {
            $fights = EventHandler::getAllFightsForEvent($event->getID(), false);
            $event_view = [];
            foreach ($fights as $fight)
            {
                $arbitrage_info = Alerter::getArbitrageInfo($fight->getID(), 100);
                $fight_view = ['arbitrage_info' => $arbitrage_info];

                $event_view[] = ['fight_obj' => $fight, 'arbitrage_info' => $arbitrage_info];
            }
            $view_data['events'][] = ['event_obj' => $event, 'fights' => $event_view];
        }

        $response->getBody()->write($this->plates->render('events', $view_data));
        return $response;
    }

    public function showEvent(Request $request, Response $response, array $args)
    {
        $events[] = EventHandler::getEvent($args['show']);
        $view_data = ['events' => []];
        foreach ($events as $event)
        {
            $fights = EventHandler::getAllFightsForEvent($event->getID(), false);
            $event_view = [];
            foreach ($fights as $fight)
            {
                $arbitrage_info = Alerter::getArbitrageInfo($fight->getID(), 100);
                $fight_view = ['arbitrage_info' => $arbitrage_info];

                $event_view[] = ['fight_obj' => $fight, 'arbitrage_info' => $arbitrage_info];
            }
            $view_data['events'][] = ['event_obj' => $event, 'fights' => $event_view];
        }
        $response->getBody()->write($this->plates->render('event_detailed', $view_data));
        return $response;
    }

    public function viewFighter(Request $request, Response $response, array $args)
    {
        if (isset($args['id']))
        {
            $fighter = FighterHandler::getFighterByID($args['id']);
            $twitter_handle = TwitterHandler::getTwitterHandle($args['id']);
            $alt_names = TeamHandler::getAltNamesForTeamByID($args['id']);
            $view_data = ['fighter_obj' => $fighter, 'twitter_handle' => $twitter_handle, 'altnames' => $alt_names ?? []];
            $response->getBody()->write($this->plates->render('fighters', $view_data));
            return $response;
        }

        return $response;
    }

    public function addOddsManually(Request $request, Response $response)
    {
        return $response;
    }

    public function clearOddsForMatchupAndBookie(Request $request, Response $response)
    {
        return $response;
    }

    public function addNewPropTemplate(Request $request, Response $response)
    {
        $view_data = [];

        $view_data['in_template'] = $request->getQueryParams()['in_template'] ?? '';
        $view_data['in_negtemplate'] = $request->getQueryParams()['in_negtemplate'] ?? '';
        $view_data['in_bookie_id'] = $request->getQueryParams()['in_bookie_id'] ?? '';

        $view_data['bookies'] = BookieHandler::getAllBookies();
        $view_data['prop_types'] = OddsHandler::getAllPropTypes();
        $response->getBody()->write($this->plates->render('proptemplate_new', $view_data));
        return $response;
    }

    public function viewPropTemplates(Request $request, Response $response)
    {
        $view_data = ['bookies' => []];
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie)
        {
            $templates = BookieHandler::getPropTemplatesForBookie($bookie->getID());
            $view_data['bookies'][] = ['bookie' => $bookie, 'templates' => $templates];
        }
        $response->getBody()->write($this->plates->render('proptemplates', $view_data));
        return $response;
    }

    public function testMail(Request $request, Response $response)
    {
        return $response;
    }

    public function viewLatestLog(Request $request, Response $response, array $args)
    {
        $view_data = [];
        if (isset($args['logfile']))
        {
            $logfile = $args['logfile'] == 'latest' ? scandir(PARSE_LOGDIR, SCANDIR_SORT_DESCENDING)[0] : $args['logfile'];
            $log_contents =  file_get_contents(PARSE_LOGDIR . '/' . $logfile);
            $view_data = ['log_contents' => $log_contents];
        }
        else
        {
            //List all available log files
            $logdir = opendir(PARSE_LOGDIR);
            $files = [];
            while ($file = readdir($logdir))
            {
                if (substr($file, 0, 1) != ".")
                {
                    $files[] = $file;
                }
            }
            sort($files);
            $view_data['logs'] = $files;
        }

        $response->getBody()->write($this->plates->render('logs', $view_data));
        return $response;
    }

    public function viewAlerts(Request $request, Response $response, array $args)
    {
        $view_data['alerts'] = [];
        $alerts = Alerter::getAllAlerts();
        foreach ($alerts as $alert)
        {
            $fight = EventHandler::getFightByID($alert->getFightID());
            $view_data['alerts'][] = ['alert_obj' => $alert, 'fight_obj' => $fight];
        }
        $response->getBody()->write($this->plates->render('alerts', $view_data));
        return $response;
    }

    public function viewMatchup(Request $request, Response $response, array $args)
    {
        $args['id'];

        $view_data = [];
        $view_data['matchup'] = EventHandler::getFightByID($args['id']);
        $view_data['events'] = EventHandler::getAllUpcomingEvents();
        $response->getBody()->write($this->plates->render('matchup', $view_data));
        return $response;
    }

    public function createPropCorrelation(Request $request, Response $response, array $args)
    {

        $view_data['input_prop'] = $request->getQueryParams()['input_prop'] ?? '';
        $view_data['bookie_id'] = $request->getQueryParams()['bookie_id'] ?? '';

        /*$aSplit = explode(' vs ', $request->getQueryParams()['input_prop']);
        $oProp = new ParsedProp($aSplit[0], $aSplit[1], '123', '-123');
        //$oPP = new PropParserV2();
        $oTemplate = $oPP->matchParsedPropToTemplate($_GET['inBookieID'], $oProp);
        if ($oTemplate != null)
        {
            $aMatchup = $oPP->matchParsedPropToMatchup($oProp, $oTemplate);
            if ($aMatchup['matchup'] != null)
            {
                $iMatchedMatchup = $aMatchup['matchup'];
                echo 'Prematched: '  . $iMatchedMatchup .  ' <br/>';
            }
            $_GET['inCorrelation'] = $oProp->getMainProp() == 1 ? $oProp->getTeamName(1) : $oProp->getTeamName(2);
        }*/

        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event)
        {
            $matchups = EventHandler::getAllFightsForEvent($event->getID(), false);
            $event_view = [];
            foreach ($matchups as $matchup)
            {
                $event_view[] = ['matchup_obj' => $matchup];
            }
            $view_data['events'][] = ['event_obj' => $event, 'matchups' => $event_view];
        }
        $response->getBody()->write($this->plates->render('propcorrelation', $view_data));
        return $response;
    }
   
    public function resetChangeNums(Request $request, Response $response, array $args)
    {
        $view_data = [];
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie)
        {
            $parsers = BookieHandler::getParsers($bookie->getID());
            foreach ($parsers as $parser)
            {
                if ($parser->hasChangenumInUse())
                {
                    //Note: We are currently clearing changenums for all parsers at a specific bookie. Maybe extend this to be able to reset for a single parser instead?
                    $view_data['bookies'][$bookie->getID()] = $bookie->getName();
                }
            }
        }
        $response->getBody()->write($this->plates->render('resetchangenums', $view_data));
        return $response;
    }

    public function viewUnmatched(Request $request, Response $response)
    {
        $view_data = [];

        $unmatched_col = EventHandler::getUnmatched(1500);
        foreach ($unmatched_col as $key => $unmatched)
        {

            $split = explode(' vs ', $unmatched['matchup']);
            $unmatched_col[$key]['view_indata1'] = $split[0];
            $unmatched_col[$key]['view_indata2'] = $split[1];
            if (isset($aUnmatched['metadata']['event_name']))
            {
                $event_search = EventHandler::searchEvent($event_name, true);
            }
            
        }
        $view_data['unmatched'] = $unmatched_col;
        
        $bookies = BookieHandler::getAllBookies();
        $view_data['bookies'] = [];
        foreach ($bookies as $bookie)
        {
            $view_data['bookies'][$bookie->getID()] = $bookie->getName();
        }
        $response->getBody()->write($this->plates->render('viewunmatched', $view_data));
        return $response;
    }

    public function oddsOverview(Request $request, Response $response)
    {
        $view_data = [];
        $events = EventHandler::getAllUpcomingEvents();

        $bookies = BookieHandler::getAllBookies();
        $view_data['bookies'] = [];
        foreach ($bookies as $bookie)
        {
            $view_data['bookies'][$bookie->getID()] = $bookie->getName();
        }

        foreach ($events as $event)
        {
            $matchups = EventHandler::getAllFightsForEvent($event->getID());
            $matchup_view = [];
            foreach ($matchups as $matchup)
            {
                $odds = EventHandler::getAllLatestOddsForFight($matchup->getID());
                $odds_view = [];
                foreach ($odds as $odds_part)
                {
                    $flagged = OddsHandler::checkIfFlagged($odds_part->getBookieID(), $odds_part->getFightID(), -1, -1, -1);
                    $odds_view[$odds_part->getBookieID()] = ['odds_obj' => $odds_part, 'flagged' => $flagged]; 
                }
                $matchup_view[] = ['matchup_obj' => $matchup, 'odds' => $odds_view];
            }
            $view_data['events'][] = ['event_obj' => $event, 'matchups' => $matchup_view];
        }


        $response->getBody()->write($this->plates->render('odds', $view_data));
        return $response;
    }



}