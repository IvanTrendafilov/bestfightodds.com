<?php

require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.ScheduleHandler.php');
require_once('config/class.RuleSet.php');

class MatchupCreator
{
    private $logger = null;
    private $bookie_obj = null;
    private $ruleset = null;
    private $upcoming_matchups = null;
    private $audit_log = null;
    private $manual_actions_create_events = null;
    private $manual_actions_create_matchups = null;

    public function __construct($logger, $bookie_id)
    {
        $this->logger = $logger;
        $this->bookie_obj = BookieHandler::getBookieByID($bookie_id);
        $this->ruleset = new RuleSet();

        $this->audit_log = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        //Prefetch manual actions
        $this->manual_actions_create_events = ScheduleHandler::getAllManualActions(1);
        $this->manual_actions_create_matchups = ScheduleHandler::getAllManualActions(5);
        if ($this->manual_actions_create_events != null) {
            foreach ($this->manual_actions_create_events as &$action) {
                $action['action_obj'] = json_decode($action['description']);
            }
        }
        if ($this->manual_actions_create_matchups != null) {
            foreach ($this->manual_actions_create_matchups as &$action) {
                $action['action_obj'] = json_decode($action['description']);
            }
        }
    }

    public function evaluateMatchup($team1, $team2, $event_name, $matchup_time)
    {
        $event_name = $event_name ?? 'Unknown Event';

        $this->logger->info("Evaluating if " . $team1 . " vs " . $team2 . " at " . $event_name . " on " . $matchup_time . " can be created..");

        //Check if the site-specific creation ruleset approves that this match up is created
        $ruleset_blessing = $this->ruleset->evaluateMatchup($this->bookie_obj, $team1, $team2, $event_name, $matchup_time);

        //Blessing can also come from the scheduler (if matchup is scheduled)
        $scheduler_blessing = $this->matchupProposedBySchedule($team1, $team2);

        if (($ruleset_blessing || $scheduler_blessing['found']) && isset($event_name, $matchup_time)) {
            //Find matching event for this league (e.g. UFC, PFL, Bellator)

            $date_obj = new DateTime();
            $date_obj = $date_obj->setTimestamp($matchup_time);
            $date = $date_obj->format('Y-m-d');

            //Match on date for the matched events
            $matched_event = null;
            if (strtoupper(trim($event_name)) == 'FUTURE EVENTS') {
                $matched_event = EventHandler::getEvent(PARSE_FUTURESEVENT_ID);
            }  else if ($scheduler_blessing['event_id']) { //Scheduler has already matched this to an event
                $matched_event = EventHandler::getEvent($scheduler_blessing['event_id']);
                if ($matched_event == null) {
                    $this->logger->error("Matched event in scheduler is invalid (null). Potential scheduler action with deleted event");
                    return null;
                } 
            }
            else {
                $event_pieces = explode(' ', strtoupper($event_name));
                $event_search = EventHandler::searchEvent($event_pieces[0], true);
                foreach ($event_search as $event) {
                    if ($event->getDate() == $date) {
                        $matched_event = $event;
                    }
                }
            }

            if ($matched_event == null) {
                $matched_event = $this->tryToCreateEvent($event_name, $matchup_time, $date_obj, $scheduler_blessing);
            }

            if ($matched_event != null) {
                //Found an event that matches. Additional check in place is to ensure neither fighter has another matchup already at that particular event
                $found_other_matchup = false;
                if ($matched_event->getID() != PARSE_FUTURESEVENT_ID || $scheduler_blessing['found']) { //Ok to add multiple matchups if event is future events or if also featured in scheduler

                    //Fetch upcoming matchups if not already done
                    if ($this->upcoming_matchups == null) {
                        $this->upcoming_matchups = EventHandler::getAllUpcomingMatchups(true);
                    }

                    foreach ($this->upcoming_matchups as $matchup) {
                        if (
                            OddsTools::compareNames($matchup->getFighter(1), $team1) > 82
                            || OddsTools::compareNames($matchup->getFighter(1), $team2) > 82
                            || OddsTools::compareNames($matchup->getFighter(2), $team1) > 82
                            || OddsTools::compareNames($matchup->getFighter(2), $team2) > 82
                        ) {
                            $found_other_matchup = true;
                            $this->logger->info("- Found other matchup " . $matchup->getFighter(1) . " vs " . $matchup->getFighter(2) . ". Will not create");
                        }
                    }
                }
                if (!$found_other_matchup) {

                    //Check that date is not in the past
                    $current_date = new DateTime();
                    if ($date_obj > $current_date) {

                        $new_matchup = null;
                        if ($scheduler_blessing['found'] && $scheduler_blessing['team1'] != null && $scheduler_blessing['team2'] != null) {
                            //Found in scheduler as well, use names from scheduler instead since they are more accurate
                            $new_matchup = new Fight(0, $scheduler_blessing['team1'], $scheduler_blessing['team2'], $matched_event->getID());
                        } else {
                            $new_matchup = new Fight(0, $team1, $team2, $matched_event->getID());
                        }
                        
                        $id = EventHandler::addNewFight($new_matchup);
                        if ($id == true) {
                            $created_matchup_obj = EventHandler::getFightByID($id);
                            $this->audit_log->info("Created new matchup " . $created_matchup_obj->getFighter(1) . ' vs ' . $created_matchup_obj->getFighter(2) . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                            $this->logger->info("- Created new matchup " . $created_matchup_obj->getFighter(1) . ' vs ' . $created_matchup_obj->getFighter(2) . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                            return $created_matchup_obj;
                        } else {
                            $this->audit_log->error("Failed to create new matchup " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                            $this->logger->error("- Failed to create new matchup " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                            return null;
                        }
                    }
                }
            }
        }

        $this->logger->info("Not eligble to be automatically created");
        return null;

        //Create matchup: If bookie has odds for other matchups on this event (and event name and date matches)

        //Create event and matchups: If matching whitelisting criteria (site specific). E.g. BFO, BetOnline and OKTAGON

        //Create event if: Bookie matches a specific name like BetOnline. Maybe also check if there are multiple matchups provided?

        //Create matchup: If this one is in schedule as Create?

        //Create matchup: If multiple bookies has this one provided?

        //Do not create: IF another matchup that is already matched has the same event name metadata but matched to different event
    }

    private function matchupProposedBySchedule($team1, $team2)
    {
        //Loop through all proposed "Create event AND matchups"
        if ($this->manual_actions_create_events != null) {
            foreach ($this->manual_actions_create_events as $create_event_action) {
                foreach ($create_event_action['action_obj']->matchups as $create_matchup_action) {
                    if (
                        (OddsTools::compareNames($create_matchup_action[0], $team1) > 82 && OddsTools::compareNames($create_matchup_action[1], $team2) > 82)
                        || (OddsTools::compareNames($create_matchup_action[1], $team1) > 82 && OddsTools::compareNames($create_matchup_action[0], $team2) > 82)
                    ) {
                        return ['found' => true, 'team1' => $create_matchup_action[0], 'team2' => $create_matchup_action[1], 'event_id' => null, 'event_name' => $create_event_action['action_obj']->eventTitle, 'event_date' => $create_event_action['action_obj']->eventDate];
                    }
                }
            }
        }

        if ($this->manual_actions_create_matchups != null) {
            //Loop through all proposed "Create matchup"
            foreach ($this->manual_actions_create_matchups as $create_matchup_action) {
                if (
                    (OddsTools::compareNames($create_matchup_action['action_obj']->matchups[0]->team1, $team1) > 82 && OddsTools::compareNames($create_matchup_action['action_obj']->matchups[0]->team2, $team2) > 82)
                    || (OddsTools::compareNames($create_matchup_action['action_obj']->matchups[0]->team2, $team1) > 82 && OddsTools::compareNames($create_matchup_action['action_obj']->matchups[0]->team1, $team2) > 82)
                ) {
                    return ['found' => true, 'team1' => $create_matchup_action['action_obj']->matchups[0]->team1, 'team2' => $create_matchup_action['action_obj']->matchups[0]->team2, 'event_id' => $create_matchup_action['action_obj']->eventID, 'event_name' => null, 'event_date' => null];
                }
            }
        }
        return ['found' => false, 'team1' => null, 'team2' => null, 'event_id' => null, 'event_name' => null, 'event_date' => null];
    }

    private function tryToCreateEvent($event_name, $matchup_time, $date_obj, $scheduler_blessing) 
    {
        //Check that ruleset allows for creation of this event
        $ruleset_event_blessing = $this->ruleset->evaluateEvent($this->bookie_obj, $event_name, $matchup_time);

        //Note: Blessing can also come from the scheduler (if event is scheduled)

        //Check that date is not in the past
        $current_date = new DateTime();
        if (($ruleset_event_blessing && $date_obj > $current_date) || ($scheduler_blessing['found'] && $scheduler_blessing['event_name'] != null && $scheduler_blessing['event_date'] != null)) { //Create event either if ruleset allows it or if the scheduler has it planned

            $new_event = null;
            if ($scheduler_blessing['event_name'] != null && $scheduler_blessing['event_date'] != null) {
                //Create event object based on scheduler fetched content (more accurate)
                $new_event = new Event(0, $scheduler_blessing['event_date'], $scheduler_blessing['event_name'], true);
            }
            else {
                //Create event object based on bookie fetched content (more accurate)
                $new_event = new Event(0, $date_obj->format('Y-m-d'), $event_name, true);
            }
            
            $created_event_obj = EventHandler::addNewEvent($new_event);
            if ($created_event_obj != null) {
                $this->audit_log->info("Created new event " . $created_event_obj->getName() . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                $this->logger->info("Created new event " . $created_event_obj->getName() . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                return $created_event_obj;
            } else {
                $this->audit_log->error("Failed to create new event " . $event_name . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                $this->logger->error("Failed to create new event " . $event_name . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($scheduler_blessing['found'] ? 'Yes' : 'No'));
                return null;
            }
        }
    }
}
