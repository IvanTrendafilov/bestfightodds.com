Manual actions: <a href="#" onclick="$('input[onclick^=\'maAdd\']').click();" >Accept all <b>Create</b> actions below</a>
<br /><br />

<?php

require_once('lib/bfocore/general/class.ScheduleHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');


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
				echo 'Create new event: </td><td>' . $oAction->eventTitle . '</td><td> at </td><td>' . $oAction->eventDate . ' with matchups: <br />' ;
				foreach ($oAction->matchups as $aMatchup)
				{
					echo '&nbsp;' . $aMatchup[0] . ' vs ' . $aMatchup[1] . '<br/>';
				}
				echo '</td><td><input type="submit" value="Accept" onclick="maAddEventWithMatchups(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 2:
			//Rename event
				echo 'Rename event </td><td>' . EventHandler::getEvent($oAction->eventID)->getName() . '</td><td> to </td><td>' . $oAction->eventTitle;
				echo '</td><td><input type="submit" value="Accept" onclick="maRenameEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 3:
			//Change date of event
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Change date of </td><td>' . $oEvent->getName() . '</td><td> from </td><td>' . $oEvent->getDate() . '</td><td> to </td><td>' . $oAction->eventDate;
				echo '</td><td><input type="submit" value="Accept" onclick="maRedateEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 4:
			//Delete event
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Delete event </td><td>' . $oEvent->getName() . ' - ' . $oEvent->getDate();
				echo '</td><td><input type="submit" value="Accept" onclick="maDeleteEvent(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 5:
			//Create matchup
				$oEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Create matchup </td><td>' . $oAction->matchups[0]->team1 . ' vs. ' . $oAction->matchups[0]->team2 . '</td><td> at </td><td>' . EventHandler::getEvent($oAction->eventID)->getName();
				echo '</td><td><input type="submit" value="Accept" onclick="maAddMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 6:
			//Move matchup
				$oMatchup = EventHandler::getFightByID($oAction->matchupID);
				$oOldEvent = EventHandler::getEvent($oMatchup->getEventID());
				$oNewEvent = EventHandler::getEvent($oAction->eventID);
				echo 'Move </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a></td><td> from </td><td>' . $oOldEvent->getName() . ' (' . $oOldEvent->getDate() . ')</td><td> to </td><td>' . $oNewEvent->getName() . ' (' . $oNewEvent->getDate() . ')';
				echo '</td><td><input type="submit" value="Accept" onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 7:
			//Delete matchup
				$oMatchup = EventHandler::getFightByID($oAction->matchupID);
				//Check if matchup has odds and the indicate that 
				$odds = OddsHandler::getOpeningOddsForMatchup($oAction->matchupID);

				//Check if either fighter has another matchup scheduled and indicate that
				$matchups1 = EventHandler::getAllFightsForFighter($oMatchup->getTeam(1));
				$found1 = false;
				foreach ($matchups1 as $matchup)
				{
					$found1 = ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)));
				}
				$matchups2 = EventHandler::getAllFightsForFighter($oMatchup->getTeam(2));
				$found2 = false;
				foreach ($matchups2 as $matchup)
				{
					$found2 = ($matchup->isFuture() && ($matchup->getFighterID(1) != $oMatchup->getFighterID(1) || $matchup->getFighterID(2) != $oMatchup->getFighterID(2)));
				}

				$oEvent = EventHandler::getEvent($oMatchup->getEventID());
				echo 'Delete </td><td><a href="http://www.google.com/search?q=tapology ' . urlencode($oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2)) . '">' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . '</a> ' . ($odds == null ? ' (no odds)' : ' (has odds) ') . ' ' . ($found1 == false ? '' : ' (1 has other matchup)') . ' ' . ($found2 == false ? '' : ' (2 has other matchup)') . '</td><td> from </td><td>' . $oEvent->getName() . ' (' . $oEvent->getDate() .')';
				echo '</td><td><input type="submit" value="Accept" onclick="maDeleteMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($aManualAction['description']). '\')" />
				';
			break;
			case 8:
			//Move matchup to a non-existant event
			
				$oNewEvent = EventHandler::getEventByName($oAction->eventTitle);
				echo 'Move the following matchups</td><td> to </td><td>' . ($oNewEvent != null ? $oNewEvent->getName() : 'TBD') . '<br />
';
				foreach ($oAction->matchupIDs as $sMatchup)
				{
					$oMatchup = EventHandler::getFightByID($sMatchup);
					echo '&nbsp;' . $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2). '';
					$newMA = json_encode(array('matchupID' => $sMatchup, 'eventID' => ($oNewEvent != null ? $oNewEvent->getID() : '-9999')), JSON_HEX_APOS | JSON_HEX_QUOT);
					echo '</td><td><input type="submit" value="Accept" ' .  ($oNewEvent == null ? ' disabled=true ' : '') . ' onclick="maMoveMatchup(' . $aManualAction['id'] . ', \'' . htmlspecialchars($newMA). '\')" /><br/>
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