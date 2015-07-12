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

<div id="page-wrapper">

    
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
            <h1 id="team-name"><?php echo $oFighter->getNameAsString(); ?></h1>
    </div>
                <div class="fighter-history-container" style="display: inline-block">

                    <table class="team-stats-table" cellspacing="0" summary="Odds for <?php echo $oFighter->getNameAsString(); ?>">
                        <thead>
                            <th>Event</th>
                            <th>Matchup</th>
                            <th>Open</th>
                            <th colspan="2">Close</th>
                            <th></th>
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

                        $sGraphData = GraphHandler::getMedianSparkLine($oFight->getID(), ($oFight->getFighterID(1) == $oFighter->getID() ? 1 : 2));

                        $sEventDate = '';
                        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
                        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
                        {
                            $sEventDate = date('M jS Y', strtotime($oEvent->getDate()));
                        }
                        ?>
                            
                                <?php
                                if ($oFightOdds1 != null && $oFightOdds2 != null && $oEvent != null)
                                {
                                    if ($oFightOdds1->getBookieID() == $oFightOdds2->getBookieID())
                                    {
                                        ?>
                                            <tr>
                                                <th scope="row" rowspan="2"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a><br><?php echo $sEventDate; ?></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iTeamPos) . '">' . $oFight->getFighterAsString($iTeamPos) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iTeamPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString($iTeamPos); ?></span></td>
                                                <td></td>
                                                <td class="chart-cell" rowspan="2" data-sparkline="<?php echo $sGraphData; ?>" data-li="[<?php echo $oFight->getID(); ?>,<?php echo $iTeamPos; ?>]" />
                                            </tr>
                                            <tr class="odd">
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iOtherPos) . '">' . $oFight->getFighterAsString($iOtherPos) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iOtherPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString($iOtherPos); ?></span></td>
                                                <td></td>
                                            </tr>
                                        
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                            <tr>
                                                <th scope="row" rowspan="2"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a><br><?php echo $sEventDate; ?></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iTeamPos) . '">' . $oFight->getFighterAsString($iTeamPos) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iTeamPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString($iTeamPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds2->getFighterOddsAsString($iTeamPos); ?></span></td>
                                                <td class="chart-cell" rowspan="2" data-sparkline="<?php echo $sGraphData; ?>" data-li="[<?php echo $oFight->getID(); ?>,<?php echo $iTeamPos; ?>]" />
                                            </tr>
                                            <tr class="odd">
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iOtherPos) . '">' . $oFight->getFighterAsString($iOtherPos) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString($iOtherPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds1->getFighterOddsAsString($iOtherPos); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds2->getFighterOddsAsString($iOtherPos); ?></span></td>
                                            </tr>

                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tr>
                                        <th scope="row" rowspan="2"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a><br><?php echo $sEventDate; ?></th>
                                        <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iTeamPos) . '">' . $oFight->getFighterAsString($iTeamPos) . '</a>'; ?></td>
                                        <td align="center" class="moneyline" colspan="3">n/a</td>
                                        <td rowspan="2"></td>
                                    </tr>
                                    <tr class="odd">
                                        <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString($iOtherPos) . '">' . $oFight->getFighterAsString($iOtherPos) . '</a>'; ?></td>
                                        <td align="center" class="moneyline" colspan="3">n/a</td>
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
