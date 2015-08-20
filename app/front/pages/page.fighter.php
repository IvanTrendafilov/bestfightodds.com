<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/general/class.GraphHandler.php');

$oFighter = FighterHandler::getFighterByID($_GET['fighterID']);
if (!isset($_GET['fighterID']) || !is_numeric($_GET['fighterID']) || $_GET['fighterID'] < 0 || $_GET['fighterID'] > 999999 || $oFighter == null
    || strtolower($oFighter->getFighterAsLinkString()[0]) != strtolower(substr($_SERVER['REQUEST_URI'], 10, 1)))
{
    error_log('Invalid fighter requested at ' . $_SERVER['REQUEST_URI']);
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

$sBuffer = '';
$sLastChange = TeamHandler::getLastChangeDate($oFighter->getID());
//Check if page is cached or not. If so, fetch from cache and include
if (CacheControl::isPageCached('team-' . $oFighter->getID() . '-' . strtotime($sLastChange)))
{
    //Retrieve cached page
    $sBuffer = CacheControl::getCachedPage('team-' . $oFighter->getID() . '-' . strtotime($sLastChange));
    echo '<!--C:HIT-->';
}
else
{
    $aFights = EventHandler::getAllFightsForFighter((int) $_GET['fighterID']);
    $iCellCounter = 0;
    ob_start();
?>

<div id="page-wrapper" style="max-width: 800px;">

    
    <div id="page-container">
    <div class="content-header team-stats-header" id="team-name"><?php echo $oFighter->getNameAsString(); ?></div>
        <div id="page-inner-wrapper">
            <div id="page-content">           
                <div id="team-stats-container" style="display: inline-block">
                    <table class="team-stats-table" cellspacing="0" summary="Odds for <?php echo $oFighter->getNameAsString(); ?>">
                        <thead>
                            <tr>
                                <th>Matchup</th>
                                <th style="text-align: right;">Open</th>
                                <th style="text-align: right;">Close</th>
                                <th></th>
                                <th style="padding-left: 20px">Movement</th>
                                <th></th>
                                <th class="item-non-mobile" style="padding-left: 35px">Event</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    foreach ($aFights as $oFight)
                    {
                        $oEvent = EventHandler::getEvent($oFight->getEventID());
                        $oFightOdds1 = EventHandler::getBestOddsForFightAndFighter($oFight->getID(), 1);
                        $oFightOdds2 = EventHandler::getBestOddsForFightAndFighter($oFight->getID(), 2);
                        $oOpeningOdds = OddsHandler::getOpeningOddsForMatchup($oFight->getID());
                        
                        $iTeamPos = ((int) $oFight->getFighterID(2) == $oFighter->getID()) + 1;
                        $iOtherPos = $iTeamPos == 1 ? 2 : 1;
                        $oLatestIndex = EventHandler::getCurrentOddsIndex($oFight->getID(), $iTeamPos);

                        //Calculate % change from opening to mean
                        if ($oLatestIndex != null && $oOpeningOdds != null)
                        {
                            $fPercChange = round((($oLatestIndex->getFighterOddsAsDecimal($iTeamPos, true) - $oOpeningOdds->getFighterOddsAsDecimal($iTeamPos, true)) / $oLatestIndex->getFighterOddsAsDecimal($iTeamPos, true)) * 100, 1);
                        }

                        $sGraphData = GraphHandler::getMedianSparkLine($oFight->getID(), ($oFight->getFighterID(1) == $oFighter->getID() ? 1 : 2));

                        $sEventDate = '';
                        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
                        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
                        {
                            $sEventDate = date('M jS Y', strtotime($oEvent->getDate()));
                        }
                        ?>
                                <tr class="event-header item-mobile-only-row">
                                    <td colspan="7" scope="row"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>"><?php echo $oEvent->getName(); ?></a> <?php echo $sEventDate; ?></td>
                                </tr>                            
                                <?php
                                if ($oFightOdds1 != null && $oFightOdds2 != null && $oEvent != null)
                                {
                                    ?>

                                    <tr class="main-row">
                                        <th class="oppcell"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iTeamPos) . '">' . $oFight->getFighterAsString($iTeamPos) . '</a>'; ?></td>
                                        <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iTeamPos); ?></span></td>
                                        <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString($iTeamPos); ?></span></td>
                                    <?php
                                    if ($oFightOdds1->getBookieID() == $oFightOdds2->getBookieID())
                                    {
                                        ?>
                                                <td></td>
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                                <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds2->getFighterOddsAsString($iTeamPos); ?></span></td>
                                        <?php
                                    }
                                    ?>
                                                <td class="chart-cell" data-sparkline="<?php echo $sGraphData; ?>" data-li="[<?php echo $oFight->getID(); ?>,<?php echo $iTeamPos; ?>]" rowspan="2"></td>
                                                <td rowspan="2" class="change-cell"><span class="teamPercChange" data-li="[<?php echo $oFight->getID(); ?>,<?php echo $iTeamPos; ?>]"><?php echo $fPercChange > 0 ? '+' : ''; ?><?php echo $fPercChange; ?>%<span style="color: <?php echo $fPercChange > 0 ? '#4BCA02' : ($fPercChange < 0 ? '#E93524' : '') ?>;position:relative; margin-left: 0"><?php echo $fPercChange > 0 ? '▲' : ($fPercChange < 0 ? '▼' : '') ?></span></span></td>
                                                <td class="item-non-mobile" scope="row" style="padding-left: 35px"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" ><?php echo $oEvent->getName(); ?></a></th>
                                            </tr>
                                            <tr>
                                                <th class="oppcell"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iOtherPos) . '">' . $oFight->getFighterAsString($iOtherPos) . '</a>'; ?></td>
                                                <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iOtherPos); ?></span></td>
                                                <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oFightOdds1->getFighterOddsAsString($iOtherPos); ?></span></td>
                                    <?php
                                    if ($oFightOdds1->getBookieID() == $oFightOdds2->getBookieID())
                                    {
                                        ?>
                                                <td></td>
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                                <td class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds2->getFighterOddsAsString($iOtherPos); ?></span></td>
                                        <?php
                                    }
                                    ?>
                                        <td class="item-non-mobile" style="padding-left: 35px"><?php echo $sEventDate; ?></td>
                                    </tr>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <tr class="main-row">
                                        <th class="oppcell"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iTeamPos) . '">' . $oFight->getFighterAsString($iTeamPos) . '</a>'; ?></td>
                                        <td class="moneyline"></td>
                                        <td class="moneyline">n/a</td>
                                        <td class="moneyline"></td>
                                        <td></td>
                                        <td></td>
                                        <td class="item-non-mobile" scope="row" style="padding-left: 35px"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" ><?php echo $oEvent->getName(); ?></a></th>
                                    </tr>
                                    <tr>
                                        <th class="oppcell"><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iOtherPos) . '">' . $oFight->getFighterAsString($iOtherPos) . '</a>'; ?></td>
                                        <td class="moneyline"></td>
                                        <td class="moneyline">n/a</td>
                                        <td class="moneyline"></td>
                                        <td></td>
                                        <td></td>
                                        <td class="item-non-mobile" style="padding-left: 35px"><?php echo $sEventDate; ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            
                        <?php
                    }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>

<div id="page-bottom"></div>

<?php

    $sBuffer = ob_get_clean();

    CacheControl::cleanPageCacheWC('team-' . $oFighter->getID() . '-*');
    CacheControl::cachePage($sBuffer, 'team-' . $oFighter->getID() . '-' . strtotime($sLastChange) . '.php');

    echo '<!--C:MIS-->';
}

echo $sBuffer;

?>
