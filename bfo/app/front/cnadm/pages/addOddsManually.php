<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');


echo '<form method="post" action="logic/addManualOdds.php">';
echo 'Bookie: <select name="bookieID">';
$aBookies = BookieHandler::getAllBookies();
foreach($aBookies as $oBookie)
{
	echo '<option value="' . $oBookie->getID() . '">' . $oBookie->getName() . '</option>';
}
echo '</select><br /><br />';

$aEvents = EventHandler::getAllUpcomingEvents();
echo 'Fight: <select name="fightID">';
foreach($aEvents as $oEvent)
{
	echo '<option value="-1">' . $oEvent->getName() . '</option>';
	$aFights = EventHandler::getAllFightsForEvent($oEvent->getID());
	foreach ($aFights as $oFight)
	{
		echo '<option value="' . $oFight->getID() . '">&nbsp;&nbsp;' . $oFight->getFighterAsString(1) . ' VS ' . $oFight->getFighterAsString(2) . '</option>';
	}
}
echo '</select><br /><br />
Odds: &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="fighter1odds" size="5"/> / <input type="text" name="fighter2odds"  size="5"/><br /><br />';

echo '<input type="submit" value="Add odds" onclick="javascript:return confirm(\'Are you sure?\')"/>';
echo '</form>';

?>
