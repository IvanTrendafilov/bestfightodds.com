<?php

namespace BFO\General;

use BFO\DB\OddsDB;
use BFO\General\BookieHandler;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\EventPropBet;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

class OddsHandler
{
    public static function addPropBet($a_oPropBet)
    {
        if (
            $a_oPropBet->getPropOdds() == '' || $a_oPropBet->getNegPropOdds() == ''
            || (intval($a_oPropBet->getPropOdds()) > 0 && intval($a_oPropBet->getNegPropOdds()) > 0)
        ) { //Ignore odds that are positive on both ends (should indicate an incorrect line)
            return false;
        }
        return OddsDB::addPropBet($a_oPropBet);
    }

    public static function getPropBetsForMatchup($a_iMatchupID)
    {
        return OddsDB::getPropBetsForMatchup($a_iMatchupID);
    }

    public static function getAllPropTypesForMatchup($a_iMatchupID)
    {
        return OddsDB::getAllPropTypesForMatchup($a_iMatchupID);
    }

    public static function getAllPropTypes()
    {
        return OddsDB::getAllPropTypes();
    }

    public static function getPropTypeByID($a_iID)
    {
        return OddsDB::getPropTypeByID($a_iID);
    }

    public static function checkMatchingPropOdds($a_oPropBet)
    {
        $oExistingPropOdds = OddsHandler::getLatestPropOdds($a_oPropBet->getMatchupID(), $a_oPropBet->getBookieID(), $a_oPropBet->getPropTypeID(), $a_oPropBet->getTeamNumber());
        if ($oExistingPropOdds != null) {
            return $oExistingPropOdds->equals($a_oPropBet);
        }
        return false;
    }

    public static function getLatestPropOdds($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDB::getLatestPropOdds($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function getAllLatestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam = 0, $a_iOffset = 0)
    {
        $aRetOdds = array();

        //Loop through each bookie and retrieve prop odds
        $aBookies = BookieHandler::getAllBookies();
        foreach ($aBookies as $oBookie) {
            $oOdds = OddsDB::getLatestPropOdds($a_iMatchupID, $oBookie->getID(), $a_iPropTypeID, $a_iTeam, $a_iOffset);
            if ($oOdds != null) {
                $aRetOdds[] = $oOdds;
            }
        }

        return $aRetOdds;
    }

    public static function getBestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam)
    {
        return OddsDB::getBestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam);
    }

    public static function getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDB::getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function getPropCountForMatchup($a_iMatchupID)
    {
        return count(OddsDB::getAllPropTypesForMatchup($a_iMatchupID));
    }

    /* Gets the average value across multiple bookies for specific prop type */
    public static function getCurrentPropIndex($a_iMatchupID, $a_iPosProp, $a_iPropTypeID, $a_iTeam)
    {
        $iSkippedProps = 0; //Keeps track of skipped prop bets that are not available, i.e. stored as -99999 in the database

        if ($a_iTeam > 2 || $a_iTeam < 0) {
            return null;
        }

        $aOdds = OddsHandler::getAllLatestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam);

        if ($aOdds == null || sizeof($aOdds) == 0) {
            return null;
        }
        if (sizeof($aOdds) == 1) {
            return new PropBet($a_iMatchupID, -1, '', ($a_iPosProp == 1 ? $aOdds[0]->getPropOdds() : 0), '', ($a_iPosProp == 2 ? $aOdds[0]->getNegPropOdds() : 0), $a_iPropTypeID, -1, $a_iTeam);
        }
        $iCurrentOddsTotal = 0;
        foreach ($aOdds as $oPropBet) {
            //Check if prop bet should be skipped, i.e. stored as -99999 in database
            if (($a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999) {
                $iSkippedProps++;
            } else {
                $iCurrOdds = $a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds();
                $iCurrentOddsTotal += $iCurrOdds < 0 ? ($iCurrOdds + 100) : ($iCurrOdds - 100);
            }
        }
        if (sizeof($aOdds) - $iSkippedProps != 0) {
            $iCurrentOddsTotal = round($iCurrentOddsTotal / (sizeof($aOdds) - $iSkippedProps) + ($iCurrentOddsTotal < 0 ? -100 : 100));
        }
        return new PropBet($a_iMatchupID, -1, '', ($a_iPosProp == 1 ? $iCurrentOddsTotal : 0), '', ($a_iPosProp == 2 ? $iCurrentOddsTotal : 0), $a_iPropTypeID, -1, $a_iTeam);
    }

    /**
     * Get the openings odds for a specific matchup
     *
     *
     * @param int Matchup ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForMatchup($a_iMatchupID)
    {
        return OddsDB::getOpeningOddsForMatchup($a_iMatchupID);
    }

    /**
     * Get the opening odds for a specified matchup and bookie
     *
     * @param int $a_iMatchupID Matchup ID
     * @param int $a_iBookieID Bookie ID
     * @return FightOdds The opening odds or null if no line was found
     */
    public static function getOpeningOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        return OddsDB::getOpeningOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID);
    }

    /**
     * Get openings odds for a specific prop
     *
     * @param int Matchup ID
     * @param int Proptype ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForProp($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDB::getOpeningOddsForProp($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum);
    }

    /**
     * Get openings odds for a specific prop and bookkie
     *
     * @param int Matchup ID
     * @param int Proptype ID
     * @param int Bookie ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForPropAndBookie($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum)
    {
        return OddsDB::getOpeningOddsForPropAndBookie($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum);
    }

    /**
     * Get all correlations for the specified bookie
     *
     * @param int $a_iBookieID Bookie ID
     * @return array Collection of correlations
     */
    public static function getCorrelationsForBookie($a_iBookieID)
    {
        //TODO: Add call to new function that removes correlations that are not valid anymore

        return OddsDB::getCorrelationsForBookie($a_iBookieID);
    }

    /**
     * Stores a collection of correlations
     *
     * Accepts an array of correlations defined as follows:
     *
     * array('correlation' => xxx, 'matchup_id' => xxx)
     *
     * @param int $a_iBookieID Bookie ID
     * @param array $a_aCorrelations Collection of correlations as defined above
     */
    public static function storeCorrelations($a_iBookieID, $a_aCorrelations)
    {
        return OddsDB::storeCorrelations($a_iBookieID, $a_aCorrelations);
    }


    public static function getMatchupForCorrelation($a_iBookieID, $a_sCorrelation)
    {
        return OddsDB::getMatchupForCorrelation($a_iBookieID, $a_sCorrelation);
    }


    public static function getCompletePropsForMatchup($a_iMatchup, $a_iOffset = 0)
    {
        return OddsDB::getCompletePropsForMatchup($a_iMatchup, $a_iOffset);
    }

    /**
     * Cleans correlations by removing the ones that are not needed anymore. This is determined
     * by checking if the matchup it is associated with is in the past
     */
    public static function cleanCorrelations()
    {
        return OddsDB::cleanCorrelations();
    }

    public static function addEventPropBet($a_oEventPropBet)
    {
        return OddsDB::addEventPropBet($a_oEventPropBet);
    }

    /*public static function getPropBetsForEvent($a_iEventID)
    {
        return OddsDB::getPropBetsForEvent($a_iEventID);
    }*/

    public static function getAllPropTypesForEvent($a_iEventID)
    {
        return OddsDB::getAllPropTypesForEvent($a_iEventID);
    }

    public static function getLatestEventPropOdds($a_iEventID, $a_iBookieID, $a_iPropTypeID, $a_iOffset = 0)
    {
        return OddsDB::getLatestEventPropOdds($a_iEventID, $a_iBookieID, $a_iPropTypeID, $a_iOffset);
    }

    public static function getAllLatestEventPropOddsForEvent($a_iEventID, $a_iPropTypeID, $a_iOffset = 0)
    {
        $aRetOdds = array();

        //Loop through each bookie and retrieve prop odds
        $aBookies = BookieHandler::getAllBookies();
        foreach ($aBookies as $oBookie) {
            $oOdds = OddsDB::getLatestEventPropOdds($a_iEventID, $oBookie->getID(), $a_iPropTypeID, $a_iOffset);
            if ($oOdds != null) {
                $aRetOdds[] = $oOdds;
            }
        }

        return $aRetOdds;
    }


    public static function getBestPropOddsForEvent($a_iEventID, $a_iPropTypeID)
    {
        return OddsDB::getBestPropOddsForEvent($a_iEventID, $a_iPropTypeID);
    }

    public static function getAllPropOddsForEventPropType($a_iEventID, $a_iBookieID, $a_iPropTypeID)
    {
        return OddsDB::getAllPropOddsForEventPropType($a_iEventID, $a_iBookieID, $a_iPropTypeID);
    }

    public static function getPropCountForEvent($a_iEventID)
    {
        return count(OddsDB::getAllPropTypesForEvent($a_iEventID));
    }

    public static function getCurrentEventPropIndex($a_iEventID, $a_iPosProp, $a_iPropTypeID)
    {
        $iSkippedProps = 0; //Keeps track of skipped prop bets that are not available, i.e. stored as -99999 in the database

        $aOdds = OddsHandler::getAllLatestEventPropOddsForEvent($a_iEventID, $a_iPropTypeID);

        if ($aOdds == null || sizeof($aOdds) == 0) {
            return null;
        }
        if (sizeof($aOdds) == 1) {
            return new PropBet($a_iEventID, -1, '', ($a_iPosProp == 1 ? $aOdds[0]->getPropOdds() : 0), '', ($a_iPosProp == 2 ? $aOdds[0]->getNegPropOdds() : 0), $a_iPropTypeID, -1);
        }
        $iCurrentOddsTotal = 0;
        foreach ($aOdds as $oPropBet) {
            //Check if prop bet should be skipped, i.e. stored as -99999 in database
            if (($a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999) {
                $iSkippedProps++;
            } else {
                $iCurrOdds = $a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds();
                $iCurrentOddsTotal += $iCurrOdds < 0 ? ($iCurrOdds + 100) : ($iCurrOdds - 100);
            }
        }
        if (sizeof($aOdds) - $iSkippedProps != 0) {
            $iCurrentOddsTotal = round($iCurrentOddsTotal / (sizeof($aOdds) - $iSkippedProps) + ($iCurrentOddsTotal < 0 ? -100 : 100));
        }
        return new EventPropBet($a_iEventID, -1, '', ($a_iPosProp == 1 ? $iCurrentOddsTotal : 0), '', ($a_iPosProp == 2 ? $iCurrentOddsTotal : 0), $a_iPropTypeID, -1);
    }


    public static function getOpeningOddsForEventProp($a_iEventID, $a_iPropTypeID)
    {
        return OddsDB::getOpeningOddsForEventProp($a_iEventID, $a_iPropTypeID);
    }

    public static function getOpeningOddsForEventPropAndBookie($a_iEventID, $a_iPropTypeID, $a_iBookieID)
    {
        return OddsDB::getOpeningOddsForEventPropAndBookie($a_iEventID, $a_iPropTypeID, $a_iBookieID);
    }

    public static function getCompletePropsForEvent($a_iEventID, $a_iOffset = 0, $a_iBookieID = null)
    {
        return OddsDB::getCompletePropsForEvent($a_iEventID, $a_iOffset, $a_iBookieID);
    }

    public static function checkMatchingEventPropOdds($a_oEventPropBet)
    {
        $oExistingPropOdds = OddsHandler::getLatestEventPropOdds($a_oEventPropBet->getEventID(), $a_oEventPropBet->getBookieID(), $a_oEventPropBet->getPropTypeID());
        if ($oExistingPropOdds != null) {
            return $oExistingPropOdds->equals($a_oEventPropBet);
        }
        return false;
    }

    public static function removeOddsForMatchupAndBookie($matchup_id, $bookie_id)
    {
        if (!is_int($matchup_id) || !is_int($bookie_id)) {
            return false;
        }
        //First we remove all prop odds so that we don't leave any orphans
        OddsHandler::removePropOddsForMatchupAndBookie($matchup_id, $bookie_id);
        //We also remove any flags related to this matchup
        OddsHandler::removeFlagged($bookie_id, $matchup_id);
        return OddsDB::removeOddsForMatchupAndBookie($matchup_id, $bookie_id);
    }

    public static function removePropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id = null, $team_num = null)
    {
        if (!is_numeric($matchup_id) || !is_numeric($bookie_id)) {
            return false;
        }
        OddsHandler::removeFlagged($bookie_id, $matchup_id, null, $proptype_id, $team_num);
        return OddsDB::removePropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function removePropOddsForEventAndBookie($event_id, $bookie_id, $proptype_id = null)
    {
        if (!is_numeric($event_id) || !is_numeric($bookie_id)) {
            return false;
        }
        OddsHandler::removeFlagged($bookie_id, null, $event_id, $proptype_id);
        return OddsDB::removePropOddsForEventAndBookie($event_id, $bookie_id, $proptype_id);
    }

    public static function getAllLatestPropOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID, $a_iPropTypeID = -1)
    {
        if (!is_numeric($a_iMatchupID) || !is_numeric($a_iBookieID) || !is_numeric($a_iPropTypeID)) {
            return false;
        }
        return OddsDB::getAllLatestPropOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID, $a_iPropTypeID);
    }

    public static function flagMatchupOddsForDeletion($a_iBookieID, $a_iMatchupID)
    {
        if (
            !is_numeric($a_iMatchupID) || !is_numeric($a_iBookieID)
            || $a_iMatchupID <= 0 || $a_iBookieID <= 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($a_iBookieID, $a_iMatchupID, null, null, null);
    }

    public static function flagPropOddsForDeletion($a_iBookieID, $a_iMatchupID, $a_iPropTypeID, $a_iTeamNum)
    {
        if (
            !is_numeric($a_iMatchupID) || !is_numeric($a_iBookieID) || !is_numeric($a_iPropTypeID) || !is_numeric($a_iTeamNum)
            || $a_iMatchupID <= 0 || $a_iBookieID <= 0 || $a_iPropTypeID <= 0 || $a_iTeamNum < 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($a_iBookieID, $a_iMatchupID, null, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function flagEventPropOddsForDeletion($a_iBookieID, $a_iEventID, $a_iPropTypeID, $a_iTeamNum)
    {
        if (
            !is_numeric($a_iEventID) || !is_numeric($a_iBookieID) || !is_numeric($a_iPropTypeID) || !is_numeric($a_iTeamNum)
            || $a_iEventID <= 0 || $a_iBookieID <= 0 || $a_iPropTypeID <= 0 || $a_iTeamNum < 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($a_iBookieID, null, $a_iEventID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function checkIfFlagged($a_iBookieID, $a_iMatchupID, $a_iEventID, $a_iPropTypeID, $a_iTeamNum)
    {
        if (
            !is_numeric($a_iMatchupID) || !is_numeric($a_iEventID) || !is_numeric($a_iBookieID) || !is_numeric($a_iPropTypeID) || !is_numeric($a_iTeamNum)
            || $a_iBookieID <= 0
        ) {
            return false;
        }
        return OddsDB::checkIfFlagged($a_iBookieID, $a_iMatchupID, $a_iEventID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function removeFlagged($a_iBookieID, $a_iMatchupID = null, $a_iEventID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        if (
            !is_numeric($a_iBookieID) || $a_iBookieID <= 0
            || (!$a_iMatchupID && !$a_iEventID && !$a_iPropTypeID && !$a_iTeamNum)
        ) { //If all is null we don't proceed
            return false;
        }
        return OddsDB::removeFlagged($a_iBookieID, $a_iMatchupID, $a_iEventID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function removeAllOldFlagged()
    {
        return OddsDB::removeAllOldFlagged();
    }

    public static function getAllFlaggedMatchups()
    {
        $results = OddsDB::getAllFlaggedMatchups();
        $ret = [];
        foreach ($results as $row) {
            $ret_row = $row;
            $ret_row['fight_obj'] = new Fight($row['id'], $row['team1_name'], $row['team2_name'], $row['event_id']);
            $ret_row['event_obj'] = new Event($row['event_id'], $row['event_date'], $row['event_name']);
            $ret[] = $ret_row;
        }
        return $ret;
    }

    public static function deleteFlaggedOdds()
    {
        $flagged_col = OddsDB::getFlaggedOddsForDeletion();

        //Log to common audit log file
        $logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);

        //Iterate each type and delete
        foreach ($flagged_col['matchup_odds'] as $flagged) {
            $result = OddsHandler::removeOddsForMatchupAndBookie((int) $flagged['matchup_id'], (int) $flagged['bookie_id']);
            if ($result > 0) {
                $logger->info('Deleted odds for ' . ucwords(strtolower($flagged['team1_name'])) . ' vs ' . ucwords(strtolower($flagged['team2_name'])) . ' (' . $flagged['matchup_id'] . ') at ' . $flagged['event_name'] . ' for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . '). Odds removed: ' . $result);
            } else {
                $logger->error('Error deleting odds for ' . ucwords(strtolower($flagged['team1_name'])) . ' vs ' . ucwords(strtolower($flagged['team2_name'])) . ' (' . $flagged['matchup_id'] . ') at ' . $flagged['event_name'] . ' for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . ')');
            }
        }

        foreach ($flagged_col['prop_odds'] as $flagged) {
            $result = OddsHandler::removePropOddsForMatchupAndBookie((int) $flagged['matchup_id'], (int) $flagged['bookie_id'], (int) $flagged['proptype_id'], (int) $flagged['team_num']);
            if ($result > 0) {
                $logger->info('Deleted prop odds for ' . ucwords(strtolower($flagged['team1_name'])) . ' vs ' . ucwords(strtolower($flagged['team2_name'])) . ' (' . $flagged['matchup_id'] . '), prop ' . (str_replace('<', '&#60;', str_replace('>', '&#62;', $flagged['prop_desc']))) . ' (' . $flagged['proptype_id'] . '), team_num ' .  $flagged['team_num'] . ' for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . '). Odds removed: ' . $result);
            } else {
                $logger->info('Unable to delete prop odds for ' . ucwords(strtolower($flagged['team1_name'])) . ' vs ' . ucwords(strtolower($flagged['team2_name'])) . ' (' . $flagged['matchup_id'] . '), prop ' . (str_replace('<', '&#60;', str_replace('>', '&#62;', $flagged['prop_desc']))) . ' (' . $flagged['proptype_id'] . '), team_num ' .  $flagged['team_num'] . ' for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . '). Probably due to deleted matchup');
            }
        }
        foreach ($flagged_col['event_prop_odds'] as $flagged) {
            $result = OddsHandler::removePropOddsForEventAndBookie((int) $flagged['event_id'], (int) $flagged['bookie_id'], (int) $flagged['proptype_id']);
            if ($result > 0) {
                $logger->info('Deleted event prop odds for ' . $flagged['event_name'] . ' (' . $flagged['event_id'] . '), prop ' . (str_replace('<', '&#60;', str_replace('>', '&#62;', $flagged['prop_desc']))) . ' (' . $flagged['proptype_id'] . ') for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . '). Odds removed: ' . $result);
            } else {
                $logger->error('Error deleting event prop odds for ' . $flagged['event_name'] . ' (' . $flagged['event_id'] . '), prop ' . (str_replace('<', '&#60;', str_replace('>', '&#62;', $flagged['prop_desc']))) . ' (' . $flagged['proptype_id'] . ') for ' . $flagged['bookie_name'] . ' (' . $flagged['bookie_id'] . ')');
            }
        }

        return count($flagged_col['matchup_odds']) + count($flagged_col['prop_odds']) + count($flagged_col['event_prop_odds']);
    }

    public static function getLatestPropOddsV2($a_iEventID = null, $a_iMatchupID = null, $a_iBookieID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        $result = OddsDB::getLatestPropOddsV2($a_iEventID, $a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);

        $return = [];
        //Move result into multidimensional array
        foreach ($result as $row) {
            //This segment initializes a key if not set before
            if (!isset($return[$row['event_id']])) {
                $return[$row['event_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['matchup_id']])) {
                $return[$row['event_id']][$row['matchup_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['matchup_id']][$row['proptype_id']])) {
                $return[$row['event_id']][$row['matchup_id']][$row['proptype_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['matchup_id']][$row['proptype_id']][$row['team_num']])) {
                $return[$row['event_id']][$row['matchup_id']][$row['proptype_id']][$row['team_num']] = [];
            }
            if (!isset($return[$row['event_id']][$row['matchup_id']][$row['proptype_id']][$row['team_num']][$row['bookie_id']])) {
                $return[$row['event_id']][$row['matchup_id']][$row['proptype_id']][$row['team_num']][$row['bookie_id']] = [];
            }

            $prop_obj = new PropBet(
                $row['matchup_id'],
                $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                $row['proptype_id'],
                $row['date'],
                $row['team_num']
            );

            $return[$row['event_id']][$row['matchup_id']][$row['proptype_id']][$row['team_num']][$row['bookie_id']] =
                [
                    'odds_obj' =>    $prop_obj,
                    'previous_prop_odds' => $row['previous_prop_odds'],
                    'previous_negprop_odds' => $row['previous_negprop_odds']
                ];
        }
        return $return;
    }

    public static function getLatestEventPropOddsV2($a_iEventID = null, $a_iMatchupID = null, $a_iBookieID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        $result = OddsDB::getLatestEventPropOddsV2($a_iEventID, $a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);

        $return = [];
        //Move result into multidimensional array
        foreach ($result as $row) {
            //This segment initializes a key if not set before
            if (!isset($return[$row['event_id']])) {
                $return[$row['event_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['proptype_id']])) {
                $return[$row['event_id']][$row['proptype_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['proptype_id']][$row['bookie_id']])) {
                $return[$row['event_id']][$row['proptype_id']][$row['bookie_id']] = [];
            }

            $prop_obj = new EventPropBet(
                $row['event_id'],
                $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                $row['proptype_id'],
                $row['date']
            );

            $return[$row['event_id']][$row['proptype_id']][$row['bookie_id']] =
                [
                    'odds_obj' =>    $prop_obj,
                    'previous_prop_odds' => $row['previous_prop_odds'],
                    'previous_negprop_odds' => $row['previous_negprop_odds']
                ];
        }
        return $return;
    }

    public static function getLatestMatchupOddsV2($a_iEventID = null, $a_iMatchupID = null)
    {
        $result = OddsDB::getLatestMatchupOddsV2($a_iEventID, $a_iMatchupID);

        $return = [];
        //Move result into multidimensional array
        foreach ($result as $row) {
            //This segment initializes a key if not set before
            if (!isset($return[$row['event_id']])) {
                $return[$row['event_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['fight_id']])) {
                $return[$row['event_id']][$row['fight_id']] = [];
            }
            if (!isset($return[$row['event_id']][$row['fight_id']][$row['bookie_id']])) {
                $return[$row['event_id']][$row['fight_id']][$row['bookie_id']] = [];
            }


            $fo_obj = new FightOdds($row['fight_id'], $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);

            $return[$row['event_id']][$row['fight_id']][$row['bookie_id']] =
                [
                    'odds_obj' => $fo_obj,
                    'previous_team1_odds' => $row['previous_team1_odds'],
                    'previous_team2_odds' => $row['previous_team2_odds']
                ];
        }
        return $return;
    }

    public static function getEventViewData($a_iEventID)
    {
        if ($a_iEventID == null || !is_numeric($a_iEventID)) {
            return false;
        }

        $view_data = [];

        $event = EventHandler::getEvent((int) $a_iEventID);

        $matchups = EventHandler::getAllFightsForEvent($event->getID(), true);

        //Convert matchups array to associative
        $matchups_assoc = [];
        foreach ($matchups as $matchup) {
            $matchups_assoc[$matchup->getID()] = $matchup;
        }

        $prop_odds = OddsHandler::getLatestPropOddsV2($event->getID());
        $matchup_odds = OddsHandler::getLatestMatchupOddsV2($event->getID());
        $event_prop_odds = OddsHandler::getLatestEventPropOddsV2($event->getID());

        foreach ($matchup_odds as &$event_entry) {
            foreach ($event_entry as &$matchup_entry) {
                $best_odds_reflist1 = [];
                $best_odds_reflist2 = [];
                foreach ($matchup_entry as $odds_key => $bookie_odds) {
                    //Indicate which line is best on both sides
                    if (count($best_odds_reflist1) == 0) {
                        $best_odds_reflist1[] = $odds_key;
                    } elseif ($bookie_odds['odds_obj']->getOdds(1) > $matchup_entry[$best_odds_reflist1[0]]['odds_obj']->getOdds(1)) {
                        $best_odds_reflist1 = [$odds_key];
                    } elseif ($bookie_odds['odds_obj']->getOdds(1) == $matchup_entry[$best_odds_reflist1[0]]['odds_obj']->getOdds(1)) {
                        $best_odds_reflist1[] = $odds_key;
                    }
                    if (count($best_odds_reflist2) == 0) {
                        $best_odds_reflist2[] = $odds_key;
                    } elseif ($bookie_odds['odds_obj']->getOdds(2) > $matchup_entry[$best_odds_reflist2[0]]['odds_obj']->getOdds(2)) {
                        $best_odds_reflist2 = [$odds_key];
                    } elseif ($bookie_odds['odds_obj']->getOdds(2) == $matchup_entry[$best_odds_reflist2[0]]['odds_obj']->getOdds(2)) {
                        $best_odds_reflist2[] = $odds_key;
                    }
                }
                foreach ($best_odds_reflist1 as $bookie_key) {
                    $matchup_entry[$bookie_key]['is_best_team1'] = true;
                }
                foreach ($best_odds_reflist2 as $bookie_key) {
                    $matchup_entry[$bookie_key]['is_best_team2'] = true;
                }
            }
        }

        //Loop through prop odds and count the number of props available for each matchup
        $view_data['matchup_prop_count'] = [];
        foreach ($prop_odds as &$event_entry) {
            foreach ($event_entry as $matchup_key => &$matchup_entry) {
                foreach ($matchup_entry as &$proptype_entry) {
                    foreach ($proptype_entry as $team_num_key => &$team_num_entry) {

                        //Count entries per matchup
                        if (!isset($view_data['matchup_prop_count'][$matchup_key])) {
                            $view_data['matchup_prop_count'][$matchup_key] = 0;
                        }
                        $view_data['matchup_prop_count'][$matchup_key]++;

                        $best_odds_reflist1 = [];
                        $best_odds_reflist2 = [];
                        foreach ($team_num_entry as $odds_key => $bookie_odds) {
                            //Indicate which line is best on both sides
                            if (count($best_odds_reflist1) == 0) {
                                $best_odds_reflist1[] = $odds_key;
                            } elseif ($bookie_odds['odds_obj']->getPropOdds() > $team_num_entry[$best_odds_reflist1[0]]['odds_obj']->getPropOdds()) {
                                $best_odds_reflist1 = [$odds_key];
                            } elseif ($bookie_odds['odds_obj']->getPropOdds() == $team_num_entry[$best_odds_reflist1[0]]['odds_obj']->getPropOdds()) {
                                $best_odds_reflist1[] = $odds_key;
                            }
                            if (count($best_odds_reflist2) == 0) {
                                $best_odds_reflist2[] = $odds_key;
                            } elseif ($bookie_odds['odds_obj']->getNegPropOdds() > $team_num_entry[$best_odds_reflist2[0]]['odds_obj']->getNegPropOdds()) {
                                $best_odds_reflist2 = [$odds_key];
                            } elseif ($bookie_odds['odds_obj']->getNegPropOdds() == $team_num_entry[$best_odds_reflist2[0]]['odds_obj']->getNegPropOdds()) {
                                $best_odds_reflist2[] = $odds_key;
                            }

                            //If fight has changed order in database we must switch team nums
                            $temp_team_num_key = $team_num_key;
                            if (isset($matchups_assoc[$matchup_key]) && $matchups_assoc[$matchup_key]->hasOrderChanged()) {
                                if ($team_num_key == 1) {
                                    $temp_team_num_key = 2;
                                } elseif ($team_num_key == 2) {
                                    $temp_team_num_key = 1;
                                }
                            }

                            //Adjust prop name description
                            $prop_desc = $bookie_odds['odds_obj']->getPropName();
                            $prop_desc = str_replace(
                                ['<T>', '<T2>'],
                                [
                                    $matchups_assoc[$matchup_key]->getTeamLastNameAsString($temp_team_num_key),
                                    $matchups_assoc[$matchup_key]->getTeamLastNameAsString(($temp_team_num_key % 2) + 1)
                                ],
                                $prop_desc
                            );
                            $prop_desc = $bookie_odds['odds_obj']->setPropName($prop_desc);

                            $prop_desc = $bookie_odds['odds_obj']->getNegPropName();
                            $prop_desc = str_replace(
                                ['<T>', '<T2>'],
                                [
                                    $matchups_assoc[$matchup_key]->getTeamLastNameAsString($temp_team_num_key),
                                    $matchups_assoc[$matchup_key]->getTeamLastNameAsString(($temp_team_num_key % 2) + 1)
                                ],
                                $prop_desc
                            );
                            $prop_desc = $bookie_odds['odds_obj']->setNegPropName($prop_desc);
                        }

                        foreach ($best_odds_reflist1 as $bookie_key) {
                            $team_num_entry[$bookie_key]['is_best_pos'] = true;
                        }
                        foreach ($best_odds_reflist2 as $bookie_key) {
                            $team_num_entry[$bookie_key]['is_best_neg'] = true;
                        }
                    }
                }
            }
        }

        //Loop through event prop odds and count the number of props available for each matchup
        $view_data['event_prop_count'] = 0;
        foreach ($event_prop_odds as &$event_entry) {
            foreach ($event_entry as &$proptype_entry) {
                $view_data['event_prop_count']++;

                $best_odds_reflist1 = [];
                $best_odds_reflist2 = [];
                foreach ($proptype_entry as $odds_key => $bookie_odds) {
                    //Indicate which line is best on both sides
                    if (count($best_odds_reflist1) == 0) {
                        $best_odds_reflist1[] = $odds_key;
                    } elseif ($bookie_odds['odds_obj']->getPropOdds() > $proptype_entry[$best_odds_reflist1[0]]['odds_obj']->getPropOdds()) {
                        $best_odds_reflist1 = [$odds_key];
                    } elseif ($bookie_odds['odds_obj']->getPropOdds() == $proptype_entry[$best_odds_reflist1[0]]['odds_obj']->getPropOdds()) {
                        $best_odds_reflist1[] = $odds_key;
                    }
                    if (count($best_odds_reflist2) == 0) {
                        $best_odds_reflist2[] = $odds_key;
                    } elseif ($bookie_odds['odds_obj']->getNegPropOdds() > $proptype_entry[$best_odds_reflist2[0]]['odds_obj']->getNegPropOdds()) {
                        $best_odds_reflist2 = [$odds_key];
                    } elseif ($bookie_odds['odds_obj']->getNegPropOdds() == $proptype_entry[$best_odds_reflist2[0]]['odds_obj']->getNegPropOdds()) {
                        $best_odds_reflist2[] = $odds_key;
                    }
                }
                foreach ($best_odds_reflist1 as $bookie_key) {
                    $proptype_entry[$bookie_key]['is_best_pos'] = true;
                }
                foreach ($best_odds_reflist2 as $bookie_key) {
                    $proptype_entry[$bookie_key]['is_best_neg'] = true;
                }
            }
        }

        $view_data['event'] = $event;

        $view_data['matchups'] = $matchups;
        $view_data['prop_odds'] = $prop_odds;
        $view_data['matchup_odds'] = $matchup_odds;
        $view_data['event_prop_odds'] = $event_prop_odds;

        return $view_data;
    }
}
