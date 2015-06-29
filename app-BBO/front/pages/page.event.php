<?php

if (!isset($_GET['eventID']) || !is_numeric($_GET['eventID']) || $_GET['eventID'] < 0 || $_GET['eventID'] > 99999)
{
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.generalConfig.php');

require_once('app/front/pages/inc.FrontLogic.php');

$oEvent = EventHandler::getEvent((int) $_GET['eventID']);
if ($oEvent == null)
{
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

$sOverlibStyle = ', FGCOLOR, \'#eeeeee\', BGCOLOR, \'#1f2a34\', BORDER, 1';

$aBookies = BookieHandler::getAllBookies();
if (sizeof($aBookies) == 0)
{
    echo 'No bookies found';
    exit();
}

$iCellCounter = 0;

//List event
ob_start();
if ($oEvent != null)
{
    $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);

    $sLastUpdateDate = ''; //Used to keep track of latest change to event as displayed at the bottom of the table
    
    //Check if event is named FUTURE EVENTS, if so, do not display the date
    //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
    $sAddDate = '';
    if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
    {
        $sAddDate = '&nbsp;&nbsp;&nbsp;' . date('F jS Y', strtotime($oEvent->getDate()));
    }

    echo '<div class="table-outer-wrapper"><div class="table-div" id="event' . $oEvent->getID() . '"><div class="table-header"><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a>' . $sAddDate . '</div>'
    . '<table class="odds-table" cellspacing="0" summary="' . $oEvent->getName() . ' Odds">'
    . '<thead>'
    . '<tr><th scope="col"></th>';

    //List all bookies, save a reference list for later use in table
    $aBookieRefList = array();
    foreach ($aBookies as $oBookie)
    {
        $aBookieRefList[] = $oBookie->getID();
        //echo '<th scope="col"><a href="' . str_replace('&', '&amp;', $oBookie->getRefURL()) . '" target="_blank" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
        //Temporary Bodog/Bovoda replacement fix:
        echo '<th scope="col"><a href="' . str_replace('&', '&amp;', $oBookie->getRefURL()) . '" target="_blank" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
    }
    echo '<th scope="col" colspan="2" style="text-align: right; padding-right: 5px;"><b>Props</b></th></tr></thead><tbody>';

    $iFightCounter = 0;
    $bSomeOddsFound = false;

    foreach ($aFights as $oFight)
    {
        //List all odds for the fight
        $aFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID());
        $aOldFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID(), 1);
        $oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());

        $iProcessed = 0;
        $iCurrentOperatorColumn = 0;
        for ($iX = 1; $iX <= 2; $iX++)
        {
            echo '<tr ' . (($iX % 2) == 1 ? '' : 'class="odd"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
            echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '">' . $oFight->getFighterAsString($iX) . '</a></th>';

            $iProcessed = 0;
            $bEverFoundOldOdds = false;

            foreach ($aFightOdds as $oFightOdds)
            {
                $bSomeOddsFound = true;

                //Check if date for fight odds is newer than previously stored latest update date for the event
                $oFightOdds->getDate() > $sLastUpdateDate ? $sLastUpdateDate = $oFightOdds->getDate() : '';

                $iCurrentOperatorColumn = $iProcessed;
                while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oFightOdds->getBookieID())
                {
                    echo '<td></td>';
                    $iCurrentOperatorColumn++;
                    $iProcessed++;
                }

                $sClassName = '';
                if ($oFightOdds->getFighterOdds($iX) == $oBestOdds->getFighterOdds($iX))
                {
                    $sClassName = 'class="bestbet"';
                }

                //Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
                $iCurrentOperatorID = $oFightOdds->getBookieID();
                $bFoundOldOdds = false;

                foreach ($aOldFightOdds as $oOldFightOdds)
                {
                    //Get difference in hours between latest and next to latest odds
                    $iHoursDiff = intval(floor((time() - strtotime($oFightOdds->getDate())) / 3600));
                    if ($iHoursDiff >= 72)
                    {
                        $iChangeID = 'age3';
                    }
                    else if ($iHoursDiff >= 24)
                    {
                        $iChangeID = 'age2';
                    }
                    else
                    {
                        $iChangeID = 'age1';
                    }


                    if ($oOldFightOdds->getBookieID() == $iCurrentOperatorID)
                    {
                        if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
                        {
                            echo '<td><a href="#" onclick="return sH(this, ' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span><img src="/img/up.gif" class="carr-' . $iChangeID . '" alt="" /></a></td>';
                        }
                        else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
                        {
                            echo '<td><a href="#" onclick="return sH(this, ' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span><img src="/img/down.gif" class="carr-' . $iChangeID . '" alt="" /></a></td>';
                        }
                        else
                        {
                            echo '<td><a href="#" onclick="return sH(this, ' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></a></td>';
                        }
                        $bFoundOldOdds = true;
                        $bEverFoundOldOdds = true;
                    }
                }
                if (!$bFoundOldOdds)
                {
                    echo '<td><a href="#" onclick="return sNH(this, ' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ')"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></a></td>';
                }

                $iProcessed++;
            }

            //Fill empty cells
            for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
            {
                echo '<td></td>';
            }

            //Add alert cell <-- DISABLED. TODO: Show if event is upcoming
            //echo '<td class="button-cell"><a href="#" onclick="return showAlertForm(' . $oFight->getID() . ', ' . $iX . ',\'' . $oBestOdds->getFighterOddsAsString($iX) . '\')"><img src="/img/alert.gif" class="small-button" alt="Add alert" /></a></td>';
            //Add index graph button
            if ($bEverFoundOldOdds || count($aFightOdds) > 1)
            {
                echo '<td class="button-cell"><a href="#" onclick="return sI(this,' . $iX . ',' . $oFightOdds->getFightID() . ');"><img src="/img/graph.gif" class="small-button" alt="Show index graph" title="Display mean history" /></a></td>';
            }
            else
            {
                echo '<td class="button-cell"><img src="/img/nograph.gif" class="small-button" alt="No index graph available" /></td>';
            }

            echo '<td class="prop-cell">';
            $iPropCount = OddsHandler::getPropCountForMatchup($oFight->getID());
            if ($iPropCount > 0)
            {
                echo '<a href="#" onclick="return tPR(\'' . $oFight->getID() . '\', \'exp-' . $oFight->getID() . '\');">' . $iPropCount . '<img src="/img/exp.gif" class="exp-img" id="exp-' . $oFight->getID() . '-' . $iX . '" alt="Show/hide" /></a>';
            }
            echo '</td>';

            echo '</tr>';
        }

        //Add prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForMatchup($oFight->getID());

        if (count($aPropTypes) > 0)
        {
            $aAllPropOdds = OddsHandler::getCompletePropsForMatchup($oFight->getID());
            $aAllOldPropOdds = OddsHandler::getCompletePropsForMatchup($oFight->getID(), 1);

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($aPropTypes as $oPropType)
            {
                //From previously fetech props, grab all for that specific proptype
                $aPropsOdds = array();
                foreach ($aAllPropOdds as $oTempPropOdds)
                {
                    if ($oTempPropOdds->getPropTypeID() == $oPropType->getID() && $oTempPropOdds->getTeamNumber() == $oPropType->getTeamNum())
                    {
                        $aPropsOdds[] = $oTempPropOdds;
                    }
                }

                $aOldPropOdds = array();
                if ($aAllOldPropOdds != null)
                {
                    foreach ($aAllOldPropOdds as $oTempPropOdds)
                    {
                        if ($oTempPropOdds->getPropTypeID() == $oPropType->getID() && $oTempPropOdds->getTeamNumber() == $oPropType->getTeamNum())
                        {
                            $aOldPropOdds[] = $oTempPropOdds;
                        }
                    }
                }

                $oBestOdds = OddsHandler::getBestPropOddsForMatchup($oFight->getID(), $oPropType->getID(), $oPropType->getTeamNum());

                $iProcessedProps = 0;
                $iCurrentOperatorColumn = 0;

                for ($iX = 1; $iX <= 2; $iX++)
                {
                    //If order has changed in the database we need to switch the odds
                    if ($oFight->hasOrderChanged())
                    {
                        $oPropType->invertTeamNum();
                    }

                    $iPropRowCounter++;
                    //Replace template placeholders for team names
                    $oPropType->setPropDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropDesc()));
                    $oPropType->setPropNegDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropNegDesc()));
                    $oPropType->getPropDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropDesc()));
                    $oPropType->setPropNegDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropNegDesc()));

                    echo '<tr class="prop-row' . (($iX % 2) == 1 ? '' : '-odd') . '" id="prop-' . $oFight->getID() . '-' . $iPropRowCounter . '" ' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iFightCounter == count($aFights) - 1) ? ' style="border-top: 1px solid #888888;"' : '')) . '>';
                    echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';

                    $iProcessedProps = 0;
                    $bEverFoundOldOdds = false;

                    foreach ($aPropsOdds as $oPropOdds)
                    {
                        $iCurrentOperatorColumn = $iProcessedProps;
                        while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oPropOdds->getBookieID())
                        {
                            echo '<td></td>';
                            $iCurrentOperatorColumn++;
                            $iProcessedProps++;
                        }

                        $sClassName = '';
                        if (($iX == 1 && $oPropOdds->getPropOdds() == $oBestOdds->getPropOdds()) ||
                                ($iX == 2 && $oPropOdds->getNegPropOdds() == $oBestOdds->getNegPropOdds()))
                        {
                            $sClassName = 'class="bestbet"';
                        }

                        //Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
                        $iCurrentOperatorID = $oPropOdds->getBookieID();
                        $bFoundOldOdds = false;

                        foreach ($aOldPropOdds as $oOldPropOdds)
                        {
                            //Get difference in hours between latest and next to latest odds
                            $iHoursDiff = intval(floor((time() - strtotime($oPropOdds->getDate())) / 3600));
                            if ($iHoursDiff >= 72)
                            {
                                $iChangeID = 'age3';
                            }
                            else if ($iHoursDiff >= 24)
                            {
                                $iChangeID = 'age2';
                            }
                            else
                            {
                                $iChangeID = 'age1';
                            }

                            //Determine if the prop or negative prop is the one to compare
                            $iCompareOddsNew = 0;
                            $iCompareOddsOld = 0;
                            if ($iX == 1)
                            {
                                $iCompareOddsNew = $oPropOdds->getPropOdds();
                                $iCompareOddsOld = $oOldPropOdds->getPropOdds();
                            }
                            else
                            {
                                $iCompareOddsNew = $oPropOdds->getNegPropOdds();
                                $iCompareOddsOld = $oOldPropOdds->getNegPropOdds();
                            }

                            if ($oOldPropOdds->getBookieID() == $iCurrentOperatorID)
                            {
                                echo '<td>';
                                if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                {
                                    if ($iCompareOddsNew > $iCompareOddsOld)
                                    {
                                        echo '<a href="#" onclick="return sHp(this, ' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span><img src="/img/up.gif" class="carr-' . $iChangeID . '" alt="" /></a>';
                                    }
                                    else if ($iCompareOddsNew < $iCompareOddsOld)
                                    {
                                        echo '<a href="#" onclick="return sHp(this, ' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span><img src="/img/down.gif" class="carr-' . $iChangeID . '" alt="" /></a>';
                                    }
                                    else
                                    {
                                        echo '<a href="#" onclick="return sHp(this, ' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ');"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></a>';
                                    }
                                }
                                else
                                {
                                    echo '<span class="na">n/a</span>';
                                }
                                $bFoundOldOdds = true;
                                $bEverFoundOldOdds = true;

                                echo '</td>';
                            }
                        }
                        if (!$bFoundOldOdds)
                        {
                            echo '<td>';
                            if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                            {
                                echo '<a href="#" onclick="return sNHp(this, ' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ')"><span id="oddsID' . $iCellCounter++ . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></a>';
                            }
                            else
                            {
                                echo '<span class="na">n/a</span>';
                            }
                            echo '</td>';
                        }

                        $iProcessedProps++;
                    }

                    //Fill empty cells
                    for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                    {
                        echo '<td></td>';
                    }

                    //Add alert cell - DISABLED
                    //echo '<td class="button-cell"><img src="/img/noalert.gif" class="small-button" alt="Alert not available" /></td>';
                    //Add index graph button
                    if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                    {
                        echo '<td class="button-cell"><a href="#" onclick="return sIp(this,' . $iX . ',' . $oPropOdds->getMatchupID() . ', ' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ');"><img src="/img/graph.gif" class="small-button" alt="Show prop index graph" title="Display mean history" /></a></td>';
                    }
                    else
                    {
                        echo '<td class="button-cell"><img src="/img/nograph.gif" class="small-button" alt="No index graph available" /></td>';
                    }

                    //Add empty cell normally used for props
                    echo '<td class="prop-cell"></td>';
                    echo '</tr>';
                }

                $iPropCounter++;
            }
        }

        //Finished adding props

        $iFightCounter++;
    }

    if (!$bSomeOddsFound)
    {
        echo '<tr><td colspan="' . (sizeof($aBookies) + 3) . '" style="padding: 20px 0; ">No betting lines available for this event';
        //Check if event is in the future, if so, add a notifier about alerts, tweets, etc..
        if (strtotime(GENERAL_TIMEZONE . ' hours') < strtotime(date('Y-m-d 23:59:59', strtotime($oEvent->getDate()))))
        {
            echo '<br /><br /><img src="/img/info.gif" class="img-info-box" /> Get notified when odds are posted either via our <a href="/alerts" style="text-decoration: underline">Alerts</a> functionality or by following us on <a href="http://twitter.com/bestfightodds" target="_blank" style="text-decoration: underline">Twitter</a>';
        }
        echo '</td></tr>';
    }

    echo '</tbody>'
    . '</table></div></div>
    <div class="table-last-changed">Last change: <span title="' . (sizeof($aFights) == 0 ? 'n/a' : (date('M jS Y H:i', strtotime($sLastUpdateDate)) . ' EST')) . '">' . getTimeDifference(strtotime($sLastUpdateDate), strtotime(GENERAL_TIMEZONE . ' hours')) . '</span></div>'
    . '
          ';
}
$sBuffer = ob_get_clean();
echo $sBuffer;
?>