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

$aBookies = BookieHandler::getAllBookies();
$aEvents = array_merge(EventHandler::getAllUpcomingEvents(), EventHandler::getRecentEvents(5));
$iRemovedOdds = 1;
$iRemovedPropOdds = 1;
$iCounter = 0;

echo 'Preparing to clean ' . count($aEvents) . ' events.. 
';

while (($iRemovedOdds > 0 || $iRemovedPropOdds > 0)  && $iCounter < 10) 
{
    $iRemovedOdds = 0;
    $iRemovedPropOdds = 0;

    foreach ($aEvents as $oEvent)
    {
        $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
        foreach ($aFights as $oFight)
        {
            foreach ($aBookies as $oBookie)
            {
                $aOddsToRemove = getAllDuplicates(EventHandler::getAllOddsForFightAndBookie($oFight->getID(), $oBookie->getID()));
                if (count($aOddsToRemove) > 0)
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
                        if (count($aPropOddsToRemove) > 0)
                        {
                            $iRemovedPropOdds += count($aPropOddsToRemove);
                            echo ' P:' . $oFight->getID() . '/' . $oBookie->getID() . '/' . $oPropType->getID() .  '/' . $i;
                        }
                        removePropOdds($aPropOddsToRemove);
                    }
                }
            }
        }
    }

    echo "\r\nFollowing dupes removed: " . $iRemovedOdds . "\r\n";
    echo "\r\nFollowing dupes props removed: " . $iRemovedPropOdds . "\r\n";

    $iCounter++;
}



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

?>
