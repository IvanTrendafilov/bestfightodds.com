<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.config.php');

$sOverlibStyle = ', FGCOLOR, \'#eeeeee\', BGCOLOR, \'#1f2a34\', BORDER, 1';

$aBookies = BookieHandler::getAllBookies();
if (sizeof($aBookies) == 0)
{
    echo 'No bookies found';
    exit();
}
$aEvent = EventHandler::getAllUpcomingEvents();

//List all events
foreach ($aEvent as $oEvent)
{
    $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
    //Reverse fight order since we recieve it with latest first
    $aFights = array_reverse($aFights);    

    if (sizeof($aFights) > 0 && $oEvent->isDisplayed())
    {
        //Check if event is named FUTURE EVENTS, if so, do not display the date
        $sAddDate = '';
        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
        {
            $sAddDate = date('F jS', strtotime($oEvent->getDate()));
        }
        else
        {
             $sAddDate = 'FUTURE EVENTS';
        }

        echo '<div class="table-outer-wrapper"><div class="table-div" id="event' . $oEvent->getID() . '"><div class="table-header"><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $sAddDate . '</a>';

        $sShareURL = 'https://www.proboxingodds.com/events/' . $oEvent->getEventAsLinkString();
        $sShareDesc = $oEvent->getName() . ' betting lines';

        echo '<div class="share-area"><div class="share-button"></div></div>
                                        <div class="share-window"><div data-href="https://twitter.com/intent/tweet?text=' . urlencode($sShareDesc) . '&amp;url=' . urlencode($sShareURL) . '" class="share-item share-twitter"></div><div data-href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($sShareURL) . '" class="share-item share-facebook"></div><div data-href="whatsapp://send?text=' . urlencode($sShareDesc) . ' ' . urlencode($sShareURL) . '" data-action="share/whatsapp/share" class="share-item share-whatsapp item-mobile-only"></div></div>';

        echo '</div>';

        echo '<table class="odds-table odds-table-responsive-header"><thead>'
        . '<tr><th scope="col" class="date-head"></th><th scope="col"></th></tr></thead>'
        . '<tbody>';

        $iFightCounter = 0;

        foreach ($aFights as $oFight)
        {
            //Fetch and format gametime if available as metadata
            $sGameTime = '';
            if ($oFight->getMetadata('gametime') != false)
            {
                $date = new DateTime();
                $date->setTimestamp($oFight->getMetadata('gametime'));
                $sGameTime = $date->format('H:i');
            }

            $iProcessed = 0;
            $iCurrentOperatorColumn = 0;
            for ($iX = 1; $iX <= 2; $iX++)
            {
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd" id="mu-' . $oFight->getID() . '"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<td class="date-cell" data-time="' . $sGameTime . '">' . ($iX == 1 ? $sGameTime : ($sGameTime != '' ? 'UTC' : '')) . '</td><th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="t-b-fcc">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

                $iProcessed = 0;
                $bEverFoundOldOdds = false;

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
                        echo '<th scope="row" colspan="2">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';

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
        . '<tr><th scope="col" class="date-head"></th><th scope="col"></th>';

        //List all bookies, save a reference list for later use in table
        $aBookieRefList = array();
        foreach ($aBookies as $oBookie)
        {
            $aBookieRefList[] = $oBookie->getID();
            echo '<th scope="col" data-b="' . $oBookie->getID() . '"><a href="/out/' . $oBookie->getID() . '" onclick="lO(' . $oBookie->getID() . ',' . $oEvent->getID() . ');">' . str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName())) . '</a></th>';
        }
        echo '<th scope="col" colspan="3" class="table-prop-header">Props</th></tr></thead><tbody>';

        $iFightCounter = 0;

        foreach ($aFights as $oFight)
        {
            //List all odds for the fight
            $aFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID());
            $aOldFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID(), 1);
            $oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());

            //Fetch and format gametime if available as metadata
            $sGameTime = '';
            if ($oFight->getMetadata('gametime') != false)
            {
                $date = new DateTime();
                $date->setTimestamp($oFight->getMetadata('gametime'));
                $sGameTime = $date->format('H:i');
            }

            $iProcessed = 0;
            $iCurrentOperatorColumn = 0;
            for ($iX = 1; $iX <= 2; $iX++)
            {
                echo '<tr ' . (($iX % 2) == 1 ? 'class="even"' : 'class="odd"') . ' ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<td class="date-cell" data-time="' . $sGameTime . '">' . ($iX == 1 ? $sGameTime : ($sGameTime != '' ? 'UTC' : '')) . '</td><th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="t-b-fcc">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

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
                            echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']" ><span class="t-b-cc"><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID()) . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span>';
                            if ($oFightOdds->getFighterOdds($iX) > $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="aru arage-' . $iChangeID . '">▲</span>';
                            }
                            else if ($oFightOdds->getFighterOdds($iX) < $oOldFightOdds->getFighterOdds($iX))
                            {
                                echo '<span class="ard arage-' . $iChangeID . '">▼</span>';
                            }
 
                            echo '</span></a></td>';
                             $bFoundOldOdds = true;
                            $bEverFoundOldOdds = true;
                        }
                    }
                    if (!$bFoundOldOdds)
                    {
                        echo '<td><a href="#" class="but-sg" data-li="[' . $oFightOdds->getBookieID() . ',' . $iX . ',' . $oFightOdds->getFightID() . ']"  ><span class="t-b-cc"><span id="oID' . ('1' . sprintf("%06d", $oFightOdds->getFightID()) . sprintf("%02d", $oFightOdds->getBookieID())  . $iX) . '" ' . $sClassName . '>' . $oFightOdds->getFighterOddsAsString($iX) . '</span></span></a></td>';
                    }

                    $iProcessed++;
                }

                //Fill empty cells
                for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                {
                    echo '<td></td>';
                }

                //Add alert cell
                echo '<td class="button-cell"><a href="#" class="but-al" data-li="[' . $oFight->getID() . ',' . $iX . ']"><span class="but-img i-a" title="Add alert"></span></a></td>';

                //Add index graph button
                if ($bEverFoundOldOdds || count($aFightOdds) > 1)
                {
                    echo '<td class="button-cell"><a href="#" class="but-si" data-li="[' . $iX . ',' . $oFightOdds->getFightID() . ']"><span class="but-img i-g"  title="Betting line movement"></span></a></td>';
                }
                else
                {
                    echo '<td class="button-cell"><span class="but-img i-ng"></span></td>';
                }

                echo '<td class="prop-cell"><a href="#" data-mu="' . $oFight->getID() . '" class="prop-cell-exp"><span class="t-b-cc">';
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
                        echo '<th scope="row" colspan="2">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';

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
                                        echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span class="t-b-cc"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru arage-' . $iChangeID . '">▲</span>';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard arage-' . $iChangeID . '">▼</span>';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</span></a>';
                                    }
                                    else
                                    {
                                        echo '<span class="t-b-cc"><span class="na">n/a</span></span>';
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
                                    echo '<a href="#" class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span class="t-b-cc"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></span></a>';
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
                        echo '<td class="button-cell"><span class="but-img i-na"></span></td>';

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
        echo '</tbody>'
        . '</table></div></div></div></div>
        <div class="table-last-changed">Last change: <span title="' . ($sLastChange == null ? 'n/a' : (date('M jS Y H:i', strtotime($sLastChange)) . ' UTC')) . '"><?php echo getTimeDifference("' . strtotime($sLastChange) . '", strtotime("' . GENERAL_TIMEZONE . ' hours")); ?></span></div>';
    }

    
}


?>


