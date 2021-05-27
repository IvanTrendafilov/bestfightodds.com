<?php
/**
 * This script will clean up any bad data in the database including:
 * - Duplicates on the same minute
 * - Differencing lines posted at the same minute for a fight and bookie
 * - Lines that for some reason have been added after eachother in time but
 *   represent the exact same line
 * - Prop inconsistencies (+3.5 points, multiple over/unders for a single book/fight)
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

$aBookies = BookieHandler::getAllBookies();
$aEvents = array_merge(EventHandler::getEvents(future_events_only: true), EventHandler::getRecentEvents(5));
$iRemovedOdds = 1;
$iRemovedPropOdds = 1;
$iCounter = 0;

echo 'Preparing to clean ' . count($aEvents) . ' events.. 
';

while (($iRemovedOdds > 0 || $iRemovedPropOdds > 0)  && $iCounter < 10) 
{
    $iRemovedOdds = 0;
    $iRemovedPropOdds = 0;
    $iRemovedEventPropOdds = 0;

    foreach ($aEvents as $oEvent)
    {
        $aFights = EventHandler::getMatchups(event_id: $oEvent->getID(), only_with_odds: true);

        foreach ($aFights as $oFight)
        {
            foreach ($aBookies as $oBookie)
            {
                $aOddsToRemove = getAllDuplicates(EventHandler::getAllOdds($oFight->getID(), $oBookie->getID()));
                if (isset($aOddsToRemove) && count($aOddsToRemove) > 0)
                {
                    $iRemovedOdds += count($aOddsToRemove);
                    echo ' M:' . $oFight->getID() . '/' . $oBookie->getID();
                }
                removeFightOdds($aOddsToRemove);

                $aPropTypes = OddsHandler::getAllPropTypesForMatchup($oFight->getID());
                foreach ($aPropTypes as $oPropType)
                {
                    for ($i = 0; $i <= 2; $i++)
                    {
                        $aPropOddsToRemove = getAllPropDuplicates(OddsHandler::getAllPropOddsForMatchupPropType($oFight->getID(), $oBookie->getID(), $oPropType->getID(), $i)); 
                        if (isset($aPropOddsToRemove) && count($aPropOddsToRemove) > 0)
                        {
                            $iRemovedPropOdds += count($aPropOddsToRemove);
                            echo ' P:' . $oFight->getID() . '/' . $oBookie->getID() . '/' . $oPropType->getID() .  '/' . $i;
                        }
                        removePropOdds($aPropOddsToRemove);
                    }
                }
            }
        }

        $aPropTypes = OddsHandler::getAllPropTypesForEvent($oEvent->getID());
        foreach ($aPropTypes as $oPropType)
        {
            for ($i = 0; $i <= 2; $i++)
            {
                foreach ($aBookies as $oBookie)
                {
                    $aPropOddsToRemove = getAllPropDuplicates(OddsHandler::getAllPropOddsForEventPropType($oEvent->getID(), $oBookie->getID(), $oPropType->getID())); 
                    if (isset($aPropOddsToRemove) && count($aPropOddsToRemove) > 0)
                    {
                        $iRemovedEventPropOdds += count($aPropOddsToRemove);
                        echo ' EP:' . $oEvent->getID() . '/' . $oBookie->getID() . '/' . $oPropType->getID() .  '/' . $i;
                    }
                    removeEventPropOdds($aPropOddsToRemove);
                }
            }
        }

    }

    echo "\r\nFollowing dupes removed: " . $iRemovedOdds . "\r\n";
    echo "\r\nFollowing dupes props removed: " . $iRemovedPropOdds . "\r\n";
    echo "\r\nFollowing dupes event props removed: " . $iRemovedEventPropOdds . "\r\n";

    $iCounter++;
}



//echo "\r\nCleaned over/under inconsistensies: " . clearOverUnderIncons();
//echo "\r\nCleaned handicap (e.g. +3 points) inconsistensies: " . clearPointsHandicapIncons();

/**
 * Returns all fight odds that are considered duplicates. A duplicate is one
 * that appear right after another odds object with the exact same values.
 *
 * @param <type> $a_aFightOdds
 */
function getAllDuplicates($a_aFightOdds)
{

    if ($a_aFightOdds == null || count($a_aFightOdds) <= 0)
    {
        return null;
    }

    $aRemoveOdds = array();
    $oLastMatch = null;

    foreach ($a_aFightOdds as $oFightOdds)
    {
        if ($oLastMatch != null)
        {

            if (abs(strtotime($oLastMatch->getDate()) - strtotime($oFightOdds->getDate())) < 15)
            {
                if ($oFightOdds->equals($oLastMatch))
                {
                    $aRemoveOdds[] = $oFightOdds;
                }
                else
                {

                //Check if one fightodds is better than the other
$fFav = (pow($oFightOdds->getFighterOddsAsDecimal(1, true), -1)
                + pow($oFightOdds->getFighterOddsAsDecimal(2, true), -1));
$fOav = (pow($oLastMatch->getFighterOddsAsDecimal(1, true), -1)
                + pow($oLastMatch->getFighterOddsAsDecimal(2, true), -1));
                
                if ($fFav > $fOav)
                {
                    $aRemoveOdds[] = $oFightOdds;
                }
                else if ($fFav < $fOav)
                {
                    $aRemoveOdds[] = $oLastMatch;
                }
                else
                {
                    //If they are equal in arbitrage then delete the one that was added last
                    if (strtotime($oLastMatch->getDate()) < strtotime($oFightOdds->getDate()))
                    {
                        $aRemoveOdds[] = $oFightOdds;
                    }   
                    else if (strtotime($oLastMatch->getDate()) < strtotime($oFightOdds->getDate()))
                    {
                        $aRemoveOdds[] = $oLastMatch;
                    } 
                    else
                    {

                    echo 'nothing ..
                    ';
                    }

                }

                }
                $oLastMatch = $oFightOdds;


            }
            else if ($oFightOdds->equals($oLastMatch))
            {
                    $aRemoveOdds[] = $oFightOdds;
            }
            else
            {
                $oLastMatch = $oFightOdds;
            }
        }
        else
        {
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
    if ($a_aProps == null || count($a_aProps) <= 0)
    {
        return null;
    }

    $aRemoveOdds = array();
    $oLastMatch = null;

    foreach ($a_aProps as $oProp)
    {
        if ($oLastMatch != null)
        {

            if (abs(strtotime($oLastMatch->getDate()) - strtotime($oProp->getDate())) < 15)
            {
                if ($oProp->equals($oLastMatch))
                {
                    $aRemoveOdds[] = $oProp;
                }
                else
                {

                //Check if one fightodds is better than the other
                    $fFav = 0;
                    $fOav = 0;
                    if ($oProp->getNegPropOdds() == '-99999')
                    {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getPropOdds(), true), -1));
                    }
                    else
                    {
                        $fFav = (pow(OddsTools::convertMoneylineToDecimal($oProp->getPropOdds(), true), -1)
                                        + pow(OddsTools::convertMoneylineToDecimal($oProp->getNegPropOdds(), true), -1));
                        $fOav = (pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getPropOdds(), true), -1)
                                        + pow(OddsTools::convertMoneylineToDecimal($oLastMatch->getNegPropOdds(), true), -1));
                    }



                
                if ($fFav > $fOav)
                {
                    $aRemoveOdds[] = $oProp;
                }
                else if ($fFav < $fOav)
                {
                    $aRemoveOdds[] = $oLastMatch;
                }
                else
                {
                    //If they are equal in arbitrage then delete the one that was added last
                    if (strtotime($oLastMatch->getDate()) < strtotime($oProp->getDate()))
                    {
                        $aRemoveOdds[] = $oProp;
                    }   
                    else if (strtotime($oLastMatch->getDate()) < strtotime($oProp->getDate()))
                    {
                        $aRemoveOdds[] = $oLastMatch;
                    } 
                    else
                    {

                    echo 'nothing ..
                    ';
                    }

                }

                }
                $oLastMatch = $oProp;


            }
            else if ($oProp->equals($oLastMatch))
            {
                    $aRemoveOdds[] = $oProp;
            }
            else
            {
                $oLastMatch = $oProp;
            }
        }
        else
        {
            $oLastMatch = $oProp;
        }
    }
    return $aRemoveOdds;
}



function removeFightOdds($a_aFightOddsCol)
{
    if ($a_aFightOddsCol == null || count($a_aFightOddsCol) <= 0)
    {
        return false;
    }
    foreach ($a_aFightOddsCol as $oFightOdds)
    {
        $sQuery = 'DELETE FROM fightodds
                    WHERE fight_id = ?
                        AND bookie_id = ?
                        AND fighter1_odds = ?
                        AND fighter2_odds = ?
                        AND date = ?';

        $aParams = array($oFightOdds->getFightID(), $oFightOdds->getBookieID(), $oFightOdds->getFighterOdds(1), $oFightOdds->getFighterOdds(2), $oFightOdds->getDate());

        DBTools::doParamQuery($sQuery, $aParams);

        if (DBTools::getAffectedRows() != 1)
        {
            echo "-";
        }
        else
        {
            echo "*";
        }
    }
    return true;
}


function removePropOdds($a_aPropOddsCol)
{
    if ($a_aPropOddsCol == null || count($a_aPropOddsCol) <= 0)
    {
        return false;
    }
    foreach ($a_aPropOddsCol as $oPropOdds)
    {
        $sQuery = 'DELETE FROM lines_props
                    WHERE matchup_id = ?
                        AND bookie_id = ?
                        AND prop_odds = ?
                        AND negprop_odds = ?
                        AND date = ?
                        AND team_num = ?
                        AND proptype_id = ?';

        $aParams = array($oPropOdds->getMatchupID(), $oPropOdds->getBookieID(), $oPropOdds->getPropOdds(), $oPropOdds->getNegPropOdds(), $oPropOdds->getDate(), $oPropOdds->getTeamNumber(), $oPropOdds->getPropTypeID());

        DBTools::doParamQuery($sQuery, $aParams);

        if (DBTools::getAffectedRows() != 1)
        {
            echo "-";
        }
        else
        {
            echo "*";
        }
    }
    return true;
}

function removeEventPropOdds($a_aPropOddsCol)
{
    if ($a_aPropOddsCol == null || count($a_aPropOddsCol) <= 0)
    {
        return false;
    }
    foreach ($a_aPropOddsCol as $oPropOdds)
    {
        $sQuery = 'DELETE FROM lines_eventprops
                    WHERE event_id = ?
                        AND bookie_id = ?
                        AND prop_odds = ?
                        AND negprop_odds = ?
                        AND date = ?
                        AND proptype_id = ?';

        $aParams = array($oPropOdds->getEventID(), $oPropOdds->getBookieID(), $oPropOdds->getPropOdds(), $oPropOdds->getNegPropOdds(), $oPropOdds->getDate(), $oPropOdds->getPropTypeID());

        DBTools::doParamQuery($sQuery, $aParams);

        if (DBTools::getAffectedRows() != 1)
        {
            echo "-";
        }
        else
        {
            echo "*";
        }
    }
    return true;
}


function clearOverUnderIncons()
{
    $sQuery = 'DELETE
                FROM lp2 USING lines_props lp1,
                               lines_props lp2
                WHERE lp1.proptype_id IN (32,
                                          33,
                                          34,
                                          35)
                  AND lp2.proptype_id IN (32,
                                          33,
                                          34,
                                          35)
                  AND lp1.proptype_id != lp2.proptype_id
                  AND lp1.matchup_id = lp2.matchup_id
                  AND lp1.bookie_id = lp2.bookie_id
                  AND lp1.date > lp2.date;';
        
        DBTools::doQuery($sQuery);

        return DBTools::getAffectedRows();
}

function clearPointsHandicapIncons()
{
    $sQuery = 'DELETE
                FROM lp2 USING lines_props lp1,
                               lines_props lp2
                WHERE lp1.proptype_id IN (38,
                                          39,
                                          40,
                                          41,
                                          42,
                                          43,
                                          47,
                                          48,
                                          49,
                                          50,
                                          64)
                  AND lp1.matchup_id = lp2.matchup_id
                  AND lp1.bookie_id = lp2.bookie_id
                  AND lp1.proptype_id = lp2.proptype_id
                  AND lp1.team_num <> lp2.team_num
                  AND lp1.team_num <> 0
                  AND lp2.team_num <> 0
                  AND lp1.date > lp2.date;';
        
        DBTools::doQuery($sQuery);

        return DBTools::getAffectedRows();
}


?>
