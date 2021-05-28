<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DI\Container;

use BFO\General\ScheduleHandler;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\General\BookieHandler;
use BFO\General\TeamHandler;
use BFO\General\TwitterHandler;
use BFO\General\Alerter;

class AdminController
{
    private $plates;

    // constructor receives container instance
    public function __construct(\League\Plates\Engine $plates)
    {
        $this->plates = $plates;

        //Get run status data
        $plates->addData(['runstatus' => BookieHandler::getAllRunStatuses()]);
    }

    public function __invoke(Request $request, Response $response)
    {
        return $response;
    }

    public function loginPage(Request $request, Response $response)
    {
        $response->getBody()->write($this->plates->render('login'));
        return $response;
    }

    public function login(Request $request, Response $response)
    {
        $input = $request->getParsedBody();
        if (
            isset($input['nm']) && $input['nm'] == GENERAL_CNADM_LOGIN
            && isset($input['pwd']) && $input['pwd'] == GENERAL_CNADM_PWD
        ) {
            $_SESSION['authenticated'] = true;
            return $response->withHeader('Location', '/cnadm/')->withStatus(302);
        }
        return $response->withHeader('Location', '/cnadm/lin')->withStatus(302);
    }

    public function logout(Request $request, Response $response)
    {
        $_SESSION['authenticated'] = false;
        unset($_SESSION['authenticated']);
        session_destroy();
        return $response->withHeader('Location', '/cnadm/lin')->withStatus(302);
    }

    public function home(Request $request, Response $response)
    {
        $view_data = [];

        //Get status on whether or not bookie has finished parsing in the last 5 minutes
        $view_data['lastfinishes'] = $this->getLastFinishDates();

        //Get status on whether or not OddsJob has finished in the last 5 minutes
        $view_data['oddsjob_finished'] = $this->oddsJobFinished();

        //Get alerts data
        $view_data['alertcount'] = Alerter::getAlertCount();

        //Get unmatched data
        $unmatched_col = EventHandler::getUnmatched(1500);

        //Old approach:
        $unmatched_groups = [];
        foreach ($unmatched_col as $key => $unmatched) {
            if (isset($unmatched['metadata']['gametime'])) {
                $unmatched_col[$key]['view_extras']['event_date_formatted'] = (new DateTime('@' . $unmatched['metadata']['gametime']))->format('Y-m-d');
            }

            $split = explode(' vs ', $unmatched['matchup']);
            $unmatched_col[$key]['view_indata1'] = $split[0] == '' ? $split[1] : $split[0]; //Swap places if first part is blank
            $unmatched_col[$key]['view_indata2'] = $split[0] == '' ? $split[0] : $split[1];
            if (isset($unmatched['metadata']['event_name'])) {
                $cut_pos = strpos($unmatched['metadata']['event_name'], " -") != 0 ? strpos($unmatched['metadata']['event_name'], " -") : strlen($unmatched['metadata']['event_name']);
                $unmatched_col[$key]['view_extras']['event_name_reduced'] = substr($unmatched['metadata']['event_name'], 0, $cut_pos);

                $event_search = EventHandler::searchEvent($unmatched_col[$key]['view_extras']['event_name_reduced'], true);
                if ($event_search != null) {
                    $matched_event = $event_search[0];
                    if (count($event_search) > 1) {
                        //Multiple matches, match on date if possible
                        foreach ($event_search as $item) {
                            if ($item->getDate() == $unmatched_col[$key]['view_extras']['event_date_formatted']) {
                                $matched_event = $item;
                            }
                        }
                    }
                    $unmatched_col[$key]['view_extras']['event_match'] = ['id' => $matched_event->getID(), 'name' => $matched_event->getName(), 'date' => $matched_event->getDate()];
                }
            }

            if ($unmatched['type'] == 0) {
                $unmatched_groups[($unmatched_col[$key]['view_extras']['event_name_reduced'] ?? '') . ':' . ($unmatched_col[$key]['view_extras']['event_date_formatted'] ?? '')][] = $unmatched_col[$key];
            }
        }

        //New approach:
        $groups = [];
        $event_groups = [];
        foreach ($unmatched_col as $unmatched) {
            if ($unmatched['type'] == 0) {

                //First check if this can be grouped to an existing key using similarity
                $key = $unmatched['matchup'];
                foreach ($groups as $search_key => $value) {
                    similar_text($search_key, $unmatched['matchup'], $fSim);

                    if ($fSim > 87) {
                        //Matches existing, add to this one
                        $key = $search_key;

                        //TODO: Search for existing fighters to get the best match for name
                    }
                }
                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                }
                if (!isset($groups[$key]['dates'])) {
                    $groups[$key]['dates'] = [];
                }

                $date = 'UNKNOWN';
                if (@isset($unmatched['metadata']['gametime'])) {
                    $date = (new DateTime('@' . $unmatched['metadata']['gametime']))->format('Y-m-d');
                }

                if (!isset($groups[$key]['dates'][$date]['unmatched'])) {
                    $groups[$key]['dates'][$date]['unmatched'] = [];
                }
                $groups[$key]['dates'][$date]['unmatched'][] = $unmatched;

                //Match events
                if (isset($unmatched['metadata']['event_name'])) {
                    if (!isset($groups[$key]['dates'][$date]['parsed_events'])) {
                        $groups[$key]['dates'][$date]['parsed_events'] = [];
                    }
                    if (!isset($groups[$key]['dates'][$date]['matched_events'])) {
                        $groups[$key]['dates'][$date]['matched_events'] = [];
                    }

                    //Reduce event name
                    $cut_pos = strpos($unmatched['metadata']['event_name'], " -") != 0 ? strpos($unmatched['metadata']['event_name'], " -") : strlen($unmatched['metadata']['event_name']);
                    $reduced_name = substr($unmatched['metadata']['event_name'], 0, $cut_pos);

                    //If event name is a subset (shorter version) of another event name we'll assume it is same as the longer one
                    $found = false;
                    foreach ($groups[$key]['dates'][$date]['parsed_events'] as $previously_parsed_event) {
                        if (substr($previously_parsed_event, 0, strlen($reduced_name)) == $reduced_name) {
                            $found = true;
                        }
                    }
                    if (!$found) {
                        //Add parsed event as child for each matchup but also assign this matchup to a parent event
                        $groups[$key]['dates'][$date]['parsed_events'][] = $reduced_name;
                        if (!isset($event_groups[$reduced_name])) {
                            $event_groups[$reduced_name] = [];
                        }
                    }

                    $event_search = EventHandler::searchEvent($reduced_name, true);
                    //Match on date for the matched events
                    $matched_event = '';
                    foreach ($event_search as $event) {
                        if ($event->getDate() == $date) {
                            $matched_event = $event;
                        }
                    }
                    if ($matched_event != '') {
                        $groups[$key]['dates'][$date]['matched_events'][] = $matched_event;
                    }
                }
            }
        }

        //Re-index the main key (matchup) based on what name combination is the most frequently occurring
        foreach ($groups as $key_matchup => $group) {
            foreach ($group['dates'] as $key_date => $date_group) {
                $count_arr = [];
                foreach ($date_group['unmatched'] as $unmatched) {
                    $count_arr[] = $unmatched['matchup'];
                }
                $count_arr = array_count_values($count_arr);
                arsort($count_arr);
                if ($key_matchup != array_key_first($count_arr)) {
                    $groups[array_key_first($count_arr)] = $groups[$key_matchup];
                    unset($groups[$key_matchup]);
                }
            }
        }

        //Loop through each group and add exploded matchup names
        foreach ($groups as $key_matchup => $group) {
            $groups[$key_matchup]['teams'] = explode(' vs ', $key_matchup);
        }

        $view_data['unmatched'] = $unmatched_col;
        $view_data['unmatched_groups'] = $unmatched_groups;
        $view_data['unmatched_matchup_groups'] = $groups;

        $bookies = BookieHandler::getAllBookies();
        $view_data['bookies'] = [];
        foreach ($bookies as $bookie) {
            $view_data['bookies'][$bookie->getID()] = $bookie->getName();
        }

        $response->getBody()->write($this->plates->render('home', $view_data));
        return $response;
    }

    public function viewManualActions(Request $request, Response $response)
    {
        $actions = ScheduleHandler::getAllManualActions();

        if ($actions != null && count($actions) > 0) {
            foreach ($actions as $key => &$action) {
                $action['action_obj'] = json_decode($action['description']);
                switch ((int) $action['type']) {
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
                        $action['view_extra']['matchup'] = EventHandler::getMatchup($action['action_obj']->matchupID);
                        $action['view_extra']['old_event'] = EventHandler::getEvent($action['view_extra']['matchup']->getEventID());
                        $action['view_extra']['new_event'] = EventHandler::getEvent($action['action_obj']->eventID);
                        break;
                    case 7:
                        //Delete matchup
                        $action['view_extra']['matchup'] = EventHandler::getMatchup($action['action_obj']->matchupID);
                        if ($action['view_extra']['matchup'] == null) {
                            unset($actions[$key]);
                            break;
                        }

                        //Check if matchup has odds and the indicate that
                        $action['view_extra']['odds'] = OddsHandler::getOpeningOddsForMatchup($action['action_obj']->matchupID);

                        //Check if either fighter has another matchup scheduled and indicate that
                        if ($action['view_extra']['matchup'] != null) {
                            $matchups1 = EventHandler::getMatchups(team_id: $action['view_extra']['matchup']->getFighterID(1));
                            $action['view_extra']['found1'] = false;
                            foreach ($matchups1 as $matchup) {
                                if ($matchup->isFuture() && ($matchup->getFighterID(1) != $action['view_extra']['matchup']->getFighterID(1) || $matchup->getFighterID(2) != $action['view_extra']['matchup']->getFighterID(2))) {
                                    $action['view_extra']['found1'] = true;
                                }
                            }
                            $matchups2 = EventHandler::getMatchups(team_id: $action['view_extra']['matchup']->getFighterID(2));
                            $action['view_extra']['found2'] = false;
                            foreach ($matchups2 as $matchup) {
                                if ($matchup->isFuture() && ($matchup->getFighterID(1) != $action['view_extra']['matchup']->getFighterID(1) || $matchup->getFighterID(2) != $action['view_extra']['matchup']->getFighterID(2))) {
                                    $action['view_extra']['found2'] = true;
                                }
                            }
                            $action['view_extra']['old_event'] = EventHandler::getEvent($action['view_extra']['matchup']->getEventID());
                        }

                        break;
                    case 8:
                        //Move matchup to a non-existant event
                        $action['view_extra']['new_event'] = EventHandler::getEventByName($action['action_obj']->eventTitle);
                        $action['view_extra']['matchups'] = [];
                        foreach ($action['action_obj']->matchupIDs as $sMatchup) {
                            $action['view_extra']['matchups'][$sMatchup] = EventHandler::getMatchup($sMatchup);
                            $action['view_extra']['newma'][$sMatchup] = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($action['view_extra']['new_event'] != null ? $action['view_extra']['new_event']->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
                        }

                        break;
                    default:
                }
            }
        }

        $response->getBody()->write($this->plates->render('manualactions', ['actions' => $actions]));
        return $response;
    }

    public function createMatchup(Request $request, Response $response)
    {
        $view_data = [];
        $view_data['inteam1'] = $request->getQueryParams()['inteam1'] ?? '';
        $view_data['inteam2'] = $request->getQueryParams()['inteam2'] ?? '';
        $view_data['ineventid'] = $request->getQueryParams()['ineventid'] ?? '';

        $view_data['events'] = EventHandler::getEvents(future_events_only: true);

        $response->getBody()->write($this->plates->render('matchup_new', $view_data));
        return $response;
    }

    public function eventsOverview(Request $request, Response $response, array $args)
    {
        $view_data = ['events' => []];

        $view_data['in_event_name'] = $request->getQueryParams()['in_event_name'] ?? '';
        $view_data['in_event_date'] = $request->getQueryParams()['in_event_date'] ?? '';

        $events = null;
        if (isset($args['show']) && $args['show'] == 'all') {
            $events = EventHandler::getEvents();
        } elseif (isset($args['show']) && is_numeric($args['show'])) {
            return $this->showEvent($request, $response, $args);
        } else {
            $events = EventHandler::getEvents(future_events_only: true);
        }

        foreach ($events as $event) {
            $fights = EventHandler::getMatchups(event_id: $event->getID());
            $event_view = [];
            foreach ($fights as $fight) {
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
        foreach ($events as $event) {
            $fights = EventHandler::getMatchups(event_id: $event->getID());
            $event_view = [];
            foreach ($fights as $fight) {
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
        if (isset($args['id'])) {
            $fighter = TeamHandler::getTeams(team_id: $args['id'])[0] ?? null;
            $twitter_handle = TwitterHandler::getTwitterHandle((int) $args['id']);
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

    public function addNewPropType(Request $request, Response $response)
    {
        $view_data = [];
        $response->getBody()->write($this->plates->render('proptype_new', $view_data));
        return $response;
    }

    public function viewPropTemplates(Request $request, Response $response)
    {
        $view_data = ['bookies' => []];
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie) {
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
        if (isset($args['logfile'])) {
            $filenames = glob(GENERAL_KLOGDIR . 'cron.oddsjob.*');
            $logfile = $args['logfile'] == 'latest' ? end($filenames) : $args['logfile'];
            $log_contents =  file_get_contents($logfile);
            $view_data = ['log_contents' => $log_contents];
        } else {
            //List all available log files
            $logdir = opendir(GENERAL_KLOGDIR);
            $files = [];
            while ($file = readdir($logdir)) {
                if (substr($file, 0, 1) != ".") {
                    $files[] = $file;
                }
            }
            sort($files);
            $view_data['logs'] = $files;
        }

        $response->getBody()->write($this->plates->render('logs', $view_data));
        return $response;
    }

    public function viewParserLogs(Request $request, Response $response, array $args)
    {
        $view_data = [];
        if (isset($args['bookie_name'])) {
            $filenames = glob(GENERAL_KLOGDIR . 'cron.' . $args['bookie_name'] . '.*');
            $log_contents =  file_get_contents(end($filenames));
            $view_data = ['log_contents' => $log_contents];
        } else {
            //List all available log files
            $filenames = glob(GENERAL_KLOGDIR . 'cron.*.*');
            $view_data['bookies'] = [];
            foreach ($filenames as $filename) {
                preg_match('/cron\.([a-zA-Z0-9]+)\.[0-9]+/m', $filename, $matches);
                if (isset($matches[0])) {
                    if (!isset($view_data['bookies'][$matches[1]])) {
                        $view_data['bookies'][$matches[1]] = [];
                    }
                    $view_data['bookies'][$matches[1]]['count'] = ($view_data['bookies'][$matches[1]]['count'] ?? 0) + 1;
                }
            }
            //Loop through the latest log for each bookie and create a preview
            foreach ($view_data['bookies'] as $bookie => $values) {
                $filenames = glob(GENERAL_KLOGDIR . 'cron.' . $bookie . '.*');
                $log_contents =  file_get_contents(end($filenames));
                $view_data['bookies'][$bookie]['preview'] = '';
                $str = explode("\n", $log_contents);
                end($str);
                $last_row = prev($str);
                $second_last_row = prev($str);

                $view_data['bookies'][$bookie]['preview'] = $str[0] . "\n...\n" . $second_last_row . "\n" . $last_row;
            }
        }

        $response->getBody()->write($this->plates->render('parserlogs', $view_data));
        return $response;
    }

    public function viewChangeAuditLog(Request $request, Response $response)
    {
        $view_data = [];
        $log_contents =  file_get_contents(GENERAL_KLOGDIR . 'changeaudit.log');
        $view_data = ['log_contents' => $log_contents];
        $response->getBody()->write($this->plates->render('changeauditlog', $view_data));
        return $response;
    }

    public function viewLog(Request $request, Response $response, array $args)
    {
        $view_data = [];
        $log_contents =  file_get_contents(GENERAL_KLOGDIR . $args['log_name'] . '.log');
        $view_data = ['log_contents' => $log_contents];
        $response->getBody()->write($this->plates->render('logs', $view_data));
        return $response;
    }

    public function viewAlerts(Request $request, Response $response, array $args)
    {
        $view_data['alerts'] = [];
        $alerts = Alerter::getAllAlerts();
        foreach ($alerts as $alert) {
            $fight = EventHandler::getMatchup($alert->getFightID());
            $view_data['alerts'][] = ['alert_obj' => $alert, 'fight_obj' => $fight];
        }
        $response->getBody()->write($this->plates->render('alerts', $view_data));
        return $response;
    }

    public function viewMatchup(Request $request, Response $response, array $args)
    {
        $args['id'];

        $view_data = [];
        $view_data['matchup'] = EventHandler::getMatchup($args['id']);
        $view_data['events'] = EventHandler::getEvents(future_events_only: true);
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

        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            $matchups = EventHandler::getMatchups(event_id: $event->getID());
            $event_view = [];
            foreach ($matchups as $matchup) {
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
        $changenums = BookieHandler::getChangeNums();

        $view_data['bookies_changenums'] = [];
        foreach ($bookies as $bookie) {
            foreach ($changenums as $changenum) {
                if ($changenum['bookie_id'] == $bookie->getID()) {
                    $view_data['bookies_changenums'][] = $changenum + ['bookie_name' => $bookie->getName()];
                }
            }
        }

        $response->getBody()->write($this->plates->render('resetchangenums', $view_data));
        return $response;
    }

    public function viewFlaggedOdds(Request $request, Response $response)
    {
        $flagged = OddsHandler::getAllFlaggedMatchups();

        foreach ($flagged as &$flagged_row) {
            $first_date = date_create($flagged_row['initial_flagdate']);
            $last_date = date_create($flagged_row['last_flagdate']);

            $cur_date = new DateTime();

            $date_diff = date_diff($first_date, $cur_date);
            $hours_diff = $date_diff->days * 24 + $date_diff->h;
            $flagged_row['hours_diff'] = $hours_diff;
        }

        //Group into matchup groups
        /*$matchup_groups = [];
        foreach ($flagged as $flagged_row) {
            $key = $flagged_row['fight_obj']->getTeamAsString(1) . ' / ' . $flagged_row['fight_obj']->getTeamAsString(2);
            if (!isset($matchup_groups[$key])) {
                $first_date = date_create($flagged_row['initial_flagdate']);
                $last_date = date_create($flagged_row['last_flagdate']);

                $cur_date = new DateTime();

                $date_diff = date_diff($first_date, $cur_date);
                $hours_diff = $date_diff->days * 24 + $date_diff->h;

                $matchup_groups[$key] = [
                    'fight_obj' => $flagged_row['fight_obj'],
                    'event_obj' => $flagged_row['event_obj'],
                    'bookies' => [$flagged_row['bookie_name']],
                    'initial_flagdate' => $flagged_row['initial_flagdate'],
                    'last_flagdate' => $flagged_row['last_flagdate'],
                    'hours_diff' => $hours_diff
                ];
            } else {
                $matchup_groups[$key]['bookies'][] = $flagged_row['bookie_name'];
            }
        }*/

        $view_data['flagged'] = $flagged;
        $response->getBody()->write($this->plates->render('flagged_odds', $view_data));
        return $response;
    }

    public function viewParserStatus(Request $request, Response $response, array $args)
    {
        //Get run status data
        $view_data['runstatus'] = BookieHandler::getAllRunStatuses();

        $response->getBody()->write($this->plates->render('parser_status', $view_data));
        return $response;
    }

    private function getLastFinishDates()
    {
        $bookie_status = [];

        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie) {
            $has_finished_in_last_5_min = false;
            
            $filenames = glob(GENERAL_KLOGDIR . 'cron.' . str_replace(' ', '', strtolower($bookie->getName())) . '.*');
            $filenames = array_reverse($filenames);
            if (count($filenames) >= 3) {
                for ($i = 0; $i <= 2; $i++) {
                    $log_contents =  file_get_contents($filenames[$i]);
                    $str = explode("\n", $log_contents);
                    end($str);
                    $last_row = prev($str);
                    if (strpos($last_row, 'Finished') !== false) {
                        //Log contains the word Finished
                        $date_regex = '/\[([^\]]*)]/m';
                        $matches = [];
                        preg_match($date_regex, $last_row, $matches);
                        if ($matches) {
                            //Found date, check it
                            $now_date = new DateTime();
                            $log_date = new DateTime($matches[1]);
                            $log_date->add(new DateInterval('PT' . 5 . 'M'));
                            if ($log_date > $now_date) {
                                $has_finished_in_last_5_min = true;
                            }
                        }
                    }
                }
            }
            $bookie_status[$bookie->getName()] = $has_finished_in_last_5_min;
        }
        return $bookie_status;
    }

    private function oddsJobFinished()
    {
        $filenames = glob(GENERAL_KLOGDIR . 'cron.oddsjob.*');
        $filenames = array_reverse($filenames);
        if (count($filenames) >= 3) {
            for ($i = 0; $i <= 2; $i++) {
                $log_contents =  file_get_contents($filenames[$i]);
                $str = explode("\n", $log_contents);
                end($str);
                $last_row = prev($str);
                if (strpos($last_row, 'Finished') !== false) {
                    //Log contains the word Finished
                    $date_regex = '/\[([^\]]*)]/m';
                    $matches = [];
                    preg_match($date_regex, $last_row, $matches);
                    if ($matches) {
                        //Found date, check it
                        $now_date = new DateTime();
                        $log_date = new DateTime($matches[1]);
                        $log_date->add(new DateInterval('PT' . 5 . 'M'));
                        if ($log_date > $now_date) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
