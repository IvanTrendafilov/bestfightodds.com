<?php

require_once('lib/bfocore/db/class.EventDB.php');
require_once('config/inc.config.php');

class EventHandler
{

    public static function getAllUpcomingEvents()
    {
        return EventDB::getAllUpcomingEvents();
    }

    public static function getAllEvents()
    {
        return EventDB::getAllEvents();
    }

    public static function getAllFightsForEvent($event_id, $only_with_odds = false)
    {
        return EventDB::getAllFightsForEvent($event_id, $only_with_odds);
    }

    public static function getAllUpcomingMatchups($only_with_odds = false)
    {
        return EventDB::getAllUpcomingMatchups($only_with_odds);
    }

    /**
     * Gets all latest odds for a fight.
     * If the second parameter is specified it is possible to jump to historic
     * odds, for example getting the previous odds and comparing to the current
     */
    public static function getAllLatestOddsForFight($fight_id, $historic_offset = 0)
    {
        return EventDB::getAllLatestOddsForFight($fight_id, $historic_offset);
    }

    public static function getLatestOddsForFightAndBookie($fight_id, $bookie_id)
    {
        return EventDB::getLatestOddsForFightAndBookie($fight_id, $bookie_id);
    }

    public static function getAllOddsForFightAndBookie($fight_id, $bookie_id)
    {
        return EventDB::getAllOddsForFightAndBookie($fight_id, $bookie_id);
    }

    public static function getAllOddsForMatchup($matchup_id)
    {
        return EventDB::getAllOddsForMatchup($matchup_id);
    }

    public static function getEvent($event_id, $future_event_only = false)
    {
        return EventDB::getEvent($event_id, $future_event_only);
    }

    public static function getEventByName($a_sName)
    {
        return EventDB::getEventByName($a_sName);
    }

    /**
     * Get matching fight
     *
     * @param Fight $matchup_obj
     * @return Fight Matching fight
     * @deprecated Use getMatchinFightV2() instead
     */
    public static function getMatchingFight($matchup_obj)
    {
        return EventDB::getFight($matchup_obj->getFighter(1), $matchup_obj->getFighter(2), $matchup_obj->getEventID());
    }

    //New version of getFight above. Improvements are the possibility of finding old matchups
    //Params:
    //team1_name = Required
    //team2_name = Required
    //future_only = Optional
    //event_id = Optional
    /**
     * Get matching fight (V2)
     *
     * @return Fight Matching fight
     */
    public static function getMatchingFightV2($params)
    {
        return EventDB::getMatchingFightV2($params);
    }

    public static function getFightByID($id)
    {
        if ($id == null) {
            return null;
        }
        return EventDB::getFightByID($id);
    }

    /**
     * Checks if the exact same fight odds for the operator and fight exist.
     *
     * @param FightOdds object to look for (date is not checked).
     * @return true if the exact odds exists and false if it doesn't.
     */
    public static function checkMatchingOdds($odds_obj)
    {
        $found_odds_obj = EventHandler::getLatestOddsForFightAndBookie($odds_obj->getFightID(), $odds_obj->getBookieID());
        if ($found_odds_obj != null) {
            return $found_odds_obj->equals($odds_obj);
        }
        return false;
    }

    public static function addNewFightOdds($odds_obj)
    {
        //Validate input
        if (
            $odds_obj->getFightID() == '' || !is_numeric($odds_obj->getFightID()) ||
            $odds_obj->getBookieID() == '' || !is_numeric($odds_obj->getBookieID()) ||
            $odds_obj->getFighterOdds(1) == '' || !is_numeric($odds_obj->getFighterOdds(1)) ||
            $odds_obj->getFighterOdds(2) == '' || !is_numeric($odds_obj->getFighterOdds(2))
        ) {
            return false;
        }
        //Validate that odds is not in range -99 => +99
        if (
            (intval($odds_obj->getFighterOdds(1)) >= -99 && intval($odds_obj->getFighterOdds(1) <= 99)) ||
            (intval($odds_obj->getFighterOdds(2)) >= -99 && intval($odds_obj->getFighterOdds(2) <= 99))
        ) {
            return false;
        }

        //Validate that odds is not positive on both sides (=surebet, most likely invalid)
        if (intval($odds_obj->getFighterOdds(1)) >= 0 && intval($odds_obj->getFighterOdds(2) >= 0)
        ) {
            return false;
        }

        return EventDB::addNewFightOdds($odds_obj);
    }

    public static function addNewFight($a_oFight)
    {
        if ($a_oFight->getFighter(1) != '' && $a_oFight->getFighter(2) != '') {
            $iID = EventDB::addNewFight($a_oFight);

            //Check if fight is only one for this event, if so, set it as main event. Not applicable if we automatically create events - DISABLED
            /*if (PARSE_CREATEMATCHUPS == false)
            {
                $aMatchups = self::getAllFightsForEvent($a_oFight->getEventID());
                if (count($aMatchups) == 1 && $aMatchups[0]->getID() == $iID)
                {
                    self::setFightAsMainEvent($iID);
                }
            }*/

            return $iID;
        }
        return false;
    }

    public static function addNewFighter($a_sFighterName)
    {
        return EventDB::addNewFighter($a_sFighterName);
    }

    public static function addNewEvent($event)
    {
        if ($event->getName() == '' || $event->getDate() == '') {
            return false;
        }

        //Validate date
        $dt = DateTime::createFromFormat("Y-m-d", $event->getDate());
        if ($dt === false || array_sum($dt::getLastErrors()) > 0) {

            return false;
        }

        $id = EventDB::addNewEvent($event);
        if ($id != false && $id != null) {
            return EventHandler::getEvent($id);
        }
        return false;
    }

    public static function getBestOddsForFight($matchup_id)
    {
        return EventDB::getBestOddsForFight($matchup_id);
    }

    public static function getBestOddsForFightAndFighter($matchup_id, $fighter_pos)
    {
        return EventDB::getBestOddsForFightAndFighter($matchup_id, $fighter_pos);
    }

    public static function removeFight($matchup_id)
    {
        return EventDB::removeFight($matchup_id);
    }

    public static function removeEvent($event_id)
    {
        //First remove all matchups for this event
        $matchups = EventHandler::getAllFightsForEvent($event_id);
        foreach ($matchups as $matchup) {
            self::removeFight($matchup->getID());
        }
        return EventDB::removeEvent($event_id);
    }

    public static function getAllFightsForEventWithoutOdds($event_id)
    {
        return EventDB::getAllFightsForEventWithoutOdds($event_id);
    }

    /**
     * Changes an event. If any field is left blank it will not be updated.
     */
    public static function changeEvent($event_id, $new_event_name = '', $new_event_date = '', $new_is_visible = true)
    {
        $event_obj = EventHandler::getEvent($event_id);

        if ($event_obj == null) {
            return false;
        }

        if ($new_event_name != '') {
            $event_obj->setName($new_event_name);
        }

        if ($new_event_date != '') {
            $event_obj->setDate($new_event_date);
        }

        $event_obj->setDisplay($new_is_visible);

        return EventDB::updateEvent($event_obj);
    }

    public static function changeFight($matchup_id, $event_id)
    {
        $matchup_obj = EventHandler::getFightByID($matchup_id);

        if ($matchup_obj == null) {
            return false;
        }

        if ($event_id != '') {
            $matchup_obj->setEventID($event_id);
        }

        return EventDB::updateFight($matchup_obj);
    }

    public static function getCurrentOddsIndex($a_iFightID, $a_iFighter)
    {
        if ($a_iFighter > 2 || $a_iFighter < 1) {
            return null;
        }

        $aFightOdds = EventHandler::getAllLatestOddsForFight($a_iFightID);

        if ($aFightOdds == null || sizeof($aFightOdds) == 0) {
            return null;
        }
        if (sizeof($aFightOdds) == 1) {
            return new FightOdds($a_iFightID, -1, ($a_iFighter == 1 ? $aFightOdds[0]->getFighterOdds($a_iFighter) : 0), ($a_iFighter == 2 ? $aFightOdds[0]->getFighterOdds($a_iFighter) : 0), -1);
        }
        $iCurrentOddsTotal = 0;
        foreach ($aFightOdds as $oFightOdds) {
            $iCurrOdds = $oFightOdds->getFighterOdds($a_iFighter);
            $iCurrentOddsTotal += $iCurrOdds < 0 ? ($iCurrOdds + 100) : ($iCurrOdds - 100);
        }
        $iCurrentOddsTotal = round($iCurrentOddsTotal / sizeof($aFightOdds) + ($iCurrentOddsTotal < 0 ? -100 : 100));

        $oReturnOdds = new FightOdds(
            $a_iFightID,
            -1,
            ($a_iFighter == 1 ? $iCurrentOddsTotal : 0),
            ($a_iFighter == 2 ? $iCurrentOddsTotal : 0),
            -1
        );

        return $oReturnOdds;
    }

    public static function getAllFightsForFighter($team_id)
    {
        return EventDB::getAllFightsForFighter($team_id);
    }

    public static function setFightAsMainEvent($fight_id, $set_as_main_event = true)
    {
        return EventDB::setFightAsMainEvent($fight_id, $set_as_main_event);
    }

    public static function searchEvent($event_name, $only_future_events = false)
    {
        return EventDB::searchEvent($event_name, $only_future_events);
    }

    /**
     * Retrieve recent events
     *
     * @param int $limit Event limit (default 10)
     * @return array List of events
     */
    public static function getRecentEvents($limit = 10, $offset = 0)
    {

        if (!is_integer($limit) || $limit <= 0) {
            return null;
        }
        if (!is_integer((int) $offset) || (int) $offset < 0) {
            return null;
        }

        return EventDB::getRecentEvents($limit, $offset);
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     *
     * Type: 0 = matchup, 1 = prop without matchup, 2 = prop without template
     */
    public static function logUnmatched($a_sMatchup, $a_iBookieID, $a_iType, $a_aMetaData = null)
    {
        $metadata = serialize($a_aMetaData);
        return EventDB::logUnmatched($a_sMatchup, $a_iBookieID, $a_iType, $metadata);
    }

    /**
     * Retrieves all unmatched entries
     *
     * Returns an associated array
     */
    public static function getUnmatched($limit = 10, $type = -1)
    {
        $unmatches = EventDB::getUnmatched($limit, $type);

        //Before returning, unserialize the metadata field
        foreach ($unmatches as $key => $val) {
            if ($val['metadata'] != '') {
                $unmatches[$key]['metadata'] = unserialize($unmatches[$key]['metadata']);
            }
        }
        return $unmatches;
    }

    /**
     * Clears all unmatched entries
     */
    public static function clearUnmatched($unmatched_item = null, $bookie_id = null)
    {
        return EventDB::clearUnmatched($unmatched_item, $bookie_id);
    }

    public static function getGenericEventForDate($date_str)
    {
        //Check first if future events date, if so, fetch that one
        if ($date_str == '2030-12-31') {
            return self::getEvent(PARSE_FUTURESEVENT_ID);
        }
        $event_obj = EventDB::getGenericEventForDate($date_str);
        if ($event_obj == null) {
            //No generic event was found, create it
            $event_obj = self::addNewEvent(new Event(0, $date_str, $date_str, true));
        }
        return $event_obj;
    }

    public static function setMetaDataForMatchup($matchup_id, $metadata_attribute, $metadata_value, $bookie_id)
    {
        return EventDB::setMetaDataForMatchup($matchup_id, $metadata_attribute, $metadata_value, $bookie_id);
    }

    public static function getLatestChangeDate($event_id)
    {
        return EventDB::getLatestChangeDate($event_id);
    }

    public static function getAllEventsWithMatchupsWithoutResults()
    {
        return EventDB::getAllEventsWithMatchupsWithoutResults();
    }

    public static function addMatchupResults($params)
    {
        return EventDB::addMatchupResults($params);
    }

    public static function getResultsForMatchup($matchup_id)
    {
        return EventDB::getResultsForMatchup($matchup_id);
    }
}
