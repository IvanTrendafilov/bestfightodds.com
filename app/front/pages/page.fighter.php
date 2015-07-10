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
            <h1>
        <?php echo $oFighter->getNameAsString(); ?>
        </h1>
    </div>
        <script type="text/javascript">getTeamSpreadChart(<?php echo $_GET['fighterID']; ?>);</script>
                <div class="fighter-history-container" style="display: inline-block">

                    <table class="odds-table" cellspacing="0" summary="Odds for <?php echo $oFighter->getNameAsString(); ?>">
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


                        $aGraphData = GraphHandler::getMedianSparkLine($oFight->getID(), ($oFight->getFighterID(1) == $oFighter->getID() ? 1 : 2));
                        var_dump($aGraphData);
                        $sEventDate = '';
                        //TODO: Hardcoded reference to "FUTURE EVENTS". Should be changed to set id
                        if (strtoupper($oEvent->getName()) != 'FUTURE EVENTS')
                        {
                            $sEventDate = date('M jS Y', strtotime($oEvent->getDate()));
                        }
                        //<a href="/fights/<?=$oFight->getFightAsLinkString()"><img src="/img/info-arrow.gif" class="small-button" alt="View matchup" title="Display matchup" style="float: right; margin-top: 1px;"/></a>
                        ?>
                            
                                <?php
                                if ($oFightOdds1 != null && $oFightOdds2 != null && $oEvent != null)
                                {
                                    if ($oFightOdds1->getBookieID() == $oFightOdds2->getBookieID())
                                    {
                                        ?>
                                            <tr>
                                                <th scope="row"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(1); ?></span></td>
                                                <td></td>
                                                <td data-sparkline="68, 52, 80, 96 "/>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,1,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Betting line movement" title="Betting line movement" /></a></td>';
                                                ?>
                                            </tr>
                                            <tr class="odd">
                                                <th scope="row"><?php echo $sEventDate; ?></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(2); ?></span></td>
                                                <td></td>
                                                <td data-sparkline="68, 52, 80, 96 "/>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,2,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Betting line movement" title="Betting line movement" /></a></td>';
                                                ?>
                                            </tr>
                                        
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                            <tr>
                                                <th scope="row"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds1->getFighterOddsAsString(1); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds2->getFighterOddsAsString(1); ?></span></td>
                                                <td data-sparkline="68, 52, 80, 96 "/>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,1,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Betting line movement" title="Betting line movement" /></a></td>';
                                                ?>
                                            </tr>
                                            <tr class="odd">
                                                <th scope="row"><?php echo $sEventDate; ?></th>
                                                <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>"><?php echo $oOpeningOdds->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="normalbet"><?php echo $oFightOdds1->getFighterOddsAsString(2); ?></span></td>
                                                <td align="center" class="moneyline"><span id="oddsID<?php echo $iCellCounter++; ?>" class="bestbet"><?php echo $oFightOdds2->getFighterOddsAsString(2); ?></span></td>
                                                <td data-sparkline="68, 52, 80, 96 "/>
                                                <?php
                                                echo '<td class="button-cell"><a href="#" onclick="return sI(this,2,' . $oFight->getID() . ', \'\');"><img src="/img/graph.gif" class="small-button" alt="Betting line movement" title="Betting line movement" /></a></td>';
                                                ?>
                                            </tr>

                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <tr>
                                        <th scope="row"><a href="/events/<?php echo $oEvent->getEventAsLinkString(); ?>" style="font-size: 12px;"><?php echo $oEvent->getName(); ?></a></th>
                                        <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a>'; ?></td>
                                        <td align="center" class="moneyline" colspan="3">n/a</td>
                                        <td></td>
                                    </tr>
                                    <tr class="odd">
                                        <th scope="row"><?php echo $sEventDate; ?></th>
                                        <td><?php echo '<a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a>'; ?></td>
                                        <td align="center" class="moneyline" colspan="3">n/a</td>
                                        <td></td>
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
