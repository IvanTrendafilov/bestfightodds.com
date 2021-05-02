<?php

require_once('lib/bfocore/general/class.EventHandler.php');

if (!isset($_GET['eventID']) || $_GET['eventID'] == '') {
    exit();
}

$oCurrentEvent = EventHandler::getEvent($_GET['eventID']);
if ($oCurrentEvent == null) {
    exit();
}

echo '
<form method="post" action="logic/logic.php?action=updateEvent">
	Event ID: ' . $oCurrentEvent->getID() . '<input type="hidden" name="eventID" value="' . $oCurrentEvent->getID() . '" /><br />
	Event name: <input type="text" name="eventName" value="' . $oCurrentEvent->getName() . '" size="40" /><br />
	Event date: <input type="text" name="eventDate" value="' . $oCurrentEvent->getDate() . '" /><br />
    Display event: <input type="checkbox" name="eventDisplay" ' . ($oCurrentEvent->isDisplayed() ? 'checked' : '') . ' /><br /><br />
	<input type="submit" value="Update event" />
</form>
';
