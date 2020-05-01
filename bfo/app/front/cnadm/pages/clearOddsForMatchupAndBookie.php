<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

//Add fighter altname:
echo '

<form method="post" action="logic/logic.php?action=clearOddsForMatchupAndBookie">';

<?php 
$aEvents = EventHandler::getAllUpcomingEvents();
echo 'Matchup: <select name="matchupID">';
foreach($aEvents as $oEvent)
{
	echo '<option value="-1">' . $oEvent->getName() . '</option>';
	$aFights = EventHandler::getAllFightsForEvent($oEvent->getID());
	foreach ($aFights as $oFight)
	{
		echo '<option value="' . $oFight->getID() . '">&nbsp;&nbsp;' . $oFight->getFighterAsString(1) . ' VS ' . $oFight->getFighterAsString(2) . '</option>';
	}
}
echo '</select>';

echo 'Bookie: <select name="bookieID">';
$bookies = BookieHandler::getAllBookies();
foreach($bookies as $bookie)
{
	echo '<option value="' . $bookie->getID() . '">' . $bookie->getName() . '</option>';
}
echo '</select><br /><br />
<input type="submit" value="Remove">
</form>';

?>
