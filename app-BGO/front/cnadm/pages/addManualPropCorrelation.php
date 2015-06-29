<?php

require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');

echo '<form method="post" action="logic/logic.php?action=addManualPropCorrelation"  name="addManualPropCorrelationForm">';

echo 'Correlation: <input type="text" id="correlation" name="correlation" size="70" value="' . (isset($_GET['inCorrelation']) ? $_GET['inCorrelation'] : '') . '" /><br /><br />';

echo 'Bookie: <select name="bookieID">';
echo '<option value="0" selected>- pick one -</option>';
$aBookies = BookieHandler::getAllBookies();
foreach ($aBookies as $oBookie)
{
    if (isset($_GET['inBookieID']) && $_GET['inBookieID'] == $oBookie->getID())
    {
        echo '<option value="' . $oBookie->getID() . '" selected>' . $oBookie->getName() . '</option>';
    } else
    {
        echo '<option value="' . $oBookie->getID() . '">' . $oBookie->getName() . '</option>';
    }
}
echo '</select><br /><br />';

echo 'Matchup: <select name="matchupID">';
$aEvents = EventHandler::getAllUpcomingEvents();
                        foreach ($aEvents as $oEvent)
                        {

                            $aMatchups = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
                            if (sizeof($aMatchups) > 0)
                            {
                                echo '<option value="-' . $oEvent->getID() . '">' . $oEvent->getName() . '</option>';
                                foreach ($aMatchups as $oMatchup)
                                {
                                    if (isset($_GET['inMatchupID']) && $_GET['inMatchupID'] == $oBookie->getID())
								    {
								        echo '<option value="' . $oMatchup->getID() . '" selected>&nbsp;&nbsp;&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs ' . $oMatchup->getTeamAsString(2) . '</option>';
								    } else
								    {
								        echo '<option value="' . $oMatchup->getID() . '">&nbsp;&nbsp;&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs ' . $oMatchup->getTeamAsString(2) . '</option>';
								    }
                                }
                                echo '<option value="0"></option>';
                            }
                        }
echo '</select><br /><br />';

echo '<input type="submit" value="Add correlation" />';
echo '</form><br />';

if (isset($_GET['message']))
{
    echo $_GET['message'];
}
?>
