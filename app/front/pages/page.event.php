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
require_once('lib/bfocore/utils/class.OddsTools.php');
require_once('lib/bfocore/general/class.StatsHandler.php');

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
        $aFights = null; //EventHandler::getAllFightsForEvent($oEvent->getID(), true);

        //Check if event is named FUTURE EVENTS, if so, do not display the date
        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
        $sAddDate = '';
        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
        {
            $sAddDate = '<span class="table-header-date">' . date('F jS', strtotime($oEvent->getDate())) . '</span>';
        }

        echo '<div class="table-outer-wrapper"><div class="table-div" id="event' . $oEvent->getID() . '"><div class="table-header"><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a>' . $sAddDate . '';

        $sShareURL = 'https://www.bestfightodds.com/events/' . $oEvent->getEventAsLinkString();
        $sShareDesc = $oEvent->getName() . ' betting lines';

        echo '<div class="share-area"><div class="share-button"></div></div>
                                        <div class="share-window"><div data-href="https://twitter.com/intent/tweet?text=' . urlencode($sShareDesc) . '&amp;url=' . urlencode($sShareURL) . '" class="share-item share-twitter"></div><div data-href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($sShareURL) . '" class="share-item share-facebook"></div><div data-href="https://plus.google.com/share?url=' . urlencode($sShareURL) .'" class="share-item share-google"></div><div data-href="whatsapp://send?text=' . urlencode($sShareDesc) . ' ' . urlencode($sShareURL) . '" data-action="share/whatsapp/share" class="share-item share-whatsapp item-mobile-only"></div></div>';

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
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="tw">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

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


        //Add event prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForEvent($oEvent->getID());
        if (count($aPropTypes) > 0)
        {
            echo '<tr class="odd even eventprop" id="mu-e' . $oEvent->getID() . '">';
            echo '<th scope="row" style="font-weight: 400"><a href="#" data-mu="' . $oEvent->getID() . '">Event props</a></th>';
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
            echo '<th scope="col"><a href="/out/' . $oBookie->getID() . '" target="_blank" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
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
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="tw">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

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
                            echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']" ><span class="tw"><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID()) . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span>';
                            if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="aru changedate-' . $oFightOdds->getDate() .'">▲</span>';
                            }
                            else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="ard changedate-' . $oFightOdds->getDate() .'">▼</span>';
                            }

                            echo '</span></a></td>';
                             $bFoundOldOdds = true;
                            $bEverFoundOldOdds = true;
                        }
                    }
                    if (!$bFoundOldOdds)
                    {
                        echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']"  ><span class="tw"><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID()) . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></span></a></td>';
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
                    echo '<td class="button-cell"><a href="#" class="but-si" data-li="[' . $iX . ',' . $oFightOdds->getFightID() . ']"><span class="but-img i-g" title="Betting line movement"></span></a></td>';
                }
                else
                {
                    echo '<td class="button-cell"><span class="but-img i-ng"></span></td>';
                }

                echo '<td class="prop-cell"><a href="#" data-mu="' . $oFight->getID() . '"><span class="tw">';
                $iPropCount = OddsHandler::getPropCountForMatchup($oFight->getID());
                if ($iPropCount > 0)
                {
                    echo $iPropCount . '&nbsp;<span class="exp-ard"></span>';
                }
                else
                {
                    echo '&nbsp;';
                }
                echo '</span></a></td>';

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
                                    echo '<td>';
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="tw"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
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
                                        echo '</span></a>';
                                    }
                                    else
                                    {
                                        echo '<span class="tw"><span class="na">n/a</span></span>';
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
                                    echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span class="tw"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></span></a>';
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
                            echo '<td class="button-cell"><a href="#" class="but-sip" data-li="[' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="but-img i-g" title="Prop betting line movement"></span></a></td>';
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
            echo '<th scope="row" style="font-weight: 400"><a href="#" data-mu="e' . $oEvent->getID() . '">Event props</a></th>';

            //Fill empty cells
            for ($iY = 0; $iY < (sizeof($aBookieRefList)); $iY++)
            {
                echo '<td></td>';
            }
            echo '<td class="button-cell"></td>';

            echo '<td class="prop-cell"><a href="#" data-mu="e' . $oEvent->getID() . '"><span class="tw">';
            echo count($aPropTypes) . '&nbsp;<span class="exp-ard"></span>';
            echo '</span></a></td>';


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
                                    echo '<td>';
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<a href="#" class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="tw"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';

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
                                        echo '</span></a>';
                                    }
                                    else
                                    {
                                        echo '<span class="tw"><span class="na">n/a</span></span>';
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
                                    echo '<a href="#" class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span class="tw"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></span></a>';
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
                            echo '<td class="button-cell"><a href="#" class="but-siep" data-li="[' . $oPropOdds->getEventID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="but-img i-g" title="Prop betting line movement"></span></a></td>';
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
                echo '<br /><br /><img src="/img/info.gif" class="img-info-box" /> Get notified when odds are posted either via our <a href="/alerts" style="text-decoration: underline">Alerts</a> functionality or by following us on <a href="http://twitter.com/bestfightodds" target="_blank" style="text-decoration: underline">Twitter</a>';
            }
            echo '</td></tr>';
        }


        echo '</tbody>'
        . '</table></div></div></div></div>'; 



        //BEING ADDITIONS

       echo '<div class="table-outer-wrapper" style="margin-top: 10px;"><div class="table-div" style="background-color: #fff; color: #ff0000"><a href="#" class="event-swing-picker" data-li="0" style="color: #666">All</a> | <a href="#" class="event-swing-picker" data-li="1" style="color: #666">Last 24 hours</a> | <a href="#" class="event-swing-picker" data-li="2" style="color: #666">Last hour</a>';

?>


<?php

        $aData = [];
        $aSeriesNames = ['Changes since opening', 'Changes in the last 24 hours', 'Changes in the last hour'];
        for ($x = 0; $x <= 2; $x++)
        {
            $aSwings = StatsHandler::getAllDiffsForEvent($oEvent->getID(), $x);
            $aRowData = [];
            $iCount = 0;
            foreach ($aSwings as $aSwing)
            {
                if ($aSwing[2]['swing'] != 0)
                {
                    $iCount++;
                    $aRowData[]  = [$aSwing[0]->getTeamAsString($aSwing[1]), -round($aSwing[2]['swing'] * 100)];
                }
            }
            if (count($aRowData) == 0)
            {
                $aRowData[] = ['No ' . strtolower($aSeriesNames[$x]), null];
            }
            $aData[]  = ["name" => $aSeriesNames[$x], "data" => $aRowData, "visible" => ($x == 0 ? true : false)];

        }

        echo '<div id="event-swing-container" data-moves="' . htmlentities(json_encode($aData), ENT_QUOTES, 'UTF-8') . '" style="width: 50%; height:' . (60 + count($aData[0]['data']) * 18) . 'px;"></div>';

        echo '</div></div>';

        //END ADDITIONS




        $sBuffer = ob_get_clean();

        CacheControl::cleanPageCacheWC('event-' . $oEvent->getID() . '-*');
        CacheControl::cachePage($sBuffer, 'event-' . $oEvent->getID() . '-' . strtotime($sLastChange) . '.php');
        
        echo '<!--C:MIS-->';


    }

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

        echo '<div class="table-last-changed">Last change: <span title="' . ($sLastChange == null ? 'n/a' : (date('M jS Y H:i', strtotime($sLastChange)) . ' EST')) . '">' . getTimeDifference(strtotime($sLastChange), strtotime(GENERAL_TIMEZONE . ' hours')) . '</span></div>'
    . '
          ';
}

?>