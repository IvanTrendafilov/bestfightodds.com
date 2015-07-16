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
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/class.LinkTools.php');

require_once('app/front/pages/inc.FrontLogic.php');

$oEvent = EventHandler::getEvent((int) $_GET['eventID']);

//Check that slugged title matches, to stop bots
$iMarkPos = 0;
$sMatchEvent = '';
if ($oEvent != null)
{
    $iMarkPos = strpos($oEvent->getName(), ':') != null ? strpos($oEvent->getName(), ':') : strlen($oEvent->getName());
    $sMatchEvent = strtolower(LinkTools::slugString(substr($oEvent->getName(), 0, $iMarkPos)));    
}

if ($oEvent == null ||
    $sMatchEvent != strtolower(substr($_SERVER['REQUEST_URI'], 8, strlen($sMatchEvent))))
{
    error_log('Invalid event requested at ' . $_SERVER['REQUEST_URI']);
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
if ($oEvent != null)
{
    $sBuffer = '';
    $sLastChange = EventHandler::getLatestChangeDate($oEvent->getID());

    //Check if page is cached or not. If so, fetch from cache and include
    if (CacheControl::isPageCached('event-' . $oEvent->getID() . '-' . strtotime($sLastChange)))
    {
        //Retrieve cached page
        $sBuffer = CacheControl::getCachedPage('event-' . $oEvent->getID() . '-' . strtotime($sLastChange));
        echo '<!--C:HIT-->';
    }
    else
    {
        //Generate new page and display to user
        ob_start();
        $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);

        //Check if event is named FUTURE EVENTS, if so, do not display the date
        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
        $sAddDate = '';
        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
        {
            $sAddDate = '<span class="table-header-date">' . date('F jS', strtotime($oEvent->getDate())) . '</span>';
        }

        echo '<div class="table-outer-wrapper"><div class="table-div" id="event' . $oEvent->getID() . '"><div class="table-header"><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a>' . $sAddDate . '';

        $sShareURL = 'https://www.bestfightodds.com' . $oEvent->getEventAsLinkString();
        $sShareDesc = $oEvent->getName() . ' betting lines';

        echo '<ul class="share-dropdown dropdown">
                                <li><a href="#"><img src="/img/share_3.png" class="share-button" alt="Share this" /></a>
                                        <ul class="sub_menu">
                                             <li><div class="share-window">
                                                <a href="https://twitter.com/intent/tweet?text=' . urlencode($sShareDesc) . '&amp;url=' . urlencode($sShareURL) . '" class="share-window-twitter">&nbsp;</a>
                                                <a href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fsbnation.com%2Fe%2F8538682%3Futm_campaign%3Dbloodyelbow%26utm_content%3Darticle%2525253Atop%26utm_medium%3Dsocial%26utm_source%3Dfacebook%252523%2523%23" class="share-window-facebook">&nbsp;</a>
                                                <a href="#" class="share-window-whatsapp">&nbsp;</a>
                                                <a href="#" class="share-window-google">&nbsp;</a></div></li>
                                        </ul>
                                </li>
                            </ul>';

                            /*echo '<div class="share-area"><a href="#">Share me</a></div>';*/

        echo '</div>';

        echo '<table class="odds-table odds-table-responsive-header"><thead>'
        . '<tr><th scope="col"></th></tr></thead>'
        . '<tbody>';

        $iFightCounter = 0;

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
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd" id="mu-' . $oFight->getID() . '"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '">' . $oFight->getFighterAsString($iX) . '</a></th>';

                $iProcessed = 0;
                $bEverFoundOldOdds = false;

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

                        echo '<tr class="pr' . (($iX % 2) == 1 ? '' : '-odd') . '"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iFightCounter == count($aFights) - 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';

                        $iProcessedProps = 0;
                        $bEverFoundOldOdds = false;

                        echo '</tr>';
                    }

                    $iPropCounter++;
                }
            }

            //Finished adding props

            $iFightCounter++;
        }
        echo '</tbody>'
        . '</table>';
    }

    if (sizeof($aFights) > 0 && $oEvent->isDisplayed())
    {
        $sLastChange = EventHandler::getLatestChangeDate($oEvent->getID());

        echo '<div class="table-inner-wrapper"><div class="table-inner-shadow-left"></div><div class="table-inner-shadow-right"></div><div class="table-scroller"><table class="odds-table">'
        . '<thead>'
        . '<tr><th scope="col"></th>';

        //List all bookies, save a reference list for later use in table
        $aBookieRefList = array();
        foreach ($aBookies as $oBookie)
        {
            $aBookieRefList[] = $oBookie->getID();
            echo '<th scope="col"><a href="' . str_replace('&', '&amp;', $oBookie->getRefURL()) . '" target="_blank" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
        }
        echo '<th scope="col" colspan="3" class="table-prop-header">Props</th></tr></thead><tbody>';

        $iFightCounter = 0;

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
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '">' . $oFight->getFighterAsString($iX) . '</a></th>';

                $iProcessed = 0;
                $bEverFoundOldOdds = false;

                foreach ($aFightOdds as $oFightOdds)
                {
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
                            $iChangeID = '3';
                        }
                        else if ($iHoursDiff >= 24)
                        {
                            $iChangeID = '2';
                        }
                        else
                        {
                            $iChangeID = '1';
                        }


                        if ($oOldFightOdds->getBookieID() == $iCurrentOperatorID)
                        {
                            echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']" ><div><span id="oID' . ('1' . $oFightOdds->getFightID() . $oFightOdds->getBookieID() . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span>';
                            if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="aru arage-' . $iChangeID . '">▲</span>';//<img src="/img/up.gif" class="carr-' . $iChangeID . '" alt="" />';
                            }
                            else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="ard arage-' . $iChangeID . '">▼</span>';//<img src="/img/down.gif" class="carr-' . $iChangeID . '" alt="" />';
                            }
 
                            echo '</div></a></td>';
                             $bFoundOldOdds = true;
                            $bEverFoundOldOdds = true;
                        }
                    }
                    if (!$bFoundOldOdds)
                    {
                        echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']"  ><div><span id="oID' . ('1' . $oFightOdds->getFightID() . $oFightOdds->getBookieID() . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></div></a></td>';
                    }

                    $iProcessed++;
                }

                //Fill empty cells
                for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                {
                    echo '<td></td>';
                }

                //Add alert cell
                echo '<td class="button-cell"><a href="#" class="but-al" data-li="[' . $oFight->getID() . ',' . $iX . ']"><img src="/img/alert.gif" alt="Add alert" title="Add alert" /></a></td>';

                //Add index graph button
                if ($bEverFoundOldOdds || count($aFightOdds) > 1)
                {
                    echo '<td class="button-cell"><a href="#" class="but-si" data-li="[' . $iX . ',' . $oFightOdds->getFightID() . ']"><img src="/img/graph.gif" alt="Betting line movement" title="Betting line movement" /></a></td>';
                }
                else
                {
                    echo '<td class="button-cell"><img src="/img/nograph.gif" alt="No index graph available" /></td>';
                }

                echo '<td class="prop-cell"><a href="#" data-mu="' . $oFight->getID() . '"><div>';
                $iPropCount = OddsHandler::getPropCountForMatchup($oFight->getID());
                if ($iPropCount > 0)
                {
                    echo $iPropCount . '&nbsp;<span class="exp-txt">►</span>';
                }
                echo '</div></a></td>';

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

                        echo '<tr class="pr' . (($iX % 2) == 1 ? '' : '-odd') . '"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iFightCounter == count($aFights) - 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
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
                                    $iChangeID = '3';
                                }
                                else if ($iHoursDiff >= 24)
                                {
                                    $iChangeID = '2';
                                }
                                else
                                {
                                    $iChangeID = '1';
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
                                        echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><div><span id="oID' . ('2' . $oPropOdds->getMatchupID() . $oPropOdds->getBookieID() . $iX . $oPropOdds->getPropTypeID() . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru arage-' . $iChangeID . '">▲</span>';//<img src="/img/up.gif" class="carr-' . $iChangeID . '" alt="" />';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard arage-' . $iChangeID . '">▼</span>';//<img src="/img/down.gif" class="carr-' . $iChangeID . '" alt="" />';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</div></a>';
                                    }
                                    else
                                    {
                                        echo '<div><span class="na">n/a</span></div>';
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
                                    echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><div><span id="oID' . ('2' . $oPropOdds->getMatchupID() . $oPropOdds->getBookieID() . $iX . $oPropOdds->getPropTypeID() . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></div></a>';
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

                        //Add alert cell - Functionality disabled however
                        echo '<td class="button-cell"><img src="/img/noalert.gif" alt="Alert not available" /></td>';

                        //Add index graph
                        if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                        {
                            $oCurrentPropOddsIndex = OddsHandler::getCurrentPropIndex($oPropOdds->getMatchupID(), $iX, $oPropOdds->getPropTypeID(), $oPropOdds->getTeamNumber());
                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                            {
                                $oCurrentPropOddsIndex = $oBestOdds;
                            }
                            echo '<td class="button-cell"><a href="#" class="but-sip" data-li="[' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><img src="/img/graph.gif" alt="Prop betting line movement" title="Prop betting line movement" /></a></td>';
                        }
                        else
                        {
                            echo '<td class="button-cell"><img src="/img/nograph.gif" alt="No index graph available" /></td>';
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
        echo '</tbody>'
        . '</table></div></div></div></div>
        <div class="table-last-changed">Last change: <span title="' . ($sLastChange == null ? 'n/a' : (date('M jS Y H:i', strtotime($sLastChange)) . ' EST')) . '"><?php echo getTimeDifference("' . strtotime($sLastChange) . '", strtotime("' . GENERAL_TIMEZONE . ' hours")); ?></span></div>';
    }
}

?>