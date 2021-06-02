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

    public static function getPropBetsForMatchup($matchup_id)
    {
        return OddsDB::getPropBetsForMatchup($matchup_id);
    }

    public static function getAllPropTypesForMatchup($matchup_id): array
    {
        return OddsDB::getAllPropTypesForMatchup($matchup_id);
    }

    public static function getAllPropTypes()
    {
        return OddsHandler::getPropTypes();
    }

    public static function getPropTypes(int $proptype_id = null): array
    {
        return OddsDB::getPropTypes($proptype_id);
    }

    public static function checkMatchingPropOdds($propbet_obj)
    {
        $existing_prop_odds = OddsHandler::getLatestPropOdds($propbet_obj->getMatchupID(), $propbet_obj->getBookieID(), $propbet_obj->getPropTypeID(), $propbet_obj->getTeamNumber());
        if ($existing_prop_odds != null) {
            return $existing_prop_odds->equals($propbet_obj);
        }
        return false;
    }

    public static function getLatestPropOdds($matchup_id, $bookie_id, $proptype_id, $team_num)
    {
        return OddsDB::getLatestPropOdds($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function getAllLatestPropOddsForMatchup($matchup_id, $proptype_id, $a_iTeam = 0, $a_iOffset = 0)
    {
        $aRetOdds = [];

        //Loop through each bookie and retrieve prop odds
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $oBookie) {
            $oOdds = OddsDB::getLatestPropOdds($matchup_id, $oBookie->getID(), $proptype_id, $a_iTeam, $a_iOffset);
            if ($oOdds != null) {
                $aRetOdds[] = $oOdds;
            }
        }

        return $aRetOdds;
    }

    public static function getBestPropOddsForMatchup($matchup_id, $proptype_id, $team_num)
    {
        return OddsDB::getBestPropOddsForMatchup($matchup_id, $proptype_id, $team_num);
    }

    public static function getAllPropOddsForMatchupPropType($matchup_id, $a_iBookieID, $proptype_id, $team_num): array
    {
        return OddsDB::getAllPropOddsForMatchupPropType($matchup_id, $a_iBookieID, $proptype_id, $team_num);
    }

    public static function getPropCountForMatchup($matchup_id)
    {
        return count(OddsDB::getAllPropTypesForMatchup($matchup_id));
    }

    /* Gets the average value across multiple bookies for specific prop type */
    public static function getCurrentPropIndex($matchup_id, $prop_side, $proptype_id, $team_num)
    {
        $skipped_props = 0; //Keeps track of skipped prop bets that are not available, i.e. stored as -99999 in the database

        if ($team_num > 2 || $team_num < 0) {
            return null;
        }

        $odds = OddsHandler::getAllLatestPropOddsForMatchup($matchup_id, $proptype_id, $team_num);

        if ($odds == null || sizeof($odds) == 0) {
            return null;
        }
        if (sizeof($odds) == 1) {
            return new PropBet($matchup_id, -1, '', ($prop_side == 1 ? $odds[0]->getPropOdds() : 0), '', ($prop_side == 2 ? $odds[0]->getNegPropOdds() : 0), $proptype_id, -1, $team_num);
        }
        $odds_total = 0;
        foreach ($odds as $oPropBet) {
            //Check if prop bet should be skipped, i.e. stored as -99999 in database
            if (($prop_side == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999) {
                $skipped_props++;
            } else {
                $cur_odds = $prop_side == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds();
                $odds_total += $cur_odds < 0 ? ($cur_odds + 100) : ($cur_odds - 100);
            }
        }
        if (sizeof($odds) - $skipped_props != 0) {
            $odds_total = round($odds_total / (sizeof($odds) - $skipped_props) + ($odds_total < 0 ? -100 : 100));
        }
        return new PropBet($matchup_id, -1, '', ($prop_side == 1 ? $odds_total : 0), '', ($prop_side == 2 ? $odds_total : 0), $proptype_id, -1, $team_num);
    }

    public static function getOpeningOddsForMatchup(int $matchup_id): ?FightOdds
    {
        return OddsDB::getOpeningOddsForMatchup($matchup_id);
    }

    public static function getOpeningOddsForProp(int $matchup_id, int $proptype_id, int $team_num): ?PropBet
    {
        return OddsDB::getOpeningOddsForProp($matchup_id, $proptype_id, $team_num);
    }

    public static function getOpeningOddsForPropAndBookie(int $matchup_id, int $proptype_id, int $bookie_id, int $team_num): ?PropBet
    {
        return OddsDB::getOpeningOddsForPropAndBookie($matchup_id, $proptype_id, $bookie_id, $team_num);
    }

    public static function getCorrelationsForBookie(int $bookie_id): array
    {
        return OddsDB::getCorrelationsForBookie($bookie_id);
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
    public static function storeCorrelations($bookie_id, $correlations_col)
    {
        return OddsDB::storeCorrelations($bookie_id, $correlations_col);
    }


    public static function getMatchupForCorrelation($bookie_id, $correlation)
    {
        return OddsDB::getMatchupForCorrelation($bookie_id, $correlation);
    }


    public static function getCompletePropsForMatchup($matchup_id, $offset = 0)
    {
        return OddsDB::getCompletePropsForMatchup($matchup_id, $offset);
    }

    /**
     * Cleans correlations by removing the ones that are not needed anymore. This is determined
     * by checking if the matchup it is associated with is in the past
     */
    public static function cleanCorrelations()
    {
        return OddsDB::cleanCorrelations();
    }

    public static function addEventPropBet(EventPropBet $event_prop_bet)
    {
        return OddsDB::addEventPropBet($event_prop_bet);
    }

    /*public static function getPropBetsForEvent($a_iEventID)
    {
        return OddsDB::getPropBetsForEvent($a_iEventID);
    }*/

    public static function getAllPropTypesForEvent($event_id)
    {
        return OddsDB::getAllPropTypesForEvent($event_id);
    }

    public static function getLatestEventPropOdds($event_id, $bookie_id, $proptype_id, $offset = 0)
    {
        return OddsDB::getLatestEventPropOdds($event_id, $bookie_id, $proptype_id, $offset);
    }

    public static function getAllLatestEventPropOddsForEvent($event_id, $proptype_id, $offset = 0)
    {
        $return_odds = [];

        //Loop through each bookie and retrieve prop odds
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie) {
            $odds = OddsDB::getLatestEventPropOdds($event_id, $bookie->getID(), $proptype_id, $offset);
            if ($odds != null) {
                $return_odds[] = $odds;
            }
        }

        return $return_odds;
    }

    public static function getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id): array
    {
        return OddsDB::getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id);
    }

    public static function getCurrentEventPropIndex($event_id, $prop_side, $proptype_id)
    {
        $skipped_props = 0; //Keeps track of skipped prop bets that are not available, i.e. stored as -99999 in the database

        $odds = OddsHandler::getAllLatestEventPropOddsForEvent($event_id, $proptype_id);

        if ($odds == null || sizeof($odds) == 0) {
            return null;
        }
        if (sizeof($odds) == 1) {
            return new PropBet($event_id, -1, '', ($prop_side == 1 ? $odds[0]->getPropOdds() : 0), '', ($prop_side == 2 ? $odds[0]->getNegPropOdds() : 0), $proptype_id, -1);
        }
        $total = 0;
        foreach ($odds as $propbet_obj) {
            //Check if prop bet should be skipped, i.e. stored as -99999 in database
            if (($prop_side == 1 ? $propbet_obj->getPropOdds() : $propbet_obj->getNegPropOdds()) == -99999) {
                $skipped_props++;
            } else {
                $current_odds = $prop_side == 1 ? $propbet_obj->getPropOdds() : $propbet_obj->getNegPropOdds();
                $total += $current_odds < 0 ? ($current_odds + 100) : ($current_odds - 100);
            }
        }
        if (sizeof($odds) - $skipped_props != 0) {
            $total = round($total / (sizeof($odds) - $skipped_props) + ($total < 0 ? -100 : 100));
        }
        return new EventPropBet($event_id, -1, '', ($prop_side == 1 ? $total : 0), '', ($prop_side == 2 ? $total : 0), $proptype_id, -1);
    }


    public static function getOpeningOddsForEventProp($event_id, $proptype_id)
    {
        return OddsDB::getOpeningOddsForEventProp($event_id, $proptype_id);
    }

    public static function getOpeningOddsForEventPropAndBookie($event_id, $proptype_id, $bookie_id)
    {
        return OddsDB::getOpeningOddsForEventPropAndBookie($event_id, $proptype_id, $bookie_id);
    }

    public static function getCompletePropsForEvent($event_id, $offset = 0, $bookie_id = null)
    {
        return OddsDB::getCompletePropsForEvent($event_id, $offset, $bookie_id);
    }

    public static function checkMatchingEventPropOdds($event_propbet_obj)
    {
        $existing_odds = OddsHandler::getLatestEventPropOdds($event_propbet_obj->getEventID(), $event_propbet_obj->getBookieID(), $event_propbet_obj->getPropTypeID());
        if ($existing_odds != null) {
            return $existing_odds->equals($event_propbet_obj);
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

    public static function getAllLatestPropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id = -1)
    {
        if (!is_numeric($matchup_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id)) {
            return false;
        }
        return OddsDB::getAllLatestPropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id);
    }

    public static function flagMatchupOddsForDeletion($bookie_id, $matchup_id)
    {
        if (
            !is_numeric($matchup_id) || !is_numeric($bookie_id)
            || $matchup_id <= 0 || $bookie_id <= 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, $matchup_id, null, null, null);
    }

    public static function flagPropOddsForDeletion($bookie_id, $matchup_id, $proptype_id, $team_num)
    {
        if (
            !is_numeric($matchup_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id) || !is_numeric($team_num)
            || $matchup_id <= 0 || $bookie_id <= 0 || $proptype_id <= 0 || $team_num < 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, $matchup_id, null, $proptype_id, $team_num);
    }

    public static function flagEventPropOddsForDeletion($bookie_id, $event_id, $proptype_id, $team_num)
    {
        if (
            !is_numeric($event_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id) || !is_numeric($team_num)
            || $event_id <= 0 || $bookie_id <= 0 || $proptype_id <= 0 || $team_num < 0
        ) {
            return false;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, null, $event_id, $proptype_id, $team_num);
    }

    public static function checkIfFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num)
    {
        if (
            !is_numeric($matchup_id) || !is_numeric($event_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id) || !is_numeric($team_num)
            || $bookie_id <= 0
        ) {
            return false;
        }
        return OddsDB::checkIfFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num);
    }

    public static function removeFlagged($bookie_id, $matchup_id = null, $event_id = null, $proptype_id = null, $team_num = null): int
    {
        if (
            !is_numeric($bookie_id) || $bookie_id <= 0
            || (!$matchup_id && !$event_id && !$proptype_id && !$team_num)
        ) { //If no value is specified we abort
            return false;
        }
        return OddsDB::removeFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num);
    }

    public static function removeAllOldFlagged(): int
    {
        return OddsDB::removeAllOldFlagged();
    }

    public static function getAllFlaggedMatchups()
    {
        $results = OddsDB::getAllFlaggedMatchups();
        $ret = [];
        foreach ($results as $row) {
            $ret_row = $row;
            $ret_row['fight_obj'] = new Fight((int) $row['id'], $row['team1_name'], $row['team2_name'], (int) $row['event_id']);
            $ret_row['event_obj'] = new Event((int) $row['event_id'], $row['event_date'], $row['event_name']);
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
                (int) $row['event_id'],
                (int) $row['bookie_id'],
                $row['prop_desc'],
                $row['prop_odds'],
                $row['negprop_desc'],
                $row['negprop_odds'],
                (int) $row['proptype_id'],
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


            $fo_obj = new FightOdds((int) $row['fight_id'], (int) $row['bookie_id'], $row['fighter1_odds'], $row['fighter2_odds'], $row['date']);

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

        $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);

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
