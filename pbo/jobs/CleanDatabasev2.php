<?php

/**
 * This script will clean up any bad data in the database including:
 * - Duplicates on the same minute
 * - Differencing lines posted at the same minute for a fight and bookie
 * - Lines that for some reason have been added after eachother in time but
 *   represent the exact same line
 *
 * Prefereably this should be scheduled as a cron job running at least once a day
 *
 *
 * Step 2: Remove all duplicates occuring in the same minute for the same fight
 * and bookie but remove the line that has the worst vig. This should be done using
 * the Alerter class that has vig calculation has a method.
 * 
 * Step 3: Removes any odds that appear right after each other in time with the
 * same odds for the same bookie and fight. If records where cleaned as part of
 * step 1, it is highly likely that there will be records like this left in the
 * database.
 *
 * The above description might be out of date..
 *
 */

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\BookieHandler;
use BFO\General\OddsHandler;
use BFO\General\EventHandler;
use BFO\Utils\DB\DBTools;
use BFO\Utils\OddsTools;

//Step 3

$bookies = BookieHandler::getAllBookies();
$events = array_merge(EventHandler::getEvents(future_events_only: true), EventHandler::getRecentEvents(5));
$removed_odds_counter = 1;
$removed_props_counter = 1;
$iterations_counter = 0;

echo 'Preparing to clean ' . count($events) . ' events.. 
';

while (($removed_odds_counter > 0 || $removed_props_counter > 0)  && $iterations_counter < 10) {
    $removed_odds_counter = 0;
    $removed_props_counter = 0;

    foreach ($events as $event) {
        $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);

        foreach ($matchups as $matchup) {
            foreach ($bookies as $oBookie) {
                $odds_to_remove = getAllDuplicates(OddsHandler::getAllOdds($matchup->getID(), $oBookie->getID()));
                if (count($odds_to_remove) > 0) {
                    $removed_odds_counter += count($odds_to_remove);
                    echo ' M:' . $matchup->getID() . '/' . $oBookie->getID();
                }
                removeFightOdds($odds_to_remove);

                $proptypes = OddsHandler::getAllPropTypesForMatchup($matchup->getID());
                foreach ($proptypes as $proptype) {
                    for ($i = 0; $i <= 2; $i++) {
                        $props_to_remove = getAllPropDuplicates(OddsHandler::getAllPropOddsForMatchupPropType($matchup->getID(), $oBookie->getID(), $proptype->getID(), $i));
                        if (count($props_to_remove) > 0) {
                            $removed_props_counter += count($props_to_remove);
                            echo ' P:' . $matchup->getID() . '/' . $oBookie->getID() . '/' . $proptype->getID() .  '/' . $i;
                        }
                        removePropOdds($props_to_remove);
                    }
                }
            }
        }
    }

    echo "\r\nFollowing dupes removed: " . $removed_odds_counter . "\r\n";
    echo "\r\nFollowing dupes props removed: " . $removed_props_counter . "\r\n";

    $iterations_counter++;
}



/**
 * Returns all fight odds that are considered duplicates. A duplicate is one
 * that appear right after another odds object with the exact same values.
 *
 * @param <type> $a_aFightOdds
 */
function getAllDuplicates($a_aFightOdds)
{
    if ($a_aFightOdds == null || count($a_aFightOdds) <= 0) {
        return null;
    }

    $aRemoveOdds = array();
    $oLastMatch = null;

    foreach ($a_aFightOdds as $oFightOdds) {
        if ($oLastMatch != null) {

            if (abs(strtotime($oLastMatch->getDate()) - strtotime($oFightOdds->getDate())) < 15) {
                if ($oFightOdds->equals($oLastMatch)) {
                    $aRemoveOdds[] = $oFightOdds;
                } else {

                    //Check if one fightodds is better than the other
                    $fFav = (pow($oFightOdds->getFighterOddsAsDecimal(1, true), -1)
                        + pow($oFightOdds->getFighterOddsAsDecimal(2, true), -1));
                    $fOav = (pow($oLastMatch->getFighterOddsAsDecimal(1, true), -1)
                        + pow($oLastMatch->getFighterOddsAsDecimal(2, true), -1));

                    if ($fFav > $fOav) {
                        $aRemoveOdds[] = $oFightOdds;
                    } else if ($fFav < $fOav) {
                        $aRemoveOdds[] = $oLastMatch;
                    } else {
                        //If they are equal in arbitrage then delete the one that was added last
                        if (strtotime($oLastMatch->getDate()) < strtotime($oFightOdds->getDate())) {
                            $aRemoveOdds[] = $oFightOdds;
                        } else if (strtotime($oLastMatch->getDate()) < strtotime($oFightOdds->getDate())) {
                            $aRemoveOdds[] = $oLastMatch;
                        } else {

                            echo 'nothing ..
                    ';
                        }
                    }
                }
                $oLastMatch = $oFightOdds;
            } else if ($oFightOdds->equals($oLastMatch)) {
                $aRemoveOdds[] = $oFightOdds;
            } else {
                $oLastMatch = $oFightOdds;
            }
        } else {
            $oLastMatch = $oFightOdds;
        }
    }
    return $aRemoveOdds;
}


/**
 * Returns all fight odds that are considered duplicates. A duplicate is one
 * that appear right after another odds object with the exact same values.
 *
 * @param <type> $a_aFightOdds
 */
function getAllPropDuplicates($a_aProps)
{

    if ($a_aProps == null || count($a_aProps) <= 0) {
        return null;
    }

    $aRemoveOdds = array();
    $oLastMatch = null;

    foreach ($a_aProps as $oProp) {
        if ($oLastMatch != null) {

            if (abs(strtotime($oLastMatch->getDate()) - strtotime($oProp->getDate())) < 15) {
                if ($oProp->equals($oLastMatch)) {
                    $aRemoveOdds[] = $oProp;
                } else {

                    //Check if one fightodds is better than the other
                    $fFav = 0;
                    $fOav = 0;
                    if ($oProp->getNegPropOdds() == '-99999') {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getPropOdds(), true), -1));
                    } else {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1)
                            + pow(OddsTools::convertMoneylineToDecimal($oProp->getNegPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getPropOdds(), true), -1)
                            + pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getNegPropOdds(), true), -1));
                    }

                    if ($fFav > $fOav) {
                        $aRemoveOdds[] = $oProp;
                    } else if ($fFav < $fOav) {
                        $aRemoveOdds[] = $oLastMatch;
                    } else {
                        //If they are equal in arbitrage then delete the one that was added last
                        if (strtotime($oLastMatch->getDate()) < strtotime($oProp->getDate())) {
                            $aRemoveOdds[] = $oProp;
                        } else if (strtotime($oLastMatch->getDate()) < strtotime($oProp->getDate())) {
                            $aRemoveOdds[] = $oLastMatch;
                        } else {

                            echo 'nothing ..
                    ';
                        }
                    }
                }
                $oLastMatch = $oProp;
            } else if ($oProp->equals($oLastMatch)) {
                $aRemoveOdds[] = $oProp;
            } else {
                $oLastMatch = $oProp;
            }
        } else {
            $oLastMatch = $oProp;
        }
    }
    return $aRemoveOdds;
}



function removeFightOdds($fightodds_col)
{
    if ($fightodds_col == null || count($fightodds_col) <= 0) {
        return false;
    }
    foreach ($fightodds_col as $fightodds) {
        $query = 'DELETE FROM fightodds
                    WHERE fight_id = ?
                        AND bookie_id = ?
                        AND fighter1_odds = ?
                        AND fighter2_odds = ?
                        AND date = ?';

        $params = [$fightodds->getFightID(), $fightodds->getBookieID(), $fightodds->getOdds(1), $fightodds->getOdds(2), $fightodds->getDate()];

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() != 1) {
            echo "-";
        } else {
            echo "*";
        }
    }
    return true;
}


function removePropOdds($prop_odds_col)
{
    if ($prop_odds_col == null || count($prop_odds_col) <= 0) {
        return false;
    }
    foreach ($prop_odds_col as $prop_odds) {
        $query = 'DELETE FROM lines_props
                    WHERE matchup_id = ?
                        AND bookie_id = ?
                        AND prop_odds = ?
                        AND negprop_odds = ?
                        AND date = ?
                        AND team_num = ?
                        AND proptype_id = ?';

        $params = array($prop_odds->getMatchupID(), $prop_odds->getBookieID(), $prop_odds->getPropOdds(), $prop_odds->getNegPropOdds(), $prop_odds->getDate(), $prop_odds->getTeamNumber(), $prop_odds->getPropTypeID());

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() != 1) {
            echo "-";
        } else {
            echo "*";
        }
    }
    return true;
}
