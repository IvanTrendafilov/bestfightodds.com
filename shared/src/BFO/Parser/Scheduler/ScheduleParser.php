<?php

namespace BFO\Parser\Scheduler;

use BFO\General\EventHandler;
use BFO\General\ScheduleHandler;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

/**
 * Takes a parsed schedule (collection of events with matchups) and checks against the existing
 * events and matchups stored in the database. Manual actions are then created and stored in the
 * database for any suggested actions (delete, move, create, etc). Note that no action is taken
 * automatically by the schedule parser. OddsJob can be configured with the PARSE_CREATEMATCHUPS
 * parameter to automatically process Create actions created here
 */
class ScheduleParser
{
    private $matched_existing_matchups;
    private $matched_existing_events;

    public function __construct()
    {
        $this->matched_existing_matchups = [];
        $this->matched_existing_events = [];
    }

    public function run(array $schedule): void
    {
        ScheduleHandler::clearAllManualActions();
        $this->processEvents($schedule);
        $this->suggestToRemoveUnmatchedMatchups();
    }

    public function processEvents(array $parsed_events): void
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
                    $this->processMatchups($parsed_event, $event);
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
                            $this->processMatchups($parsed_event, $event);
                            $this->matched_existing_events[] = $event->getID();
                        }
                    }
                }
            }
            if (!$found) {
                //Match on existing matchups
                $found_matches = [];
                foreach ($parsed_event['matchups'] as $parsed_matchup) {
                    $stored_matchup = EventHandler::getMatchingMatchup(team1_name: $parsed_matchup[0], team2_name: $parsed_matchup[1], future_only: true);
                    if ($stored_matchup && $stored_matchup->getEventID() != PARSE_FUTURESEVENT_ID) {
                        $found_matches[$stored_matchup->getEventID()] = isset($found_matches[$stored_matchup->getEventID()]) ? $found_matches[$stored_matchup->getEventID()] + 1 : 1;
                    }
                }
                if (count($found_matches) > 0) {
                    $found = true;
                    asort($found_matches, SORT_NUMERIC);
                    //Probably found it! However event should be renamed if different
                    $found_event = EventHandler::getEvent(array_key_first($found_matches));
                    if ($found_event->getName() != $parsed_event['title']) {
                        ScheduleHandler::storeManualAction(json_encode(array('eventID' => $found_event->getID(), 'eventTitle' => $parsed_event['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                    }
                    $this->processMatchups($parsed_event, $found_event);
                    //But maybe date is still incorrect
                    $this->checkEventDate($parsed_event, $found_event);
                    $this->matched_existing_events[] = $found_event->getID();
                }
            }
            if (!$found) {
                //If creative matching fails, add entire event with matchups
                $filtered_matchups = [];
                $orphan_matchups = [];
                foreach ($parsed_event['matchups'] as $parsed_matchup) {
                    $stored_matchup = EventHandler::getMatchingMatchup(team1_name: $parsed_matchup[0], team2_name: $parsed_matchup[1], future_only: true);
                    if ($stored_matchup == null) {
                        $filtered_matchups[] = $parsed_matchup;
                    } else {
                        $orphan_matchups[] = $stored_matchup->getID();
                    }
                }
                ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $parsed_event['title'], 'eventDate' => date('Y-m-d', $parsed_event['date']), 'matchups' => $filtered_matchups), JSON_HEX_APOS | JSON_HEX_QUOT), 1);

                if (count($orphan_matchups) > 0) {
                    ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $parsed_event['title'], 'eventDate' => date('Y-m-d', $parsed_event['date']), 'matchupIDs' => $orphan_matchups), JSON_HEX_APOS | JSON_HEX_QUOT), 8);
                }
            }
        }
    }

    private function checkEventDate(array $event_arr, Event $stored_event): bool
    {
        if (date('Y-m-d', $event_arr['date']) != substr($stored_event->getDate(), 0, 10)) {
            ScheduleHandler::storeManualAction(json_encode(array('eventID' => $stored_event->getID(), 'eventDate' => date('Y-m-d', $event_arr['date'])), JSON_HEX_APOS | JSON_HEX_QUOT), 3);
            return true;
        }
        return false;
    }

    public function processMatchups(array $event, Event $matched_event)
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
                ScheduleHandler::storeManualAction(json_encode(array('matchups' => array(array('team1' => $parsed_matchup[0], 'team2' => $parsed_matchup[1])), 'eventID' => $matched_event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 5);
            }
        }

        return true;
    }

    public function suggestToRemoveUnmatchedMatchups()
    {
        $events = EventHandler::getEvents(future_events_only: true);
        foreach ($events as $event) {
            //Skip FUTURE EVENTS event
            if ($event->getID() == PARSE_FUTURESEVENT_ID) {
                break;
            }
            $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_without_odds: true);
            foreach ($matchups as $matchup) {
                if (
                    !in_array($matchup->getID(), $this->matched_existing_matchups)
                    && $matchup->getCreateSource() == 2
                ) { //Only suggest to remove matchups created by scheduler
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $matchup->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 7);
                }
            }
        }
    }
}
