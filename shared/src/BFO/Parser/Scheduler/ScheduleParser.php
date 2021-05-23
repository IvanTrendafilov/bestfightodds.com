<?php

namespace BFO\Parser\Scheduler;

use BFO\General\EventHandler;
use BFO\General\ScheduleHandler;
use BFO\DataTypes\Fight;

class ScheduleParser
{
    private $aMatchedExistingMatchups;
    private $aMatchedExistingEvents;

    public function __construct()
    {
        $this->aMatchedExistingMatchups = [];
        $this->aMatchedExistingEvents = [];
    }

    public function run($schedule) 
    {
        ScheduleHandler::clearAllManualActions();
        $this->parseEvents($schedule);
        $this->checkRemovedContent();
    }

    /*
     * Use this instead of parseSched to make it site independent
     */
    public function parseSchedPreFetched($schedule_col)
    {
        $this->aMatchedExistingMatchups = array();
        $this->aMatchedExistingEvents = array();
        ScheduleHandler::clearAllManualActions();
        $this->parseEvents($schedule_col);
        $this->checkRemovedContent();
    }

    public function parseEvents($schedule_col)
    {
        foreach ($schedule_col as $aEvent) {
            //Check if event matches an existing stored event. Must be numered though
            $aStoredEvents = EventHandler::getAllUpcomingEvents();
            $found = false;
            foreach ($aStoredEvents as $oStoredEvent) {
                //Check if numbered as well so we dont match generic ones like UFC Fight Night
                $aPrefixParts = explode(':', $aEvent['title']);
                $aPrefixParts = explode(' ', $aPrefixParts[0]);
                if ($oStoredEvent->getName() == $aEvent['title'] && $found == false && is_numeric($aPrefixParts[count($aPrefixParts) - 1])) {
                    $found = true;
                    $this->parseMatchups($aEvent, $oStoredEvent);
                    $this->checkEventDate($aEvent, $oStoredEvent);
                    $this->aMatchedExistingEvents[] = $oStoredEvent->getID();
                }
                if ($found == true) {
                    break;
                }
            }
            if ($found == false) {
                //Match on date (but first word must match, for example for UFC*)
                $aPrefixParts = explode(' ', $aEvent['title']);
                if (sizeof($aPrefixParts) > 1) {
                    foreach ($aStoredEvents as $oStoredEvent) {
                        $aStoredPrefixParts = explode(' ', $oStoredEvent->getName());
                        if ($aStoredPrefixParts[0] == $aPrefixParts[0] && date('Y-m-d', $aEvent['date']) == substr($oStoredEvent->getDate(), 0, 10)) {
                            //Found it! But should maybe be renamed
                            $found = true;
                            if ($oStoredEvent->getName() != $aEvent['title']) {
                                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oStoredEvent->getID(), 'eventTitle' => $aEvent['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                            }
                            $this->parseMatchups($aEvent, $oStoredEvent);
                            $this->aMatchedExistingEvents[] = $oStoredEvent->getID();
                        }
                    }
                }
            }
            if ($found == false) {
                //Name does not match, do alternative matching on prefix
                $aPrefixParts = explode(':', $aEvent['title']);
                if (sizeof($aPrefixParts) > 1) {
                    foreach ($aStoredEvents as $oStoredEvent) {
                        $aStoredPrefixParts = explode(':', $oStoredEvent->getName());
                        if ($aStoredPrefixParts[0] == $aPrefixParts[0] && !$found) {
                            //Found it! However event should be renamed
                            $found = true;
                            if ($oStoredEvent->getName() != $aEvent['title']) {
                                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oStoredEvent->getID(), 'eventTitle' => $aEvent['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                            }
                            $this->parseMatchups($aEvent, $oStoredEvent);
                            //But maybe date is still incorrect
                            $this->checkEventDate($aEvent, $oStoredEvent);
                            $this->aMatchedExistingEvents[] = $oStoredEvent->getID();
                        }
                    }
                }
            }
            if ($found == false) {
                //Match on existing matchups
                $aFoundMatches = array();
                foreach ($aEvent['matchups'] as $aParsedMatchup) {
                    $aMatchup = EventHandler::getMatchingFight(['team1_name' => $aParsedMatchup[0], 'team2_name' => $aParsedMatchup[1], 'future_only' => true]);
                    if ($aMatchup != null && $aMatchup->getEventID() != PARSE_FUTURESEVENT_ID) {
                        $aFoundMatches[$aMatchup->getEventID()] = isset($aFoundMatches[$aMatchup->getEventID()]) ? $aFoundMatches[$aMatchup->getEventID()]++ : 1;
                    }
                }
                if (sizeof($aFoundMatches) > 0) {
                    $found = true;
                    arsort($aFoundMatches);
                    if (sizeof($aFoundMatches) > 1) {
                        echo 'FAIL';
                        //TODO: More than 1.. handle this somehow?
                    }
                    reset($aFoundMatches);
                    //Probably found it! Howver event should be renamed if different
                    $oFoundEvent = EventHandler::getEvent(key($aFoundMatches));
                    if ($oFoundEvent->getName() != $aEvent['title']) {
                        ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oFoundEvent->getID(), 'eventTitle' => $aEvent['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                    }
                    $this->parseMatchups($aEvent, $oFoundEvent);
                    //But maybe date is still incorrect
                    $this->checkEventDate($aEvent, $oFoundEvent);
                    $this->aMatchedExistingEvents[] = $oFoundEvent->getID();
                }
            }
            if ($found == false) {
                //If creative matching fails, add entire event with matchups
                $sAction = $aEvent['title'] . ' £ ' . date('Y-m-d', $aEvent['date']) . ' => ';
                $aFilteredMatchups = array();
                $aOrphanMatchups = array();
                foreach ($aEvent['matchups'] as $aPM) {
                    $oMatchup = EventHandler::getMatchingFight(['team1_name' => $aPM[0], 'team2_name' => $aPM[1], 'future_only' => true]);
                    if ($oMatchup == null) {
                        $aFilteredMatchups[] = $aPM;
                    } else {
                        $aOrphanMatchups[] = $oMatchup->getID();
                    }
                }
                /*if (sizeof($aEvent['matchups']) > 0)
                {
                    $sAction = substr($sAction, 0, strlen($sAction) - 3);
                }*/
                ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $aEvent['title'], 'eventDate' => date('Y-m-d', $aEvent['date']), 'matchups' => $aFilteredMatchups), JSON_HEX_APOS | JSON_HEX_QUOT), 1);

                if (count($aOrphanMatchups) > 0) {
                    ScheduleHandler::storeManualAction(json_encode(array('eventTitle' => (string) $aEvent['title'], 'eventDate' => date('Y-m-d', $aEvent['date']), 'matchupIDs' => $aOrphanMatchups), JSON_HEX_APOS | JSON_HEX_QUOT), 8);
                }
            }
        }
    }

    private function checkEventDate($event_arr, $stored_event)
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

            $matchup = EventHandler::getMatchingFight(['team1_name' => $parsed_matchup[0], 'team2_name' => $parsed_matchup[1], 'future_only' => true]);
            if ($matchup != null) {
                //Found a match! But is it the right event?
                if ($matchup->getEventID() == $matched_event->getID()) {
                    //Complete match
                } else {
                    //Matched fight but for incorrect event, switch to matched event
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $matchup->getID(), 'eventID' => $matched_event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 6);
                }
                //Store the matched fight in the matched array to check unmatched DB entries later
                $this->aMatchedExistingMatchups[] = $matchup->getID();
            } else {
                //No matching fight, probably should be added as new fight
                ScheduleHandler::storeManualAction(json_encode(array('matchups' => array(array('team1' => $parsed_matchup[0], 'team2' => $parsed_matchup[1])), 'eventID'=> $matched_event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 5);
            }
        }

        return true;
    }

    public function checkRemovedContent()
    {
        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event) {
            //Skip FUTURE EVENTS event
            if ($event->getID() == PARSE_FUTURESEVENT_ID) {
                break;
            }
            $aMatchups = EventHandler::getAllFightsForEvent($event->getID());
            foreach ($aMatchups as $oMatchup) {
                if (!in_array($oMatchup->getID(), $this->aMatchedExistingMatchups)) {
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $oMatchup->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 7);
                }
            }
            if (!in_array($event->getID(), $this->aMatchedExistingEvents)) {
                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $event->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 4);
            }
        }
    }
}
