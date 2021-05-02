<?php

namespace BFO\Parser\Scheduler;

use BFO\General\EventHandler;
use BFO\General\ScheduleHandler;
use BFO\DataTypes\Fight;

class ScheduleParser
{
    private $aMatchedExistingMatchups;
    private $aMatchedExistingEvents;

    /*
     * @depcreated Use parseSchedPreFetched instead of parseSched to make it site independent
     */
    public function parseSched()
    {
        require_once('lib/bfospec/schedule/class.MMAJunkieParser.php');
        $this->aMatchedExistingMatchups = array();
        $this->aMatchedExistingEvents = array();
        ScheduleHandler::clearAllManualActions();
        $aSchedule = MMAJunkieParser::fetchSchedule();
        $this->parseEvents($aSchedule);
        $this->checkRemovedContent();
    }

    /*
     * Use this instead of parseSched to make it site independent
     */
    public function parseSchedPreFetched($a_aSchedule)
    {
        $this->aMatchedExistingMatchups = array();
        $this->aMatchedExistingEvents = array();
        ScheduleHandler::clearAllManualActions();
        $this->parseEvents($a_aSchedule);
        $this->checkRemovedContent();
    }

    public function parseEvents($a_aSchedule)
    {
        foreach ($a_aSchedule as $aEvent) {
            //Check if event matches an existing stored event. Must be numered though
            $aStoredEvents = EventHandler::getAllUpcomingEvents();
            $bFound = false;
            foreach ($aStoredEvents as $oStoredEvent) {
                //Check if numbered as well so we dont match generic ones like UFC Fight Night
                $aPrefixParts = explode(':', $aEvent['title']);
                $aPrefixParts = explode(' ', $aPrefixParts[0]);
                if ($oStoredEvent->getName() == $aEvent['title'] && $bFound == false && is_numeric($aPrefixParts[count($aPrefixParts) - 1])) {
                    $bFound = true;
                    $this->parseMatchups($aEvent, $oStoredEvent);
                    $this->checkEventDate($aEvent, $oStoredEvent);
                    $this->aMatchedExistingEvents[] = $oStoredEvent->getID();
                }
                if ($bFound == true) {
                    break;
                }
            }
            if ($bFound == false) {
                //Match on date (but first word must match, for example for UFC*)
                $aPrefixParts = explode(' ', $aEvent['title']);
                if (sizeof($aPrefixParts) > 1) {
                    foreach ($aStoredEvents as $oStoredEvent) {
                        $aStoredPrefixParts = explode(' ', $oStoredEvent->getName());
                        if ($aStoredPrefixParts[0] == $aPrefixParts[0] && date('Y-m-d', $aEvent['date']) == substr($oStoredEvent->getDate(), 0, 10)) {
                            //Found it! But should maybe be renamed
                            $bFound = true;
                            if ($oStoredEvent->getName() != $aEvent['title']) {
                                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oStoredEvent->getID(), 'eventTitle' => $aEvent['title']), JSON_HEX_APOS | JSON_HEX_QUOT), 2);
                            }
                            $this->parseMatchups($aEvent, $oStoredEvent);
                            $this->aMatchedExistingEvents[] = $oStoredEvent->getID();
                        }
                    }
                }
            }
            if ($bFound == false) {
                //Name does not match, do alternative matching on prefix
                $aPrefixParts = explode(':', $aEvent['title']);
                if (sizeof($aPrefixParts) > 1) {
                    foreach ($aStoredEvents as $oStoredEvent) {
                        $aStoredPrefixParts = explode(':', $oStoredEvent->getName());
                        if ($aStoredPrefixParts[0] == $aPrefixParts[0]) {
                            //Found it! However event should be renamed
                            $bFound = true;
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
            if ($bFound == false) {
                //Match on existing matchups
                $aFoundMatches = array();
                foreach ($aEvent['matchups'] as $aParsedMatchup) {
                    $aMatchup = EventHandler::getMatchingFight(new Fight(-1, $aParsedMatchup[0], $aParsedMatchup[1], -1));
                    if ($aMatchup != null && $aMatchup->getEventID() != PARSE_FUTURESEVENT_ID) {
                        $aFoundMatches[$aMatchup->getEventID()] = isset($aFoundMatches[$aMatchup->getEventID()]) ? $aFoundMatches[$aMatchup->getEventID()]++ : 1;
                    }
                }
                if (sizeof($aFoundMatches) > 0) {
                    $bFound = true;
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
            if ($bFound == false) {
                //If creative matching fails, add entire event with matchups
                $sAction = $aEvent['title'] . ' £ ' . date('Y-m-d', $aEvent['date']) . ' => ';
                $aFilteredMatchups = array();
                $aOrphanMatchups = array();
                foreach ($aEvent['matchups'] as $aPM) {
                    $oMatchup = EventHandler::getMatchingFight(new Fight(-1, $aPM[0], $aPM[1], -1));
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

    private function checkEventDate($a_aEvent, $a_oStoredEvent)
    {
        if (date('Y-m-d', $a_aEvent['date']) != substr($a_oStoredEvent->getDate(), 0, 10)) {
            ScheduleHandler::storeManualAction(json_encode(array('eventID' => $a_oStoredEvent->getID(), 'eventDate' => date('Y-m-d', $a_aEvent['date'])), JSON_HEX_APOS | JSON_HEX_QUOT), 3);
            return true;
        }
        return false;
    }

    public function parseMatchups($a_aEvent, $a_oMatchedEvent)
    {
        foreach ($a_aEvent['matchups'] as $aParsedMatchup) {
            $oMatchup = EventHandler::getMatchingFight(new Fight(-1, $aParsedMatchup[0], $aParsedMatchup[1], -1));
            if ($oMatchup != null) {
                //Found a match! But is it the right event?
                if ($oMatchup->getEventID() == $a_oMatchedEvent->getID()) {
                    //Complete match
                } else {
                    //Matched fight but for incorrect event, switch to matched event
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $oMatchup->getID(), 'eventID' => $a_oMatchedEvent->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 6);
                }
                //Store the matched fight in the matched array to check unmatched DB entries later
                $this->aMatchedExistingMatchups[] = $oMatchup->getID();
            } else {
                //No matching fight, probably should be added as new fight
                ScheduleHandler::storeManualAction(json_encode(array('matchups' => array(array('team1' => $aParsedMatchup[0], 'team2' => $aParsedMatchup[1])), 'eventID'=> $a_oMatchedEvent->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 5);
            }
        }

        return true;
    }

    public function checkRemovedContent()
    {
        $aEvents = EventHandler::getAllUpcomingEvents();
        foreach ($aEvents as $oEvent) {
            //Skip FUTURE EVENTS event
            if ($oEvent->getID() == PARSE_FUTURESEVENT_ID) {
                break;
            }
            $aMatchups = EventHandler::getAllFightsForEvent($oEvent->getID());
            foreach ($aMatchups as $oMatchup) {
                if (!in_array($oMatchup->getID(), $this->aMatchedExistingMatchups)) {
                    ScheduleHandler::storeManualAction(json_encode(array('matchupID' => $oMatchup->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 7);
                }
            }
            if (!in_array($oEvent->getID(), $this->aMatchedExistingEvents)) {
                ScheduleHandler::storeManualAction(json_encode(array('eventID' => $oEvent->getID()), JSON_HEX_APOS | JSON_HEX_QUOT), 4);
            }
        }
    }
}
