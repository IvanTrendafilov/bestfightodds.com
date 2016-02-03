Manual actions: <a href="#" onclick="$('input[onclick^=\'maAdd\']').click();" >Accept all <b>Create</b> actions below</a>
<br /><br />

<?php

require_once('lib/bfocore/general/class.ScheduleHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');


$aManualActions = ScheduleHandler::getAllManualActions();

echo '<table class="genericTable">';

$iCounter = 0;

if ($aManualActions != null && sizeof($aManualActions) > 0)
{
	foreach($aManualActions as $aManualAction)
	{
		echo '<tr id="ma' . $aManualAction['id'] . '"><td>';
		$oAction = json_decode($aManualAction['description']);
		switch ((int) $aManualAction['type'])
		{
			case 1:
			//Create event and matchups
				echo 'Create new event: ' . $oAction->eventTitle . ' at ' . $oAction->eventDate . ' with matchups: <br />' ;
				foreach ($oAction->matchups as $aMatchup)
				{
					echo '&nbsp;' . $aMatchup[0] . ' vs ' . $aMatchup[1] . '<br/>';
				}
				echo '<input type="submit" value="Accept" style="float: right" onclick="maAddEventWithMatchups(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 2:
			//Rename event
				echo 'Rename event ' . EventHandler::getEvent($oAction->eventID)->getName() . ' to ' . $oAction->eventTitle;
				echo '<input type="submit" value="Accept" style="float: right" onclick="maRenameEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 3:
			//Change date of event
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Change date of ' . $oEvent->getName() . ' from ' . $oEvent->getDate() . ' to ' . $oAction->eventDate;
				echo '<input type="submit" value="Accept" style="float: right" onclick="maRedateEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 4:
			//Delete event
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Delete event ' . $oEvent->getName() . ' - ' . $oEvent->getDate();
				echo '<input type="submit" value="Accept" style="float: right" onclick="maDeleteEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 5:
			//Create matchup
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Create matchup ' . $oAction->matchups[0]->team1 . ' vs. ' . $oAction->matchups[0]->team2 . ' at ' . EventHandler::getEvent($oAction->eventID)->getName();
				echo '<input type="submit" value="Accept" style="float: right" onclick="maAddMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 6:
			//Move matchup
				$oMatchup = EventHandler::getFightByID($oAction->matchupID);
				$oOldEvent = EventHandler::getEvent($oMatchup->getEventID());
				$oNewEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Move ' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . ' from ' . $oOldEvent->getName() . ' (' . $oOldEvent->getDate() . ') to ' . $oNewEvent->getName() . ' (' . $oNewEvent->getDate() . ')';
				echo '<input type="submit" value="Accept" style="float: right" onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 7:
			//Delete matchup
				$oMatchup = EventHandler::getFightByID($oAction->matchupID);
				$oEvent = EventHandler::getEvent($oMatchup->getEventID());
				echo 'Delete ' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . ' from ' . $oEvent->getName() . ' (' . $oEvent->getDate() .')';
				echo '<input type="submit" value="Accept" style="float: right" onclick="maDeleteMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 8:
			//Move matchup to a non-existant event
			
				$oNewEvent = EventHandler::getEventByName($oAction->eventTitle);
				echo 'Move the following matchups to ' . ($oNewEvent != null ? $oNewEvent->getName() : 'TBD') . '<br />
';
				foreach ($oAction->matchupIDs as $sMatchup)
				{
					$oMatchup = EventHandler::getFightByID($sMatchup);
					echo '&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2). '';
					$newMA = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($oNewEvent != null ? $oNewEvent->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
					echo '<input type="submit" value="Accept" ' .  ($oNewEvent == null ? ' disabled=true ' : '') . ' style="float: right" onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($newMA). '\')" /><br/>
';					
				}

			break;
			default:
				echo $aManualAction['description'];
		}

		 echo '</td></tr>';
	}
}

echo '</table>';

?>