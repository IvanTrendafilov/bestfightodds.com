<?php

namespace BFO\General;

use BFO\DB\OddsDB;
use BFO\General\BookieHandler;
use BFO\General\EventHandler;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropBet;
use BFO\DataTypes\EventPropBet;
use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;

/**
 * Logic to handle storage, retrieval and matching of odds, including prop bets
 */
class OddsHandler
{
    public static function addPropBet(PropBet $propbet_obj): bool
    {
        if (
            $propbet_obj->getPropOdds() == '' || $propbet_obj->getNegPropOdds() == ''
            || (intval($propbet_obj->getPropOdds()) > 0 && intval($propbet_obj->getNegPropOdds()) > 0)
        ) { //Ignore odds that are positive on both ends (should indicate an incorrect line)
            return false;
        }
        return OddsDB::addPropBet($propbet_obj);
    }

    /**
     * Checks if the exact same fight odds for the operator and fight exist.
     *
     * @param FightOdds object to look for (date is not checked).
     * @return true if the exact odds exists and false if it doesn't.
     */
    public static function checkMatchingOdds(FightOdds $odds_obj): bool
    {
        $found_odds_obj = OddsHandler::getLatestOddsForFightAndBookie($odds_obj->getFightID(), $odds_obj->getBookieID());
        if ($found_odds_obj != null) {
            return $found_odds_obj->equals($odds_obj);
        }
        return false;
    }

    public static function addNewFightOdds(FightOdds $odds_obj): ?int
    {
        //Validate input
        if (
            empty($odds_obj->getFightID()) || !is_numeric($odds_obj->getFightID()) ||
            empty($odds_obj->getBookieID()) || !is_numeric($odds_obj->getBookieID()) ||
            empty($odds_obj->getOdds(1)) || !is_numeric($odds_obj->getOdds(1)) ||
            empty($odds_obj->getOdds(2)) || !is_numeric($odds_obj->getOdds(2))
        ) {
            return false;
        }
        //Validate that odds is not in range -99 => +99
        if (
            (intval($odds_obj->getOdds(1)) >= -99 && intval($odds_obj->getOdds(1) <= 99)) ||
            (intval($odds_obj->getOdds(2)) >= -99 && intval($odds_obj->getOdds(2) <= 99))
        ) {
            return false;
        }

        //Validate that odds is not positive on both sides (=surebet, most likely invalid)
        if (
            intval($odds_obj->getOdds(1)) >= 0 && intval($odds_obj->getOdds(2) >= 0)
        ) {
            return false;
        }

        return OddsDB::addNewFightOdds($odds_obj);
    }

    /**
     * Gets all latest odds for a fight.
     * If the second parameter is specified it is possible to jump to historic
     * odds, for example getting the previous odds and comparing to the current
     */
    public static function getAllLatestOddsForFight(int $fight_id, int $historic_offset = 0): array
    {
        return OddsDB::getAllLatestOddsForFight($fight_id, $historic_offset);
    }

    public static function getLatestOddsForFightAndBookie(int $fight_id, int $bookie_id): ?FightOdds
    {
        return OddsDB::getLatestOddsForFightAndBookie($fight_id, $bookie_id);
    }

    public static function getAllOdds(int $matchup_id, int $bookie_id = null): ?array
    {
        $odds = OddsDB::getAllOdds($matchup_id, $bookie_id);
        if (sizeof($odds) > 0) {
            return $odds;
        }
        return null;
    }

    public static function getLatestChangeDate(int $event_id): string
    {
        return OddsDB::getLatestChangeDate($event_id);
    }

    public static function getCurrentOddsIndex(int $matchup_id, int $team_no): ?FightOdds
    {
        if ($team_no > 2 || $team_no < 1) {
            return null;
        }

        $odds_col = OddsHandler::getAllLatestOddsForFight($matchup_id);

        if ($odds_col == null || sizeof($odds_col) == 0) {
            return null;
        }
        if (sizeof($odds_col) == 1) {
            return new FightOdds((int) $matchup_id, -1, ($team_no == 1 ? $odds_col[0]->getOdds($team_no) : 0), ($team_no == 2 ? $odds_col[0]->getOdds($team_no) : 0), -1);
        }
        $odds_total = 0;
        foreach ($odds_col as $odds_obj) {
            $current_odds = $odds_obj->getOdds($team_no);
            $odds_total += $current_odds < 0 ? ($current_odds + 100) : ($current_odds - 100);
        }
        $odds_total = round($odds_total / sizeof($odds_col) + ($odds_total < 0 ? -100 : 100));

        return new FightOdds(
            (int) $matchup_id,
            -1,
            ($team_no == 1 ? $odds_total : 0),
            ($team_no == 2 ? $odds_total : 0),
            -1
        );
    }

    public static function getBestOddsForFight(int $matchup_id): ?FightOdds
    {
        return OddsDB::getBestOddsForFight($matchup_id);
    }

    public static function checkMatchingPropOdds(PropBet $propbet_obj): bool
    {
        $existing_prop_odds = OddsHandler::getLatestPropOdds($propbet_obj->getMatchupID(), $propbet_obj->getBookieID(), $propbet_obj->getPropTypeID(), $propbet_obj->getTeamNumber());
        if ($existing_prop_odds != null) {
            return $existing_prop_odds->equals($propbet_obj);
        }
        return false;
    }

    public static function getLatestPropOdds(int $matchup_id, int $bookie_id, int $proptype_id, int $team_num): ?PropBet
    {
        return OddsDB::getLatestPropOdds($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function getAllLatestPropOddsForMatchup(int $matchup_id, int $proptype_id, int $team_num = 0, int $offset = 0)
    {
        $odds_col = [];

        //Loop through each bookie and retrieve prop odds
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $oBookie) {
            $odds_obj = OddsDB::getLatestPropOdds($matchup_id, $oBookie->getID(), $proptype_id, $team_num, $offset);
            if ($odds_obj != null) {
                $odds_col[] = $odds_obj;
            }
        }

        return $odds_col;
    }

    public static function getAllPropOddsForMatchupPropType($matchup_id, $a_iBookieID, $proptype_id, $team_num): array
    {
        return OddsDB::getAllPropOddsForMatchupPropType($matchup_id, $a_iBookieID, $proptype_id, $team_num);
    }


    /* Gets the average value across multiple bookies for specific prop type */
    public static function getCurrentPropIndex(int $matchup_id, int $prop_side, int $proptype_id, int $team_num): ?PropBet
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

    public static function getCorrelationsForBookie(int $bookie_id): array
    {
        return OddsDB::getCorrelationsForBookie($bookie_id);
    }

    /**
     * Accepts an array of correlations defined as follows:
     *
     * array('correlation' => xxx, 'matchup_id' => xxx)
     */
    public static function storeCorrelations(int $bookie_id, array $correlations_col): bool
    {
        return OddsDB::storeCorrelations($bookie_id, $correlations_col);
    }


    public static function getMatchupIDForCorrelation(int $bookie_id, string $correlation): ?int
    {
        return OddsDB::getMatchupIDForCorrelation($bookie_id, $correlation);
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

    public static function getLatestEventPropOdds(int $event_id, int $bookie_id, int $proptype_id, int $offset = 0)
    {
        return OddsDB::getLatestEventPropOdds($event_id, $bookie_id, $proptype_id, $offset);
    }

    public static function getAllPropOddsForEventPropType(int $event_id, int $bookie_id, int $proptype_id): array
    {
        return OddsDB::getAllPropOddsForEventPropType($event_id, $bookie_id, $proptype_id);
    }

    public static function getCompletePropsForEvent(int $event_id, int $offset = 0, int $bookie_id = null)
    {
        return OddsDB::getCompletePropsForEvent($event_id, $offset, $bookie_id);
    }

    public static function checkMatchingEventPropOdds(EventPropBet $event_propbet_obj): bool
    {
        $existing_odds = OddsHandler::getLatestEventPropOdds($event_propbet_obj->getEventID(), $event_propbet_obj->getBookieID(), $event_propbet_obj->getPropTypeID());
        if ($existing_odds) {
            return $existing_odds->equals($event_propbet_obj);
        }
        return false;
    }

    public static function removeOddsForMatchupAndBookie(int $matchup_id, int $bookie_id)
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

    public static function removePropOddsForMatchupAndBookie(int $matchup_id, int $bookie_id, int $proptype_id = null, int $team_num = null)
    {
        if (!is_numeric($matchup_id) || !is_numeric($bookie_id)) {
            return false;
        }
        OddsHandler::removeFlagged($bookie_id, $matchup_id, null, $proptype_id, $team_num);
        return OddsDB::removePropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id, $team_num);
    }

    public static function removePropOddsForEventAndBookie(int $event_id, int $bookie_id, int $proptype_id = null)
    {
        if (!is_numeric($event_id) || !is_numeric($bookie_id)) {
            return false;
        }
        OddsHandler::removeFlagged($bookie_id, null, $event_id, $proptype_id);
        return OddsDB::removePropOddsForEventAndBookie($event_id, $bookie_id, $proptype_id);
    }

    public static function getAllLatestPropOddsForMatchupAndBookie(int $matchup_id, int $bookie_id, int $proptype_id = -1): ?array
    {
        if (!is_numeric($matchup_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id)) {
            return null;
        }
        return OddsDB::getAllLatestPropOddsForMatchupAndBookie($matchup_id, $bookie_id, $proptype_id);
    }

    public static function flagMatchupOddsForDeletion(int $bookie_id, int $matchup_id): ?int
    {
        if (
            !is_numeric($matchup_id) || !is_numeric($bookie_id)
            || $matchup_id <= 0 || $bookie_id <= 0
        ) {
            return null;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, $matchup_id, null, null, null);
    }

    public static function flagPropOddsForDeletion(int $bookie_id, int $matchup_id, int $proptype_id, int $team_num): ?int
    {
        if (
            !is_numeric($matchup_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id) || !is_numeric($team_num)
            || $matchup_id <= 0 || $bookie_id <= 0 || $proptype_id <= 0 || $team_num < 0
        ) {
            return null;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, $matchup_id, null, $proptype_id, $team_num);
    }

    public static function flagEventPropOddsForDeletion(int $bookie_id, int $event_id, int $proptype_id, int $team_num): ?int
    {
        if (
            !is_numeric($event_id) || !is_numeric($bookie_id) || !is_numeric($proptype_id) || !is_numeric($team_num)
            || $event_id <= 0 || $bookie_id <= 0 || $proptype_id <= 0 || $team_num < 0
        ) {
            return null;
        }
        return OddsDB::flagOddsForDeletion($bookie_id, null, $event_id, $proptype_id, $team_num);
    }

    public static function isFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num)
    {
        if ($bookie_id <= 0) {
            return false;
        }
        $flagged = OddsDB::isFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num);
        if (!$flagged || count($flagged) == 0) {
            return false;
        }
        return true;
    }

    public static function removeFlagged($bookie_id, $matchup_id = null, $event_id = null, $proptype_id = null, $team_num = null): ?bool
    {
        if (
            !is_numeric($bookie_id) || $bookie_id <= 0
            || (!$matchup_id && !$event_id && !$proptype_id && !$team_num)
        ) { //If no value is specified we abort
            return false;
        }
        if (OddsHandler::isFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num))
        {
            OddsDB::removeFlagged($bookie_id, $matchup_id, $event_id, $proptype_id, $team_num);
        }
        return true; 
    }

    public static function removeAllOldFlagged(): int
    {
        return OddsDB::removeAllOldFlagged();
    }

    public static function getAllFlaggedMatchups(): array
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

    public static function getLatestPropOddsV2($event_id = null, $a_iMatchupID = null, $a_iBookieID = null, $a_iPropTypeID = null, $a_iTeamNum = null)
    {
        $result = OddsDB::getLatestPropOddsV2($event_id, $a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);

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

    public static function getLatestEventPropOddsV2(int $event_id = null, int $matchup_id = null, int $bookie_id = null, int $proptype_id = null, int $team_num = null): array
    {
        $result = OddsDB::getLatestEventPropOddsV2($event_id, $matchup_id, $bookie_id, $proptype_id, $team_num);

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

    public static function getLatestMatchupOddsV2(int $event_id = null, int $matchup_id = null): array
    {
        $result = OddsDB::getLatestMatchupOddsV2($event_id, $matchup_id);

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

    public static function getEventViewData(int $event_id): array
    {
        if ($event_id == null || !is_numeric($event_id)) {
            return false;
        }

        $view_data = [];

        $event = EventHandler::getEvent((int) $event_id);

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
