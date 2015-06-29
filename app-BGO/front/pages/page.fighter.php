<?php
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');

$oFighter = null;
if (!isset($_GET['fighterID']) || !is_numeric($_GET['fighterID']) || $_GET['fighterID'] < 0 || $_GET['fighterID'] > 999999 || ($oFighter = FighterHandler::getFighterByID($_GET['fighterID'])) == null)
{
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

$aFights = EventHandler::getAllFightsForFighter((int) $_GET['fighterID']);
$iCellCounter = 0;
?>

<div id="page-wrapper">

    <div id="page-header">
        Fighter: <?php echo $oFighter->getNameAsString(); ?>
    </div>
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <div class="fighter-profile-container">
                    <img src="/img/fighter.png" alt="<?php echo $oFighter->getNameAsString(); ?>" />
                </div>
                <div class="fighter-history-container">

                    <?php
                    foreach ($aFights as $oFight)
                    {
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
                            <div class="table-header" style="font-size: 12px; padding: 6px; "><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a><?php echo $sEventDate; ?><a href="/fights/<?=$oFight->getFightAsLinkString()?>"><img src="/img/info-arrow.gif" class="small-button" alt="View matchup" title="Display matchup" style="float: right; margin-top: 1px;"/></a></div>
                            <table class="odds-table" cellspacing="0" style="width: 550px; border-color: #b0b0b0; border-style: solid; border-width: 0 1px;" summary="<?php echo $oEvent->getName(); ?> Odds for <?php echo $oFight->getFighterAsString(1); ?> vs <?php echo $oFight->getFighterAsString(2); ?>">
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
                        <?php
                    }
                    ?>

                </div>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <span style="background-color: #585858;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Opening line<br /><br />
                <span style="background-color: #a30000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Best line at fight time<br /><br />
                Odds displayed for each matchup are the best lines available for each fighter from one or more bookies
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>
