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
 * Step: Remove all duplicates occuring in the same minute for the same fight
 * and bookie but remove the line that has the worst vig. This should be done using
 * the Alerter class that has vig calculation has a method.
 * 
 * Step: Removes any odds that appear right after each other in time with the
 * same odds for the same bookie and fight. If records where cleaned as part of
 * step 1, it is highly likely that there will be records like this left in the
 * database.
 *
 */

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\BookieHandler;
use BFO\General\OddsHandler;
use BFO\General\PropTypeHandler;
use BFO\General\EventHandler;
use BFO\Utils\DB\DBTools;
use BFO\Utils\OddsTools;

$bookies = BookieHandler::getAllBookies();
$events = array_merge(EventHandler::getEvents(future_events_only: true), EventHandler::getRecentEvents(5));
$removed_odds_counter = 1;
$removed_propodds_counter = 1;
$iteration_counter = 0;

echo 'Preparing to clean ' . count($events) . ' events.. 
';

while (($removed_odds_counter > 0 || $removed_propodds_counter > 0)  && $iteration_counter < 10) {
    $removed_odds_counter = 0;
    $removed_propodds_counter = 0;
    $removed_eventprop_counter = 0;

    foreach ($events as $event) {
        $matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);

        foreach ($matchups as $matchup) {
            foreach ($bookies as $bookie) {
                $odds_to_remove = getAllDuplicates(OddsHandler::getAllOdds($matchup->getID(), $bookie->getID()));
                if (isset($odds_to_remove) && count($odds_to_remove) > 0) {
                    $removed_odds_counter += count($odds_to_remove);
                    echo ' M:' . $matchup->getID() . '/' . $bookie->getID();
                }
                removeFightOdds($odds_to_remove);

                $prop_types = PropTypeHandler::getAllPropTypesForMatchup($matchup->getID());
                foreach ($prop_types as $prop_type) {
                    for ($i = 0; $i <= 2; $i++) {
                        $propodds_to_remove = getAllPropDuplicates(OddsHandler::getAllPropOddsForMatchupPropType($matchup->getID(), $bookie->getID(), $prop_type->getID(), $i));
                        if (isset($propodds_to_remove) && count($propodds_to_remove) > 0) {
                            $removed_propodds_counter += count($propodds_to_remove);
                            echo ' P:' . $matchup->getID() . '/' . $bookie->getID() . '/' . $prop_type->getID() .  '/' . $i;
                        }
                        removePropOdds($propodds_to_remove);
                    }
                }
            }
        }

        $prop_types = PropTypeHandler::getAllPropTypesForEvent($event->getID());
        foreach ($prop_types as $prop_type) {
            for ($i = 0; $i <= 2; $i++) {
                foreach ($bookies as $bookie) {
                    $propodds_to_remove = getAllPropDuplicates(OddsHandler::getAllPropOddsForEventPropType($event->getID(), $bookie->getID(), $prop_type->getID()));
                    if (isset($propodds_to_remove) && count($propodds_to_remove) > 0) {
                        $removed_eventprop_counter += count($propodds_to_remove);
                        echo ' EP:' . $event->getID() . '/' . $bookie->getID() . '/' . $prop_type->getID() .  '/' . $i;
                    }
                    removeEventPropOdds($propodds_to_remove);
                }
            }
        }
    }

    echo "\r\nFollowing dupes removed: " . $removed_odds_counter . "\r\n";
    echo "\r\nFollowing dupes props removed: " . $removed_propodds_counter . "\r\n";
    echo "\r\nFollowing dupes event props removed: " . $removed_eventprop_counter . "\r\n";

    $iteration_counter++;
}



/**
 * Returns all fight odds that are considered duplicates. A duplicate is one
 * that appear right after another odds object with the exact same values.
 *
 * @param <type> $odds
 */
function getAllDuplicates(array $odds): ?array
{
    if (!$odds || count($odds) <= 0) {
        return null;
    }

    $odds_to_remove = [];
    $last_match = null;

    foreach ($odds as $odds_obj) {
        if ($last_match != null) {

            if (abs(strtotime($last_match->getDate()) - strtotime($odds_obj->getDate())) < 15) {
                if ($odds_obj->equals($last_match)) {
                    $odds_to_remove[] = $odds_obj;
                } else {

                    //Check if one fightodds is better than the other
                    $fFav = (pow($odds_obj->getFighterOddsAsDecimal(1, true), -1)
                        + pow($odds_obj->getFighterOddsAsDecimal(2, true), -1));
                    $fOav = (pow($last_match->getFighterOddsAsDecimal(1, true), -1)
                        + pow($last_match->getFighterOddsAsDecimal(2, true), -1));

                    if ($fFav > $fOav) {
                        $odds_to_remove[] = $odds_obj;
                    } else if ($fFav < $fOav) {
                        $odds_to_remove[] = $last_match;
                    } else {
                        //If they are equal in arbitrage then delete the one that was added last
                        if (strtotime($last_match->getDate()) < strtotime($odds_obj->getDate())) {
                            $odds_to_remove[] = $odds_obj;
                        } else if (strtotime($last_match->getDate()) < strtotime($odds_obj->getDate())) {
                            $odds_to_remove[] = $last_match;
                        } else {

                            echo 'nothing ..
                    ';
                        }
                    }
                }
                $last_match = $odds_obj;
            } else if ($odds_obj->equals($last_match)) {
                $odds_to_remove[] = $odds_obj;
            } else {
                $last_match = $odds_obj;
            }
        } else {
            $last_match = $odds_obj;
        }
    }
    return $odds_to_remove;
}


/**
 * Returns all fight odds that are considered duplicates. A duplicate is one
 * that appear right after another odds object with the exact same values.
 *
 * @param <type> $odds
 */
function getAllPropDuplicates(array $prop_odds): ?array
{
    if (!$prop_odds || count($prop_odds) <= 0) {
        return null;
    }

    $odds_to_remove = [];
    $last_match = null;

    foreach ($prop_odds as $oProp) {
        if ($last_match != null) {

            if (abs(strtotime($last_match->getDate()) - strtotime($oProp->getDate())) < 15) {
                if ($oProp->equals($last_match)) {
                    $odds_to_remove[] = $oProp;
                } else {

                    //Check if one fightodds is better than the other
                    $fFav = 0;
                    $fOav = 0;
                    if ($oProp->getNegPropOdds() == '-99999') {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($last_match->getPropOdds(), true), -1));
                    } else {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1)
                            + pow(OddsTools::convertMoneylineToDecimal($oProp->getNegPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($last_match->getPropOdds(), true), -1)
                            + pow(OddsTools::convertMoneylineToDecimal($last_match->getNegPropOdds(), true), -1));
                    }

                    if ($fFav > $fOav) {
                        $odds_to_remove[] = $oProp;
                    } else if ($fFav < $fOav) {
                        $odds_to_remove[] = $last_match;
                    } else {
                        //If they are equal in arbitrage then delete the one that was added last
                        if (strtotime($last_match->getDate()) < strtotime($oProp->getDate())) {
                            $odds_to_remove[] = $oProp;
                        } else if (strtotime($last_match->getDate()) < strtotime($oProp->getDate())) {
                            $odds_to_remove[] = $last_match;
                        } else {
                            echo 'nothing ..
                    ';
                        }
                    }
                }
                $last_match = $oProp;
            } else if ($oProp->equals($last_match)) {
                $odds_to_remove[] = $oProp;
            } else {
                $last_match = $oProp;
            }
        } else {
            $last_match = $oProp;
        }
    }
    return $odds_to_remove;
}



function removeFightOdds($oddsCol): bool
{
    if (!$oddsCol || count($oddsCol) <= 0) {
        return false;
    }
    foreach ($oddsCol as $odds_obj) {
        $query = 'DELETE FROM fightodds
                    WHERE fight_id = ?
                        AND bookie_id = ?
                        AND fighter1_odds = ?
                        AND fighter2_odds = ?
                        AND date = ?';

        $params = array($odds_obj->getFightID(), $odds_obj->getBookieID(), $odds_obj->getOdds(1), $odds_obj->getOdds(2), $odds_obj->getDate());

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() != 1) {
            echo "-";
        } else {
            echo "*";
        }
    }
    return true;
}


function removePropOdds(array $propodds_col): bool
{
    if (!$propodds_col || count($propodds_col) <= 0) {
        return false;
    }
    foreach ($propodds_col as $oPropOdds) {
        $query = 'DELETE FROM lines_props
                    WHERE matchup_id = ?
                        AND bookie_id = ?
                        AND prop_odds = ?
                        AND negprop_odds = ?
                        AND date = ?
                        AND team_num = ?
                        AND proptype_id = ?';

        $params = array($oPropOdds->getMatchupID(), $oPropOdds->getBookieID(), $oPropOdds->getPropOdds(), $oPropOdds->getNegPropOdds(), $oPropOdds->getDate(), $oPropOdds->getTeamNumber(), $oPropOdds->getPropTypeID());

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() != 1) {
            echo "-";
        } else {
            echo "*";
        }
    }
    return true;
}

function removeEventPropOdds(array $propodds_col): bool
{
    if ($propodds_col == null || count($propodds_col) <= 0) {
        return false;
    }
    foreach ($propodds_col as $propodds_obj) {
        $query = 'DELETE FROM lines_eventprops
                    WHERE event_id = ?
                        AND bookie_id = ?
                        AND prop_odds = ?
                        AND negprop_odds = ?
                        AND date = ?
                        AND proptype_id = ?';

        $params = array($propodds_obj->getEventID(), $propodds_obj->getBookieID(), $propodds_obj->getPropOdds(), $propodds_obj->getNegPropOdds(), $propodds_obj->getDate(), $propodds_obj->getPropTypeID());

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() != 1) {
            echo "-";
        } else {
            echo "*";
        }
    }
    return true;
}