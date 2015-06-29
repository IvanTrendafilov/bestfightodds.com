<?php
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('config/inc.generalConfig.php');

$oMatchup = null;
if (!isset($_GET['matchupID']) || !is_numeric($_GET['matchupID']) || $_GET['matchupID'] < 0 || $_GET['matchupID'] > 999999 || ($oMatchup = EventHandler::getFightByID($_GET['matchupID'])) == null)
{
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

$oEvent = EventHandler::getEvent($oMatchup->getEventID());
$aBookies = BookieHandler::getAllBookies();
$oFight = $oMatchup;

$aFights = EventHandler::getAllFightsForFighter((int) $_GET['matchupID']);
$iCellCounter = 0;
?>

<div id="page-wrapper">

    <div id="page-header">
        <a href="/fighters/<?=$oMatchup->getFighterAsLinkString(1)?>"><?=$oMatchup->getTeamAsString(1)?></a> vs. <a href="/fighters/<?=$oMatchup->getFighterAsLinkString(2)?>"><?=$oMatchup->getTeamAsString(2)?></a>
    </div>
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <div class="fighter-profile-container" style="float: left; width: 56px; margin-left: 0; margin-right: 24px;">
                    <img src="/img/fighter.png" style="width: 60px;" alt="<?=$oMatchup->getTeamAsString(1)?>" />
                </div>
                <div class="fighter-profile-container" style="float: left; width: 56px; ">
                    <img src="/img/fighter.png" style="width: 60px;" alt="<?=$oMatchup->getTeamAsString(1)?>" />
                </div>
                <div class="fighter-history-container">
                    <?php
                        $oEvent = EventHandler::getEvent($oFight->getEventID());
                        $oFightOdds1 = EventHandler::getBestOddsForFightAndFighter($oFight->getID(), 1);
                        $oFightOdds2 = EventHandler::getBestOddsForFightAndFighter($oFight->getID(), 2);
                        $oOpeningOdds = OddsHandler::getOpeningOddsForMatchup($oFight->getID());

                        $sEventDate = '';
                        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
                        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
                        {
                            $sEventDate = ' - ' . date('M jS Y', strtotime($oEvent->getDate()));
                        }
                        ?>
                        <div class="fighter-history-event-container">
                            <div class="table-header" style="font-size: 12px; padding: 6px; "><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a><?php echo $sEventDate; ?></div>
                            <table class="odds-table" cellspacing="0" style="width: 510px; border-color: #b0b0b0; border-style: solid; border-width: 0 1px;" summary="<?php echo $oEvent->getName(); ?> Odds for <?php echo $oFight->getFighterAsString(1); ?> vs <?php echo $oFight->getFighterAsString(2); ?>">
                                <?php
                                if ($oFightOdds1 != null && $oFightOdds2 != null && $oEvent != null)
                                {
                                    if ($oFightOdds1->getBookieID() == $oFightOdds2->getBookieID())
                                    {
                                        ?>

                                        <tbody>
                                            <tr>
                                                <th scope="row" style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></th>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" style="background-color: #585858; color: #fff; padding: 0 1px;"><?php echo $oOpeningOdds->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(1); ?></span></td>
                                                <td></td>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,1,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Show index graph" title="Display mean history" /></a></td>';
                                                ?>
                                            </tr>
                                            <tr class="odd">
                                                <th scope="row"  style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></th>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" style="background-color: #585858; color: #fff; padding: 0 1px;"><?php echo $oOpeningOdds->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(2); ?></span></td>
                                                <td></td>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,2,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Show index graph" title="Display mean history" /></a></td>';
                                                ?>
                                            </tr>
                                        </tbody>
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <tbody>
                                            <tr>
                                                <th scope="row" style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></th>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" style="background-color: #585858; color: #fff; padding: 0 1px;"><?php echo $oOpeningOdds->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds2->getFighterOddsAsString(1); ?></span></td>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,1,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Show index graph" title="Display mean history" /></a></td>';
                                                ?>
                                            </tr>
                                            <tr class="odd">
                                                <th scope="row"  style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></th>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" style="background-color: #585858; color: #fff; padding: 0 1px;"><?php echo $oOpeningOdds->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds1->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds2->getFighterOddsAsString(2); ?></span></td>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,2,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Show index graph" title="Display mean history" /></a></td>';
                                                ?>
                                            </tr>
                                        </tbody>

                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tbody><tr><th scope="row" style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></th><td align="center" class="moneyline">n/a</td>
                                        </tr>
                                        <tr class="odd"><th scope="row"  style="width: 240px;"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></th><td align="center" class="moneyline">n/a</td>
                                        </tr>
                                    </tbody>
                                    <?php
                                }
                                ?>
                            </table>
                        </div>
                </div>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <span style="background-color: #585858;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Opening line<br /><br />
                <span style="background-color: #a30000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Best line at fight time<br /><br />
                Odds displayed for each fighter is the best line available from one or more bookies
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>

<?php

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

//List all odds for the fight
$aFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID());
$aOldFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID(), 1);
$oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());

$iProcessed = 0;
$iCurrentOperatorColumn = 0;
if (count($aFightOdds) > 0)
{
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
}
//Finished adding props

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

?>