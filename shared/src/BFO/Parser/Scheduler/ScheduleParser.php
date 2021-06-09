<?php

namespace BFO\Parser\Scheduler;

use BFO\General\EventHandler;
use BFO\General\ScheduleHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

class ScheduleParser
{
    private $matched_existing_matchups;
    private $matched_existing_events;

    public function __construct()
    {
        $this->matched_existing_matchups = [];
        $this->matched_existing_events = [];
    }

    public function run($schedule): void 
    {
        ScheduleHandler::clearAllManualActions();
        $this->parseEvents($schedule);
        $this->checkRemovedContent();
    }

    /*
     * Use this instead of parseSched to make it site independent
     */
    public function parseSchedPreFetched(array $parsed_events): void
    {
        $this->matched_existing_matchups = [];
        $this->matched_existing_events = [];
        ScheduleHandler::clearAllManualActions();
        $this->parseEvents($parsed_events);
        $this->checkRemovedContent();
    }

    public function parseEvents(array $parsed_events)
    {
        foreach ($parsed_events as $parsed_event) {
            //Check if event matches an existing stored event. Must be numered though
            $stored_events = EventHandler::getEvents(future_events_only: true);
            $found = false;
            foreach ($stored_events as $event) {
                //Check if numbered as well so we dont match generic ones like UFC Fight Night
                $prefix_parts = explode(':', $parsed_event['title']);
                $prefix_parts = explode(' ', $prefix_parts[0]);
                if ($event->getName() == $parsed_event['title'] && !$found && is_numeric($prefix_parts[count($prefix_parts) - 1])) {
                    $found = true;
                    $this->parseMatchups($parsed_event, $event);
                    $this->checkEventDate($parsed_event, $event);
                    $this->matched_existing_events[] = $event->getID();
                }
                if ($found == true) {
                    break;
                }
            }
            if (!$found) {
                //Match on date (but first word must match, for example for UFC*)
                $prefix_parts = explode(' ', $parsed_event['title']);
                if (sizeof($prefix_parts) > 1) {
                    foreach ($stored_events as $event) {
                        $stored_prefix_parts = explode(' ', $event->getName());
                        if ($stored_prefix_parts[0] == $prefix_parts[0] && date('Y-m-d', $parsed_event['date']) == substr($event->getDate(), 0, 10)) {
                            //Found it! But should maybe be renamed
                            $found = true;
                            if ($event->getName() != $parsed_event['title']) {
                                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $event->getID(), 'eventTitle' => $parsed_event['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                            }
                            $this->parseMatchups($parsed_event, $event);
                            $this->matched_existing_events[] = $event->getID();
                        }
                    }
                }
            }
            if (!$found) {
                //Name does not match, do alternative matching on prefix
                $prefix_parts = explode(':', $parsed_event['title']);
                if (sizeof($prefix_parts) > 1) {
                    foreach ($stored_events as $event) {
                        $stored_prefix_parts = explode(':', $event->getName());
                        if ($stored_prefix_parts[0] == $prefix_parts[0] && !$found) {
                            //Found it! However event should be renamed
                            $found = true;
                            if ($event->getName() != $parsed_event['title']) {
                                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $event->getID(), 'eventTitle' => $parsed_event['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                            }
                            $this->parseMatchups($parsed_event, $event);
                            //But maybe date is still incorrect
                            $this->checkEventDate($parsed_event, $event);
                            $this->matched_existing_events[] = $event->getID();
                        }
                    }
                }
            }
            if (!$found) {
                //Match on existing matchups
                $found_matches = [];
                foreach ($parsed_event['matchups'] as $aParsedMatchup) {
                    $aMatchup = EventHandler::getMatchingMatchup(team1_name: $aParsedMatchup[0], team2_name: $aParsedMatchup[1], future_only: true);
                    if ($aMatchup && $aMatchup->getEventID() != PARSE_FUTURESEVENT_ID) {
                        $found_matches[$aMatchup->getEventID()] = isset($found_matches[$aMatchup->getEventID()]) ? $found_matches[$aMatchup->getEventID()]++ : 1;
                    }
                }
                if (sizeof($found_matches) > 0) {
                    $found = true;
                    arsort($found_matches);
                    if (sizeof($found_matches) > 1) {
                        echo 'FAIL';
                        //TODO: More than 1.. handle this somehow?
                    }
                    reset($found_matches);
                    //Probably found it! Howver event should be renamed if different
                    $oFoundEvent = EventHandler::getEvent(key($found_matches));
                    if ($oFoundEvent->getName() != $parsed_event['title']) {
                        ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oFoundEvent->getID(), 'eventTitle' => $parsed_event['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                    }
                    $this->parseMatchups($parsed_event, $oFoundEvent);
                    //But maybe date is still incorrect
                    $this->checkEventDate($parsed_event, $oFoundEvent);
                    $this->matched_existing_events[] = $oFoundEvent->getID();
                }
            }
            if (!$found) {
                //If creative matching fails, add entire event with matchups
                $sAction = $parsed_event['title'] . ' £ ' . date('Y-m-d', $parsed_event['date']) . ' => ';
                $aFilteredMatchups = [];
                $aOrphanMatchups = [];
                foreach ($parsed_event['matchups'] as $aPM) {
                    $oMatchup = EventHandler::getMatchingMatchup(team1_name: $aPM[0], team2_name: $aPM[1], future_only: true);
                    if ($oMatchup == null) {
                        $aFilteredMatchups[] = $aPM;
                    } else {
                        $aOrphanMatchups[] = $oMatchup->getID();
                    }
                }
                /*if (sizeof($parsed_event['matchups']) > 0)
                {
                    $sAction = substr($sAction, 0, strlen($sAction) - 3);
                }*/
                ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $parsed_event['title'], 'eventDate' => date('Y-m-d', $parsed_event['date']), 'matchups' => $aFilteredMatchups), JSON_HEX_APOS | JSON_HEX_QUOT), 1);

                if (count($aOrphanMatchups) > 0) {
                    ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $parsed_event['title'], 'eventDate' => date('Y-m-d', $parsed_event['date']), 'matchupIDs' => $aOrphanMatchups), JSON_HEX_APOS | JSON_HEX_QUOT), 8);
                }
            }
        }
    }

    private function checkEventDate(array $event_arr, Event $stored_event)
    {
        if (date('Y-m-d', $event_arr['date']) != substr($stored_event->getDate(), 0, 10)) {
            ScheduleHandler::storeManualAction(json_encode(array('eventID' => $stored_event->getID(), 'eventDate' => date('Y-m-d', $event_arr['date'])), JSON_HEX_APOS | JSON_HEX_QUOT), 3);
            return true;
        }
        return false;
    }

    public function parseMatchups($event, $matched_event)
    {
        foreach ($event['matchups'] as $parsed_matchup) {

            $matchup = EventHandler::getMatchingMatchup(team1_name: $parsed_matchup[0], team2_name: $parsed_matchup[1], future_only: true);
            if ($matchup) {
                //Found a match! But is it the right event?
                if ($matchup->getEventID() == $matched_event->getID()) {
                    //Complete match
                } else {
                    //Matched fight but for incorrect event, switch to matched event
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $matchup->getID(), 'eventID' => $matched_event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 6);
                }
                //Store the matched fight in the matched array to check unmatched DB entries later
                $this->matched_existing_matchups[] = $matchup->getID();
            } else {
                //No matching fight, probably should be added as new fight
                ScheduleHandler::storeManualAction(json_encode(array('matchups' => array(array('team1' => $parsed_matchup[0], 'team2' => $parsed_matchup[1])), 'eventID'=> $matched_event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 5);
            }
        }

        return true;
    }

    public function checkRemovedContent()
    {
        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            //Skip FUTURE EVENTS event
            if ($event->getID() == PARSE_FUTURESEVENT_ID) {
                break;
            }
            $matchups = EventHandler::getMatchups(event_id: $event->getID());
            foreach ($matchups as $matchup) {
                if (!in_array($matchup->getID(), $this->matched_existing_matchups)
                    && $matchup->getCreateSource() != 1) { //Don't remove matchups that are now owned by sportsbooks (has odds)
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $matchup->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 7);
                }
            }
            if (!in_array($event->getID(), $this->matched_existing_events)) {
                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 4);
            }
        }
    }
}
