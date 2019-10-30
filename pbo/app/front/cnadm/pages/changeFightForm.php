<?php

require_once('lib/bfocore/general/class.EventHandler.php');

if (!isset($_GET['fightID']) || $_GET['fightID'] == '')
{
	exit();
}

$oCurrentFight = EventHandler::getFightByID($_GET['fightID']);
if ($oCurrentFight == null)
{
	exit();
}

echo '
<form method="get" action="logic/logic.php">
  <input type="hidden" name="action" value="updateFight" />
  <input type="hidden" name="returnPage" value="eventsOverview" />
  Fight: ' . $oCurrentFight->getFighterAsString(1) . ' vs ' . $oCurrentFight->getFighterAsString(2) . '<br />
	Fight ID: ' . $oCurrentFight->getID() . '<input type="hidden" name="fightID" value="' . $oCurrentFight->getID() . '" /><br />
	Fight event: <select name="eventID">';
	$aEvents = EventHandler::getAllUpcomingEvents();
  foreach($aEvents as $oEvent)
  {
    echo '<option value="' . $oEvent->getID() . '" ' . ($oEvent->getID() == $oCurrentFight->getEventID() ? 'selected' : '') . '>' . $oEvent->getName() . '</option>';
  }
  echo '</select><br /><br />
	<input type="submit" value="Update fight" />
</form>
';



?>
