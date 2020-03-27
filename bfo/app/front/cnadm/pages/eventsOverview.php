<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.Alerter.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

$sRowSwitch = '';



if (isset($_GET['show']) && $_GET['show'] == 'all')
{
	$aEvents = EventHandler::getAllEvents();
}
else
{
	$aEvents = EventHandler::getAllUpcomingEvents();
}

echo '<table class="eventsOverview" style="float: left;">';

foreach ($aEvents as $oEvent)
{
	$aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), false);
	
	echo '<tr>
			<th colspan="6"><a name="event' . $oEvent->getID() . '"></a><div style="float: left; ' . ($oEvent->isDisplayed() ? '' : 'font-style: italic; color: #909090;') . '">' . $oEvent->getName() . ' <span style="color: #777777">-</span> ' . $oEvent->getDate() . ' &nbsp;<a href="?p=changeEventForm&eventID=' . $oEvent->getID() . '">edit</a></div><div style="float: right; padding-right: 5px;"><span style="color: #ffffff">' . sizeof($aFights) . '</span> <b><a href="?p=addNewFightForm&eventID=' . $oEvent->getID() . '">add</a></b></div></th>
		</tr>';
	
	foreach ($aFights as $oFight)
	{
		$aInfo = Alerter::getArbitrageInfo($oFight->getID(), 100);
	
		$sArbInfo = '';
		if ($aInfo == null)
		{
		}
		else if ($aInfo['arbitrage'] < 1 && $aInfo['profit'] >= 1)
		{
			$sArbInfo = '<a href="index.php?p=showArbitrage&fightID=' . $oFight->getID() . '">$' . $aInfo['profit'] . '</a>';
		}
		else
		{
			$sArbInfo = '' . $aInfo['profit'] . '';		
		}
		
		if ($sRowSwitch == '')
		{
			$sRowSwitch = ' class="oddRow" ';
		}
		else
		{
			$sRowSwitch = '';
		}
		 
		echo '		
			<tr' . $sRowSwitch . '>
				<td class="eventID"><a href="index.php?p=changeFightForm&fightID=' . $oFight->getID() . '">' . $oFight->getID() . '</a></td>
				<td class="fight"' . ($oFight->isMainEvent() ? ' style="font-weight: bold" ' : '') . '><a href="index.php?p=addFighterAltName&fighterName=' . $oFight->getFighter(1) . '">' . $oFight->getFighterAsString(1) . '</a> <span style="color: #777777">vs</span> <a href="index.php?p=addFighterAltName&fighterName=' . $oFight->getFighter(2) . '">' . $oFight->getFighterAsString(2) . '</a></td>
				<td class="arbitrage">' . $sArbInfo . '</td>
				<td class="imageLink"><a href="https://www.bestfightodds.com/fights/' . $oFight->getID() . '.png">o</a></td>
				<td><a href="logic/logic.php?action=removeFight&fightID=' . $oFight->getID() . '&returnPage=eventsOverview" onclick="javascript:return confirm(\'Really remove ' . $oFight->getFighterAsString(1) . ' vs ' . $oFight->getFighterAsString(2) . '?\')" /><b>x</b></a></td>
				<td class="mainEvent"><a href="logic/logic.php?action=setFightAsMainEvent&fightID=' . $oFight->getID() . '&isMain=' . ($oFight->isMainEvent() ? '0' : '1') . '&returnPage=eventsOverview" />' . ($oFight->isMainEvent() ? 'v' : '^') . '</a></td>
			</tr>';
	}
}

echo '</table>';

echo '<p style="font-size: 10px; line-height: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Quick jump to</b><br />';
foreach ($aEvents as $oEvent)
{
    echo '&nbsp;&nbsp;&nbsp;<a href="#event' . $oEvent->getID() . '" style="color: #000000;">' . $oEvent->getName() . '</a><br />';
}
echo '</p>';

?>