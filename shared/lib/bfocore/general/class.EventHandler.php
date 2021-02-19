<?php

require_once('lib/bfocore/dao/class.EventDAO.php');
require_once('config/inc.config.php');

class EventHandler
{

    public static function getAllUpcomingEvents()
    {
        return EventDAO::getAllUpcomingEvents();
    }

    public static function getAllEvents()
    {
        return EventDAO::getAllEvents();
    }

    public static function getAllFightsForEvent($a_iEventID, $a_bOnlyWithOdds = false)
    {
        return EventDAO::getAllFightsForEvent($a_iEventID, $a_bOnlyWithOdds);
    }

    public static function getAllUpcomingMatchups($a_bOnlyWithOdds = false)
    {
        return EventDAO::getAllUpcomingMatchups($a_bOnlyWithOdds);
    }

    /**
     * Gets all latest odds for a fight.
     * If the second parameter is specified it is possible to jump to historic
     * odds, for example getting the previous odds and comparing to the current
     */
    public static function getAllLatestOddsForFight($a_iFightID, $a_iHistoric = 0)
    {
        return EventDAO::getAllLatestOddsForFight($a_iFightID, $a_iHistoric);
    }

    public static function getLatestOddsForFightAndBookie($a_iFightID, $a_iBookieID)
    {
        return EventDAO::getLatestOddsForFightAndBookie($a_iFightID, $a_iBookieID);
    }

    public static function getAllOddsForFightAndBookie($a_iFightID, $a_iBookieID)
    {
        return EventDAO::getAllOddsForFightAndBookie($a_iFightID, $a_iBookieID);
    }

    public static function getAllOddsForMatchup($a_iMatchupID)
    {
        return EventDAO::getAllOddsForMatchup($a_iMatchupID);
    }

    public static function getEvent($a_iEventID, $a_bFutureEventsOnly = false)
    {
        return EventDAO::getEvent($a_iEventID, $a_bFutureEventsOnly);
    }

    public static function getEventByName($a_sName)
    {
        return EventDAO::getEventByName($a_sName);
    }

    /**
     * Get matching fight
     *
     * @param Fight $a_oFight
     * @return Fight Matching fight
     * @deprecated Use getMatchinFightV2() instead
     */
    public static function getMatchingFight($a_oFight)
    {
        return EventDAO::getFight($a_oFight->getFighter(1), $a_oFight->getFighter(2), $a_oFight->getEventID());
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
     * @param Fight $a_oFight
     * @return Fight Matching fight
     */
    public static function getMatchingFightV2($a_aParams)
    {
        return EventDAO::getMatchingFightV2($a_aParams);
    }

    public static function getFightByID($a_iID)
    {
        if ($a_iID == null)
        {
            return null;
        }
        return EventDAO::getFightByID($a_iID);
    }

    /**
     * Checks if the exact same fight odds for the operator and fight exist.
     *
     * @param FightOdds object to look for (date is not checked).
     * @return true if the exact odds exists and false if it doesn't.
     */
    public static function checkMatchingOdds($a_oFightOdds)
    {
        $oExistingFightOdds = EventHandler::getLatestOddsForFightAndBookie($a_oFightOdds->getFightID(), $a_oFightOdds->getBookieID());
        if ($oExistingFightOdds != null)
        {
            return $oExistingFightOdds->equals($a_oFightOdds);
        }
        return false;
    }

    public static function addNewFightOdds($a_oFightOdds)
    {
        if ($a_oFightOdds->getFightID() != '' && is_numeric($a_oFightOdds->getFightID()) &&
                $a_oFightOdds->getBookieID() != '' && is_numeric($a_oFightOdds->getBookieID()) &&
                $a_oFightOdds->getFighterOdds(1) != '' && is_numeric($a_oFightOdds->getFighterOdds(1)) &&
                $a_oFightOdds->getFighterOdds(2) != '' && is_numeric($a_oFightOdds->getFighterOdds(2))
        )
        {
            EventDAO::addNewFightOdds($a_oFightOdds);
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function addNewFight($a_oFight)
    {
        if ($a_oFight->getFighter(1) != '' && $a_oFight->getFighter(2) != '')
        {
            $iID = EventDAO::addNewFight($a_oFight);

            //Check if fight is only one for this event, if so, set it as main event. Not applicable if we automatically create events
            if (PARSE_CREATEMATCHUPS == false)
            {
                $aMatchups = self::getAllFightsForEvent($a_oFight->getEventID());
                if (count($aMatchups) == 1 && $aMatchups[0]->getID() == $iID)
                {
                    self::setFightAsMainEvent($iID);
                }
            }

            return $iID;
        }
        return false;
    }

    public static function addNewFighter($a_sFighterName)
    {
        return EventDAO::addNewFighter($a_sFighterName);
    }

    public static function addNewEvent($a_oEvent)
    {
        if ($a_oEvent->getName() != '' && $a_oEvent->getDate() != '')
        {
            return EventDAO::addNewEvent($a_oEvent);
        }
        return false;
    }

    public static function getBestOddsForFight($a_iFightID)
    {
        return EventDAO::getBestOddsForFight($a_iFightID);
    }

    public static function getBestOddsForFightAndFighter($a_iFightID, $a_iFighter)
    {
        return EventDAO::getBestOddsForFightAndFighter($a_iFightID, $a_iFighter);
    }

    public static function removeFight($a_iFightID)
    {
        return EventDAO::removeFight($a_iFightID);
    }

    public static function removeEvent($a_iEventID)
    {
        //First remove all matchups for this event
        $aMatchups = EventHandler::getAllFightsForEvent($a_iEventID);
        foreach($aMatchups as $oMatchup)
        {
            self::removeFight($oMatchup->getID());
        }
        return EventDAO::removeEvent($a_iEventID);
    }

    public static function getAllFightsForEventWithoutOdds($a_iEventID)
    {
        return EventDAO::getAllFightsForEventWithoutOdds($a_iEventID);
    }

    /**
     * Changes an event. If any field is left blank it will not be updated.
     */
    public static function changeEvent($a_iEventID, $a_sName = '', $a_sDate = '', $a_bDisplay = true)
    {
        $oEvent = EventHandler::getEvent($a_iEventID);

        if ($oEvent == null)
        {
            return false;
        }

        if ($a_sName != '')
        {
            $oEvent->setName($a_sName);
        }

        if ($a_sDate != '')
        {
            $oEvent->setDate($a_sDate);
        }

        $oEvent->setDisplay($a_bDisplay);

        return EventDAO::updateEvent($oEvent);
    }

    public static function changeFight($a_iFightID, $a_iEventID)
    {
        $oFight = EventHandler::getFightByID($a_iFightID);

        if ($oFight == null)
        {
            return false;
        }

        if ($a_iEventID != '')
        {
            $oFight->setEventID($a_iEventID);
        }

        return EventDAO::updateFight($oFight);
    }

    public static function addFighterAltName($a_iFighterID, $a_sAltName)
    {
        //TODO: Move this function to FighterHandler
        return EventDAO::addFighterAltName($a_iFighterID, $a_sAltName);
    }

    public static function getCurrentOddsIndex($a_iFightID, $a_iFighter)
    {
        if ($a_iFighter > 2 || $a_iFighter < 1)
        {
            return null;
        }

        $aFightOdds = EventHandler::getAllLatestOddsForFight($a_iFightID);

        if ($aFightOdds == null || sizeof($aFightOdds) == 0)
        {
            return null;
        }
        if (sizeof($aFightOdds) == 1)
        {
            return new FightOdds($a_iFightID, -1, ($a_iFighter == 1 ? $aFightOdds[0]->getFighterOdds($a_iFighter) : 0), ($a_iFighter == 2 ? $aFightOdds[0]->getFighterOdds($a_iFighter) : 0), -1);
        }
        $iCurrentOddsTotal = 0;
        foreach ($aFightOdds as $oFightOdds)
        {
            $iCurrOdds = $oFightOdds->getFighterOdds($a_iFighter);
            $iCurrentOddsTotal += $iCurrOdds < 0 ? ($iCurrOdds + 100) : ($iCurrOdds - 100);
        }
        $iCurrentOddsTotal = round($iCurrentOddsTotal / sizeof($aFightOdds) + ($iCurrentOddsTotal < 0 ? -100 : 100));

        $oReturnOdds = new FightOdds($a_iFightID, -1, ($a_iFighter == 1 ? $iCurrentOddsTotal : 0),
                        ($a_iFighter == 2 ? $iCurrentOddsTotal : 0), -1);

        return $oReturnOdds;
    }

    public static function getAllFightsForFighter($a_iFighterID)
    {
        return EventDAO::getAllFightsForFighter($a_iFighterID);
    }

    public static function setFightAsMainEvent($a_iFightID, $a_bIsMainEvent = true)
    {
        return EventDAO::setFightAsMainEvent($a_iFightID, $a_bIsMainEvent);
    }

    public static function searchEvent($a_sEventName, $a_bFutureEventsOnly = false)
    {
        return EventDAO::searchEvent($a_sEventName, $a_bFutureEventsOnly);
    }

    /**
     * Retrieve recent events
     *
     * @param int $a_iLimit Event limit (default 10)
     * @return array List of events
     */
    public static function getRecentEvents($a_iLimit = 10, $a_iOffset = 0)
    {

        if (!is_integer($a_iLimit) || $a_iLimit <= 0)
        {
            return null;
        }
        if (!is_integer((int) $a_iOffset) || (int) $a_iOffset < 0)
        {
            return null;
        }

        return EventDAO::getRecentEvents($a_iLimit, $a_iOffset);
    }

    /**
     * Writes an entry to the log for unmatched entries from parsing
     *
     * Type: 0 = matchup, 1 = prop without matchup, 2 = prop without template
     */
    public static function logUnmatched($a_sMatchup, $a_iBookieID, $a_iType, $a_aMetaData = null)
    {
        $metadata = serialize($a_aMetaData);
        return EventDAO::logUnmatched($a_sMatchup, $a_iBookieID, $a_iType, $metadata);
    }

    /**
     * Retrieves all unmatched entries
     *
     * Returns an associated array
     */
    public static function getUnmatched($a_iLimit = 10)
    {
        $unmatches = EventDAO::getUnmatched($a_iLimit);

        //Before returning, unserialize the metadata field
        foreach ($unmatches as $key => $val)
        {
            if ($val['metadata'] != '')
            {
                $unmatches[$key]['metadata'] = unserialize($unmatches[$key]['metadata']);
            } 
        }
        return $unmatches;
    }

    /**
     * Clears all unmatched entries
     */
    public static function clearUnmatched()
    {
        return EventDAO::clearUnmatched();
    }

    public static function getGenericEventForDate($a_sDate)
    {
        //Check first if future events date, if so, fetch that one
        if ($a_sDate == '2030-12-31') 
        {
            return self::getEvent(PARSE_FUTURESEVENT_ID);
        }
        $oEvent = EventDAO::getGenericEventForDate($a_sDate);
        if ($oEvent == null)
        {
            //No generic event was found, create it
            $iEventID = self::addNewEvent(new Event(0, $a_sDate, $a_sDate, true));
            if ($iEventID != false)
            {
               $oEvent = self::getEvent($iEventID);    
            }
        }
        return $oEvent;
    }

    public static function setMetaDataForMatchup($a_iMatchup_ID, $a_sAttribute, $a_sValue, $a_iBookieID)
    {
        return EventDAO::setMetaDataForMatchup($a_iMatchup_ID, $a_sAttribute, $a_sValue, $a_iBookieID);
    }

    public static function getLatestChangeDate($a_iEventID)
    {
        return EventDAO::getLatestChangeDate($a_iEventID);
    }


    public static function getAllEventsWithMatchupsWithoutResults()
    {
        return EventDAO::getAllEventsWithMatchupsWithoutResults();
    }

    public static function addMatchupResults($a_aParams)
    {
        return EventDAO::addMatchupResults($a_aParams);
    }

    public static function getResultsForMatchup($a_iMatchup_ID)
    {
        return EventDAO::getResultsForMatchup($a_iMatchup_ID);
    }

}

?>