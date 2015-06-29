    <?php
    require_once('lib/bfocore/general/class.EventHandler.php');
    require_once('lib/bfocore/general/class.OddsHandler.php');
    require_once('lib/bfocore/general/class.FighterHandler.php');

    $oFighter = null;
    if (!isset($_GET['fighterID']) || ($oFighter = FighterHandler::getFighterByID($_GET['fighterID'])) == null)
    {
        //Headers already sent so redirect must be done using js
        echo '<script type="text/javascript">
            <!--
            window.location = "/"
            //-->
            </script>';
        exit();
    }

    $aFights = EventHandler::getAllFightsForFighter($_GET['fighterID']);
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
                               <table class="alerts-matchup-table" cellspacing="0" style="width: 700px; border-color: #b0b0b0; border-style: solid; border-width: 0 1px;" summary="<?php echo $oFighter->getNameAsString(); ?>'s odds history">
                    <thead>
                        <tr>
                            <th>
                                Event
                            </th>
                            <th>
                                Date
                            </th>
                            <th colspan="2" style="text-align:center;">
                                Opening line
                            </th>
                            <th colspan="2" style="text-align:center;">
                                Current best line
                            </th>
                        </tr>
                        </thead>

    <tbody>
                        <?php
                        $sRowSwitch = '';
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
                                $sEventDate = date('M jS Y', strtotime($oEvent->getDate()));
                            }
                            ?>
                                    <?php
                                    if ($oFightOdds1 != null && $oFightOdds2 != null && $oEvent != null)
                                    {
                                            ?>
                                                <tr <?php echo ($sRowSwitch == 'alerts-row-odd' ? 'class="' . $sRowSwitch . '"' : ''); ?>>
                                                    <td>
                                                        <?php echo $oEvent->getName(); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $sEventDate; ?>
                                                    </td>
                                                    <td style="text-align:center;"><?php echo $oFight->getFighterAsString(1); ?><br /><b><?php echo $oOpeningOdds->getFighterOddsAsString(1); ?></b></td>
                                                    <td style="text-align:center;"><?php echo $oFight->getFighterAsString(2); ?><br /><b><?php echo $oOpeningOdds->getFighterOddsAsString(2); ?></b></td>
                                                    <td style="text-align:center;"><?php echo $oFight->getFighterAsString(1); ?><br /><b><?php echo $oFightOdds1->getFighterOddsAsString(1); ?></b></td>
                                                    <td style="text-align:center;"><?php echo $oFight->getFighterAsString(2); ?><br /><b><?php echo $oFightOdds2->getFighterOddsAsString(2); ?></b></td>
                                                </tr>


                                            <?php
                                        }
                                        

                                    ?>

                            <?php
                            if ($sRowSwitch == 'alerts-row-odd')
                            {
                                $sRowSwitch = 'alerts-row-even';
                            }
                            else
                            {
                                $sRowSwitch = 'alerts-row-odd';
                            }

                        }
                        ?>
                                                                </tbody>
                                </table>
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
