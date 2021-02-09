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

        $response->getBody()->write($this->plates->render('home', $view_data));
        return $response;
    }

    public function viewManualActions(Request $request, Response $response)
    {
        /*
        $aManualActions = ScheduleHandler::getAllManualActions();
        $iCounter = 0;

        if ($aManualActions != null && sizeof($aManualActions) > 0)
        {
            foreach($aManualActions as $aManualAction)
            {
                echo '<tr id="ma' . $aManualAction['id'] . '"><td>';
                $oAction = json_decode($aManualAction['description']);
                switch ((int) $aManualAction['type'])
                {
                    case 1:
                    //Create event and matchups
                        echo 'Create new event: </td><td>' . $oAction->eventTitle . '</td><td> at </td><td>' . $oAction->eventDate . ' with matchups: <br />' ;
                        foreach ($oAction->matchups as $aMatchup)
                        {
                            echo '&nbsp;' . $aMatchup[0] . ' vs ' . $aMatchup[1] . '<br/>';
                        }
                        echo '</td><td><input type="submit" value="Accept" onclick="maAddEventWithMatchups(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 2:
                    //Rename event
                        echo 'Rename event </td><td>' . EventHandler::getEvent($oAction->eventID)->getName() . '</td><td> to </td><td>' . $oAction->eventTitle;
                        echo '</td><td><input type="submit" value="Accept" onclick="maRenameEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 3:
                    //Change date of event
                        $oEvent = EventHandler::getEvent($oAction->eventID);
                        echo 'Change date of </td><td>' . $oEvent->getName() . '</td><td> from </td><td>' . $oEvent->getDate() . '</td><td> to </td><td>' . $oAction->eventDate;
                        echo '</td><td><input type="submit" value="Accept" onclick="maRedateEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 4:
                    //Delete event
                        $oEvent = EventHandler::getEvent($oAction->eventID);
                        echo 'Delete event </td><td>' . $oEvent->getName() . ' - ' . $oEvent->getDate();
                        echo '</td><td><input type="submit" value="Accept" onclick="maDeleteEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 5:
                    //Create matchup
                        $oEvent = EventHandler::getEvent($oAction->eventID);
                        echo 'Create matchup </td><td>' . $oAction->matchups[0]->team1 . ' vs. ' . $oAction->matchups[0]->team2 . '</td><td> at </td><td>' . EventHandler::getEvent($oAction->eventID)->getName();
                        echo '</td><td><input type="submit" value="Accept" onclick="maAddMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 6:
                    //Move matchup
                        $oMatchup = EventHandler::getFightByID($oAction->matchupID);
                        $oOldEvent = EventHandler::getEvent($oMatchup->getEventID());
                        $oNewEvent = EventHandler::getEvent($oAction->eventID);
                        echo 'Move </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a></td><td> from </td><td>' . $oOldEvent->getName() . ' (' . $oOldEvent->getDate() . ')</td><td> to </td><td>' . $oNewEvent->getName() . ' (' . $oNewEvent->getDate() . ')';
                        echo '</td><td><input type="submit" value="Accept" onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 7:
                    //Delete matchup
                        $oMatchup = EventHandler::getFightByID($oAction->matchupID);
                        //Check if matchup has odds and the indicate that 
                        $odds = OddsHandler::getOpeningOddsForMatchup($oAction->matchupID);
        
                        //Check if either fighter has another matchup scheduled and indicate that
                        $matchups1 = EventHandler::getAllFightsForFighter($oMatchup->getFighterID(1));
                        $found1 = false;
                        foreach ($matchups1 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)))
                            {
                                $found1 = true;
                            }
                        }
                        $matchups2 = EventHandler::getAllFightsForFighter($oMatchup->getFighterID(2));
                        $found2 = false;
                        foreach ($matchups2 as $matchup)
                        {
                            if ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)))
                            {
                                $found2 = true;
                            }
                        }
        
                        $oEvent = EventHandler::getEvent($oMatchup->getEventID());
                        echo 'Delete </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a> ' . ($odds == null ? ' (no odds)' : ' (has odds) ') . ' ' . ($found1 == false ? '' : ' (' . $oMatchup->getTeamAsString(1) . ' has other matchup)') . ' ' . ($found2 == false ? '' : ' (' .  $oMatchup->getTeamAsString(2) . ' has other matchup)') . '</td><td> from </td><td>' . $oEvent->getName() . ' (' . $oEvent->getDate() .')';
                        echo '</td><td><input type="submit" value="Accept" onclick="maDeleteMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
                        ';
                    break;
                    case 8:
                    //Move matchup to a non-existant event
                    
                        $oNewEvent = EventHandler::getEventByName($oAction->eventTitle);
                        echo 'Move the following matchups</td><td> to </td><td>' . ($oNewEvent != null ? $oNewEvent->getName() : 'TBD') . '<br />
        ';
                        foreach ($oAction->matchupIDs as $sMatchup)
                        {
                            $oMatchup = EventHandler::getFightByID($sMatchup);
                            echo '&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2). '';
                            $newMA = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($oNewEvent != null ? $oNewEvent->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
                            echo '</td><td><input type="submit" value="Accept" ' .  ($oNewEvent == null ? ' disabled=true ' : '') . ' onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($newMA). '\')" /><br/>
        ';					
                        }
        
                    break;
                    default:
                        echo $aManualAction['description'];
                }
        
                 echo '</td></tr>';
            }
        }
        */

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