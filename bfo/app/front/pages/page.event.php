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
require_once('config/inc.config.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/class.LinkTools.php');
require_once('lib/bfocore/utils/class.OddsTools.php');
require_once('lib/bfocore/general/class.StatsHandler.php');

require_once('app/front/pages/inc.FrontLogic.php');

$oEvent = EventHandler::getEvent((int) $_GET['eventID']);

$sOverlibStyle = ', FGCOLOR, \'#eeeeee\', BGCOLOR, \'#1f2a34\', BORDER, 1';

$iCellCounter = 0;

//List event
if ($oEvent != null)
{
    $sBuffer = '';
    $sLastChange = EventHandler::getLatestChangeDate($oEvent->getID());
    $bCached = false;

    //Check if page is cached or not. If so, fetch from cache and include
    if (CacheControl::isPageCached('event-' . $oEvent->getID() . '-' . strtotime($sLastChange)))
    {
        //Retrieve cached page
        $sBuffer = CacheControl::getCachedPage('event-' . $oEvent->getID() . '-' . strtotime($sLastChange));
        $bCached = true;
        echo '<!--C:HIT-->';
    }

    if ($bCached == false || empty($sBuffer))
    {
        $aBookies = BookieHandler::getAllBookies();
        if (sizeof($aBookies) == 0)
        {
            echo 'No bookies found';
            exit();
        }
        
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

        echo '<div class="table-outer-wrapper"><div class="table-div" id="event' . $oEvent->getID() . '"><div class="table-header"><a href="/events/' . $oEvent->getEventAsLinkString() . '"><h1>' . $oEvent->getName() . '</h1></a>' . $sAddDate . '';

        $sShareURL = 'https://www.bestfightodds.com/events/' . $oEvent->getEventAsLinkString();
        $sShareDesc = $oEvent->getName() . ' betting lines';

        echo '<div class="share-area"><div class="share-button"></div></div>
                                        <div class="share-window"><div data-href="https://twitter.com/intent/tweet?text=' . urlencode($sShareDesc) . '&amp;url=' . urlencode($sShareURL) . '" class="share-item share-twitter"></div><div data-href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($sShareURL) . '" class="share-item share-facebook"></div><div data-href="whatsapp://send?text=' . urlencode($sShareDesc) . ' ' . urlencode($sShareURL) . '" data-action="share/whatsapp/share" class="share-item share-whatsapp item-mobile-only"></div></div>';

        echo '</div>';

        echo '<table class="odds-table odds-table-responsive-header"><thead>'
        . '<tr><th scope="col"></th></tr></thead>'
        . '<tbody>';

        $iFightCounter = 0;
        foreach ($aFights as $oFight)
        {
            $iCurrentOperatorColumn = 0;
            for ($iX = 1; $iX <= 2; $iX++)
            {
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd" id="mu-' . $oFight->getID() . '"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="t-b-fcc">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

                echo '</tr>';
            }

            //Add prop rows
            $aPropTypes = OddsHandler::getAllPropTypesForMatchup($oFight->getID());

            if (count($aPropTypes) > 0)
            {
                $iPropCounter = 0;
                $iPropRowCounter = 0;
                foreach ($aPropTypes as $oPropType)
                {
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
                        echo '</tr>';
                    }

                    $iPropCounter++;
                }
            }

            //Finished adding props

            $iFightCounter++;
        }

        //Add event prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForEvent($oEvent->getID());
        if (count($aPropTypes) > 0)
        {
            echo '<tr class="odd even eventprop" id="mu-e' . $oEvent->getID() . '">';
            echo '<th scope="row" style="font-weight: 400"><a href="#" data-mu="' . $oEvent->getID() . '">Event props</a></th>';
            echo '</tr>';

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($aPropTypes as $oPropType)
            {
                    $iCurrentOperatorColumn = 0;
                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        $iPropRowCounter++;
                        echo '<tr class="pr' . (($iX % 2) == 1 ? '' : '-odd') . '"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '&nbsp;</th>';

                        echo '</tr>';
                    }
                    $iPropCounter++;
            }
        }



        echo '</tbody>'
        . '</table>';
    

        $sLastChange = EventHandler::getLatestChangeDate($oEvent->getID());

        echo '<div class="table-inner-wrapper"><div class="table-inner-shadow-left"></div><div class="table-inner-shadow-right"></div><div class="table-scroller"><table class="odds-table">'
        . '<thead>'
        . '<tr><th scope="col"></th>';

        //List all bookies, save a reference list for later use in table
        $aBookieRefList = array();
        foreach ($aBookies as $oBookie)
        {
            $aBookieRefList[] = $oBookie->getID();
            echo '<th scope="col"><a href="/out/' . $oBookie->getID() . '" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
        }
        echo '<th scope="col" colspan="3" class="table-prop-header">Props</th></tr></thead><tbody>';

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
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="t-b-fcc">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

                $iProcessed = 0;
                $bEverFoundOldOdds = false;

                foreach ($aFightOdds as $oFightOdds)
                {
                    $bSomeOddsFound = true;

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
                        if ($oOldFightOdds->getBookieID() == $iCurrentOperatorID)
                        {
                            echo '<td class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']" ><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID()) . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span>';
                            if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="aru changedate-' . $oFightOdds->getDate() .'">▲</span>';
                            }
                            else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="ard changedate-' . $oFightOdds->getDate() .'">▼</span>';
                            }

                            echo '</td>';
                             $bFoundOldOdds = true;
                            $bEverFoundOldOdds = true;
                        }
                    }
                    if (!$bFoundOldOdds)
                    {
                        echo '<td class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']"  ><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID()) . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></td>';
                    }

                    $iProcessed++;
                }

                //Fill empty cells
                for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                {
                    echo '<td></td>';
                }

                //Add alert cell
                //echo '<td class="button-cell"><a href="#" class="but-al" data-li="[' . $oFight->getID() . ',' . $iX . ']"><div class="but-img i-a" title="Add alert"></div></a></td>';

                //Add index graph button
                if ($bEverFoundOldOdds || count($aFightOdds) > 1)
                {
                    echo '<td class="button-cell but-si" data-li="[' . $iX . ',' . $oFightOdds->getFightID() . ']"><span class="but-img i-g" title="Betting line movement"></span></td>';
                }
                else
                {
                    echo '<td class="button-cell but-img i-ng"></td>';
                }

                echo '<td class="prop-cell prop-cell-exp" data-mu="' . $oFight->getID() . '">';
                $iPropCount = OddsHandler::getPropCountForMatchup($oFight->getID());
                if ($iPropCount > 0)
                {
                    echo $iPropCount . '&nbsp;<span class="exp-ard"></span>';
                }
                else
                {
                    echo '&nbsp;';
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
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<td class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru changedate-' . $oPropOdds->getDate() .'">▲</span>';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard changedate-' . $oPropOdds->getDate() .'">▼</span>';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</td>';
                                    }
                                    else
                                    {
                                        echo '<td><span class="na">n/a</span></td>';
                                    }
                                    $bFoundOldOdds = true;
                                    $bEverFoundOldOdds = true;
                                }
                            }
                            if (!$bFoundOldOdds)
                            {
                                if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                {
                                    echo '<td class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></td>';
                                }
                                else
                                {
                                    echo '<td><span class="na">n/a</span>';
                                }
                            }

                            $iProcessedProps++;
                        }

                        //Fill empty cells
                        for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                        {
                            echo '<td></td>';
                        }

                        //Add alert cell - Functionality should be disabled however
                        //echo '<td class="button-cell"><div class="but-img i-na"></div></td>';

                        //Add index graph
                        if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                        {
                            $oCurrentPropOddsIndex = OddsHandler::getCurrentPropIndex($oPropOdds->getMatchupID(), $iX, $oPropOdds->getPropTypeID(), $oPropOdds->getTeamNumber());
                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                            {
                                $oCurrentPropOddsIndex = $oBestOdds;
                            }
                            echo '<td class="button-cell but-sip" data-li="[' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="but-img i-g" title="Prop betting line movement"></span></td>';
                        }
                        else
                        {
                            echo '<td class="button-cell"><span class="but-img i-ng"></span></td>';
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


        //Add event prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForEvent($oEvent->getID());
        if (count($aPropTypes) > 0)
        {
            echo '<tr class="odd even eventprop" id="mu-' . $oEvent->getID() . '">';
            echo '<th scope="row" style="font-weight: 400" data-mu="e' . $oEvent->getID() . '">Event props</th>';

            //Fill empty cells
            for ($iY = 0; $iY < (sizeof($aBookieRefList)); $iY++)
            {
                echo '<td></td>';
            }
            echo '<td class="button-cell"></td>';

            echo '<td class="prop-cell" data-mu="e' . $oEvent->getID() . '">';
            echo count($aPropTypes) . '&nbsp;<span class="exp-ard"></span>';
            echo '</td>';


            echo '</tr>';


            $aAllPropOdds = OddsHandler::getCompletePropsForEvent($oEvent->getID());
            $aAllOldPropOdds = OddsHandler::getCompletePropsForEvent($oEvent->getID(), 1);

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($aPropTypes as $oPropType)
            {
                //From previously fetech props, grab all for that specific proptype
                    $aPropsOdds = array();
                    foreach ($aAllPropOdds as $oTempPropOdds)
                    {
                        if ($oTempPropOdds->getPropTypeID() == $oPropType->getID())
                        {
                            $aPropsOdds[] = $oTempPropOdds;
                        }
                    }

                    $aOldPropOdds = array();
                    if ($aAllOldPropOdds != null)
                    {
                        foreach ($aAllOldPropOdds as $oTempPropOdds)
                        {
                            if ($oTempPropOdds->getPropTypeID() == $oPropType->getID())
                            {
                                $aOldPropOdds[] = $oTempPropOdds;
                            }
                        }
                    }

                    $oBestOdds = OddsHandler::getBestPropOddsForEvent($oEvent->getID(), $oPropType->getID());

                    $iProcessedProps = 0;
                    $iCurrentOperatorColumn = 0;

                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        $iPropRowCounter++;
                        echo '<tr class="pr' . (($iX % 2) == 1 ? '' : '-odd') . '"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '&nbsp;</th>';

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
                                    echo '';
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<td class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';

                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru changedate-' . $oPropOdds->getDate() .'">▲</span>';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard changedate-' . $oPropOdds->getDate() .'">▼</span>';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</td>';
                                    }
                                    else
                                    {
                                        echo '<td><span class="na">n/a</span></td>';
                                    }
                                    $bFoundOldOdds = true;
                                    $bEverFoundOldOdds = true;

                                    echo '';
                                }
                            }
                            if (!$bFoundOldOdds)
                            {
                                if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                {
                                    echo '<td class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                }
                                else
                                {
                                    echo '<td><span class="na">n/a</span></td>';
                                }
                            }

                            $iProcessedProps++;
                        }

                        //Fill empty cells
                        for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                        {
                            echo '<td></td>';
                        }

                        //Add alert cell
                        /*echo '<td class="button-cell"><span class="but-img i-na"></span></td>';*/

                        //Add index graph
                        if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                        {
                            $oCurrentPropOddsIndex = OddsHandler::getCurrentEventPropIndex($oPropOdds->getEventID(), $iX, $oPropOdds->getPropTypeID());
                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                            {
                                $oCurrentPropOddsIndex = $oBestOdds;
                            }
                            echo '<td class="button-cell but-siep" data-li="[' . $oPropOdds->getEventID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="but-img i-g" title="Prop betting line movement"></span></td>';
                        }
                        else
                        {
                            echo '<td class="button-cell"><span class="but-img i-ng"></span></td>';
                        }

                        //Add empty cell normally used for props
                        echo '<td class="prop-cell"></td>';
                        echo '</tr>';
                    }

                    $iPropCounter++;
            }
        }




       if (!$bSomeOddsFound)
        {
            echo '<tr><td colspan="' . (sizeof($aBookies) + 3) . '" style="padding: 20px 0; ">No betting lines available for this event';
            //Check if event is in the future, if so, add a notifier about alerts, tweets, etc..
            if (strtotime(GENERAL_TIMEZONE . ' hours') < strtotime(date('Y-m-d 23:59:59', strtotime($oEvent->getDate()))))
            {
                echo '<br /><br /><img src="/img/info.gif" class="img-info-box" /> Get notified when odds are posted either via our <a href="/alerts" style="text-decoration: underline">Alerts</a> functionality or by following us on <a href="http://twitter.com/bestfightodds" target="_blank" rel="noopener" style="text-decoration: underline">Twitter</a>';
            }
            echo '</td></tr>';
        }


        echo '</tbody>'
        . '</table></div></div></div></div>'; 

        echo '%table-lastchanged%';

        //BEING ADDITIONS

       echo '<div class="table-outer-wrapper"><div id="event-swing-area"><div id="page-container"><div class="content-header">Line movement <div id="event-swing-picker-menu"><a href="#" class="event-swing-picker picked" data-li="0">Since opening</a> | <a href="#" class="event-swing-picker" data-li="1">Last 24 hours</a> | <a href="#" class="event-swing-picker" data-li="2">Last hour</a></div></div>';

?>

<?php

        $aData = [];
        $aSeriesNames = ['Change since opening', 'Change in the last 24 hours', 'Change in the last hour'];
        for ($x = 0; $x <= 2; $x++)
        {
            $aSwings = StatsHandler::getAllDiffsForEvent($oEvent->getID(), $x);
            $aRowData = [];
            
            foreach ($aSwings as $aSwing)
            {
                if ($aSwing[2]['swing'] < 0.01 && $aSwing[2]['swing'] > 0.00)
                {
                    $aSwing[2]['swing'] = 0.01;
                }
                if (round($aSwing[2]['swing'] * 100) != 0)
                {
                    
                    $aRowData[]  = [$aSwing[0]->getTeamAsString($aSwing[1]), -round($aSwing[2]['swing'] * 100)];
                }
            }
            if (count($aRowData) == 0)
            {
                $aRowData[] = ['No ' . strtolower($aSeriesNames[$x]), null];
            }
            $aData[]  = ["name" => $aSeriesNames[$x], "data" => $aRowData, "visible" => ($x == 0 ? true : false)];

        }

        //Size of chart should be maximum 10 rows initially but if less we need to check that 
        $iMaxRows = 10;
        if (count($aData[0]['data']) < 10)
        {
            $iMaxRows = count($aData[0]['data']);
        }
        echo '<div id="event-swing-container" data-moves="' . htmlentities(json_encode($aData), ENT_QUOTES, 'UTF-8') . '" style="height:' . (50 + $iMaxRows * 18) . 'px;"></div>';

        echo '<div class="event-swing-expandarea ' . (count($aData[0]['data']) < 10 ? ' hidden' : '') . '"><a href="#" class="event-swing-expand"><span>Show all</span><div class="event-swing-expandarrow"></div></a></div></div></div>';

        //END ADDITIONS

        //BEING ADDITIONS

       echo '<div id="event-outcome-area" style=""><div id="page-container"><div class="content-header">Expected outcome</div>';

?>

<?php

        //TODO: This should be refactored to use the generic getExpectedOutcomes instead
        $aOutcomes = StatsHandler::getExpectedOutcomesForEvent($oEvent->getID());
        $aRowData = [];
        foreach ($aOutcomes as $aOutcome)
        {
            $aLabels = [$aOutcome[0]->getTeamAsString(1), $aOutcome[0]->getTeamAsString(2)];
            $iOffset = $aOutcome[1]['team1_dec'] + $aOutcome[1]['team1_itd'] + ($aOutcome[1]['draw'] / 2);

            $aPoints = [$aOutcome[1]['team1_dec'],
                        $aOutcome[1]['team1_itd'],
                        $aOutcome[1]['draw'],
                        $aOutcome[1]['team2_itd'],
                        $aOutcome[1]['team2_dec']];
            $aRowData[] = [$aLabels, $aPoints];

        }
        if (count($aRowData) == 0)
        {
            $aPoints = [0,0,0,0,0];
            $aRowData[] = [['N/A','N/A'], $aPoints];
        }
        $aData  = ["name" => 'Outcomes', "data" => $aRowData];
        echo '<div id="event-outcome-container" data-outcomes="' . htmlentities(json_encode($aData), ENT_QUOTES, 'UTF-8') . '" style="height:' . (67 + count($aRowData) * 20) . 'px;"></div>';

                //echo '<div id="event-outcome-container" style="width: 50%; height: 400px; display: inline-block;"></div>';
        echo '</div></div></div>';

        //END ADDITIONS


        $sBuffer = ob_get_clean();

        CacheControl::cleanPageCacheWC('event-' . $oEvent->getID() . '-*');
        CacheControl::cachePage($sBuffer, 'event-' . $oEvent->getID() . '-' . strtotime($sLastChange) . '.php');
        
        echo '<!--C:MIS-->';

    }

    //Dynamically replace last change placeholder
    $sBufferLastChange = '<div class="table-last-changed">Last change: <span title="' . ($sLastChange == null ? 'n/a' : (date('M jS Y H:i', strtotime($sLastChange)) . ' EST')) . '">' . getTimeDifference(strtotime($sLastChange), strtotime(GENERAL_TIMEZONE . ' hours')) . '</span></div> ';
    $sBuffer = str_replace('%table-lastchanged%', $sBufferLastChange, $sBuffer);

    //Perform dynamic modifications to the content (cached or not)
    echo preg_replace_callback('/changedate-([^\"]*)/', function ($a_aMatches)
    {
        $iHoursDiff = intval(floor((time() - strtotime($a_aMatches[1])) / 3600));
        if ($iHoursDiff >= 72)
        {
            return 'arage-3';
        }
        else if ($iHoursDiff >= 24)
        {
            return 'arage-2"';
        }
        else
        {
            return 'arage-1"';
        }
    }, $sBuffer);

    //Replace last changed placeholder


}

?>