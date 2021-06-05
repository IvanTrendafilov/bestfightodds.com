<?php

namespace BFO\Parser;

use BFO\General\BookieHandler;
use BFO\General\ScheduleHandler;
use BFO\General\EventHandler;
use BFO\Utils\OddsTools;

use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\Parser\RulesetInterface;

class MatchupCreator
{
    private $logger = null;
    private $bookie_obj = null;
    private $ruleset = null;
    private $upcoming_matchups = null;
    private $audit_log = null;
    private $manual_actions_create_events = null;
    private $manual_actions_create_matchups = null;
    private $creation_ruleset = null;

    public function __construct(object $logger, int $bookie_id, RulesetInterface $creation_ruleset)
    {
        $this->logger = $logger;
        $this->bookie_obj = BookieHandler::getBookieByID($bookie_id);
        $this->creation_ruleset = $creation_ruleset;

        $this->audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        //Prefetch manual actions that we will check against later on
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

    public function evaluateMatchup(string $team1, string $team2, string $event_name, string $matchup_time)
    {
        $event_name = $event_name ?? 'Unknown Event';

        $this->logger->info("Evaluating if " . $team1 . " vs " . $team2 . " at " . $event_name . " on " . $matchup_time . " can be created..");

        $approved_by_ruleset = $this->creation_ruleset->evaluateMatchup($this->bookie_obj, $team1, $team2, $event_name, $matchup_time);
        $in_scheduler = $this->matchupProposedBySchedule($team1, $team2);

        if (($approved_by_ruleset || $in_scheduler['found']) && isset($event_name, $matchup_time)) {
            $date_obj = new \DateTime();
            $date_obj = $date_obj->setTimestamp($matchup_time);

            //Matchup time here is in UTC as captured by parsers. We offset by configured value to adjust from UTC to local time. e.g. west coast -7 for BFO or UTC 0 for PBO

            $event_adjust_date_obj = clone $date_obj;
            if (PARSE_MATCHUP_TZ_OFFSET < 0) {
                $event_adjust_date_obj->sub(new \DateInterval('PT' . abs(PARSE_MATCHUP_TZ_OFFSET) . 'H'));

            } elseif (PARSE_MATCHUP_TZ_OFFSET > 0) {
                $event_adjust_date_obj->add(new \DateInterval('PT' . PARSE_MATCHUP_TZ_OFFSET . 'H'));
            }

            $matched_event = $this->getMatchingEvent($event_name, $event_adjust_date_obj, $in_scheduler);
            if ($matched_event == null) {
                $matched_event = $this->tryToCreateEvent($event_name, $matchup_time, $event_adjust_date_obj, $in_scheduler);
            }

            if ($matched_event != null) {
                //if (!$this->teamHasOtherMatchup($matched_event, $team1, $team2, $in_scheduler)) { //Check is disabled, this allows for a team to have multiple matchups at the same event
                    return $this->createMatchup($team1, $team2, $matched_event, $date_obj, $in_scheduler);
                //}
            }
        }

        $this->logger->info("Not eligble to be automatically created");
        return null;
    }

    private function matchupProposedBySchedule(string $team1, string $team2)
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

    private function getMatchingEvent(string $event_name, object $date_obj, array $in_scheduler)
    {
        if (strtoupper(trim($event_name)) == 'FUTURE EVENTS') {
            return EventHandler::getEvent(PARSE_FUTURESEVENT_ID);
        }

        if ($in_scheduler['event_id'] != null) { //Scheduler has already matched this to an event
            $matched_event = EventHandler::getEvent($in_scheduler['event_id']);
            if ($matched_event == null) {
                $this->logger->error("Matched event in scheduler is invalid (null). Potential scheduler action with deleted event");
            }
            return $matched_event;
        }

        $date = $date_obj->format('Y-m-d');

        if (PARSE_USE_DATE_EVENTS) {
            $event_name = $date;
        }

        return EventHandler::getMatchingEvent($event_name, $date);
    }

    private function teamHasOtherMatchup(object $matched_event, string $team1, string $team2, array $in_scheduler)
    {
        if ($matched_event->getID() != PARSE_FUTURESEVENT_ID || $in_scheduler['found']) { //Ok to add multiple matchups if event is future events or if also featured in scheduler

            //Fetch upcoming matchups if not already done
            if ($this->upcoming_matchups == null) {
                //$this->upcoming_matchups = EventHandler::getMatchups(future_matchups_only: true, only_with_odds: true); // Look in all events
                $this->upcoming_matchups = EventHandler::getMatchups(event_id: $matched_event->getID(), only_with_odds: true); // Look only in the matched event
            }
            foreach ($this->upcoming_matchups as $matchup) {
                if (
                    OddsTools::compareNames($matchup->getFighter(1), $team1) > 82
                    || OddsTools::compareNames($matchup->getFighter(1), $team2) > 82
                    || OddsTools::compareNames($matchup->getFighter(2), $team1) > 82
                    || OddsTools::compareNames($matchup->getFighter(2), $team2) > 82
                ) {
                    $this->logger->info("- Found other matchup " . $matchup->getFighter(1) . " vs " . $matchup->getFighter(2) . ". Will not create");
                    return true;
                }
            }
        }
        return false;
    }

    private function tryToCreateEvent(string $event_name, string $matchup_time, object $date_obj, array $in_scheduler)
    {
        if (PARSE_USE_DATE_EVENTS == true) {
            //We used generic dates for events instead of fight cards
            $event_name = $date_obj->format('Y-m-d');
        }

        //Check that ruleset allows for creation of this event
        $approved_by_ruleset = $this->creation_ruleset->evaluateEvent($this->bookie_obj, $event_name, $matchup_time);

        //Note: Blessing can also come from the scheduler (if event is scheduled)

        //Check that date is not in the past
        $current_date = new \DateTime();
        if (($approved_by_ruleset && $date_obj > $current_date) || ($in_scheduler['found'] && $in_scheduler['event_name'] != null && $in_scheduler['event_date'] != null)) { //Create event either if ruleset allows it or if the scheduler has it planned

            $new_event = null;
            if ($in_scheduler['event_name'] != null && $in_scheduler['event_date'] != null) {
                //Create event object based on scheduler fetched content
                $new_event = new Event(0, $in_scheduler['event_date'], $in_scheduler['event_name'], true);
            } else {
                //Create event object based on bookie fetched content
                $new_event = new Event(0, $date_obj->format('Y-m-d'), $event_name, true);
            }

            $created_event_obj = EventHandler::addNewEvent($new_event);
            if ($created_event_obj != null) {
                $this->audit_log->info("Created new event " . $created_event_obj->getName() . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                $this->logger->info("Created new event " . $created_event_obj->getName() . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                return $created_event_obj;
            } else {
                $this->audit_log->error("Failed to create new event " . $event_name . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                $this->logger->error("Failed to create new event " . $event_name . ' on ' . $date_obj->format('Y-m-d') . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                return null;
            }
        }
    }

    private function createMatchup(string $team1, string $team2, object $matched_event, object $date_obj, array $in_scheduler)
    {
        //Check that date is not in the past
        if ($date_obj > new \DateTime()) {
            $new_matchup = null;
            if ($in_scheduler['found'] && $in_scheduler['team1'] != null && $in_scheduler['team2'] != null) {
                //Found in scheduler as well, use names from scheduler instead since they are more accurate
                $new_matchup = new Fight(0, $in_scheduler['team1'], $in_scheduler['team2'], $matched_event->getID());
            } else {
                $new_matchup = new Fight(0, $team1, $team2, $matched_event->getID());
            }
            $new_matchup->setCreateSource(1);

            $id = EventHandler::createMatchup($new_matchup);
            if ($id) {
                $created_matchup_obj = EventHandler::getMatchup($id);
                $this->audit_log->info("Created new matchup " . $created_matchup_obj->getFighter(1) . ' vs ' . $created_matchup_obj->getFighter(2) . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                $this->logger->info("- Created new matchup " . $created_matchup_obj->getFighter(1) . ' vs ' . $created_matchup_obj->getFighter(2) . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                return $created_matchup_obj;
            } else {
                $this->audit_log->error("Failed to create new matchup " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                $this->logger->error("- Failed to create new matchup " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
                return null;
            }
        }
        $this->audit_log->warning("Tried to create old matchup OR matchup too close to gametime " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
        $this->logger->warning("- Tried to create old matchup OR matchup too close to gametime " . $team1 . ' vs ' . $team2 . ' at ' . $matched_event->getName() . ' on ' . $matched_event->getDate() . ' as proposed by ' . $this->bookie_obj->getName() . ' . Also in scheduler: ' . ($in_scheduler['found'] ? 'Yes' : 'No'));
        return null;
    }
}
