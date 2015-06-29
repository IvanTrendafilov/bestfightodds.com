<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.Alerter.php');


$oEvent = null;
if (isset($_GET['eventID']) && $oEvent = EventHandler::getEvent($_GET['eventID'], true))
{
  echo '<form method="post" action="logic/logic.php?action=addFight"  name="addFightForm">';
  echo '<input type="hidden" name="eventID" value="' . $_GET['eventID'] . '" />';
  echo '<input type="hidden" name="returnPage" value="addNewFightForm$eventID=' . $_GET['eventID'] . '" />';
  
  echo '<table class="eventsOverview">';

  $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), false);
  $sRowSwitch = ' class="oddRow" ';
  
  echo '<tr><th colspan="4">' . $oEvent->getName() . ' <span style="color: #777777">-</span> ' . $oEvent->getDate() . ' &nbsp;<a href="?p=changeEventForm&eventID=' . $oEvent->getID() . '">edit</a> <span style="color: #ffffff">' . sizeof($aFights) . '</span></th></tr>';
  
	foreach ($aFights as $oFight)
	{
    $aInfo = Alerter::getArbitrageInfo($oFight->getID(), 100);
    $sArbInfo = '';
    if ($aInfo != null)
		{
			$sArbInfo = '' . $aInfo['profit'] . '';		
		}
		echo '		
			<tr' . $sRowSwitch . '>
				<td class="eventID"><a href="index.php?p=changeFightForm&fightID=' . $oFight->getID() . '">' . $oFight->getID() . '</a></td>
				<td class="fight"><a href="index.php?p=addFighterAltName&fighterName=' . $oFight->getFighter(1) . '">' . $oFight->getFighterAsString(1) . '</a> <span style="color: #777777">vs</span> <a href="index.php?p=addFighterAltName&fighterName=' . $oFight->getFighter(2) . '">' . $oFight->getFighterAsString(2) . '</a></td>
				<td class="arbitrage">' . $sArbInfo . '</td>
				<td style="text-align: center;"><a href="logic/logic.php?action=removeFight&fightID=' . $oFight->getID() . '&returnPage=addNewFightForm$eventID=' . $_GET['eventID'] . '" onclick="javascript:return confirm(\'Really remove ' . $oFight->getFighterAsString(1) . ' vs ' . $oFight->getFighterAsString(2) . '?\')" /><b>x</b></a></td>
			</tr>';
			
		if ($sRowSwitch == '')
		{
			$sRowSwitch = ' class="oddRow" ';
		}
		else
		{
			$sRowSwitch = '';
		}
	}

    echo '		
			<tr style="border-top: 1px solid black; background-color: #dddddd; ">

				<td class="fight" colspan="2"><input type="text" id="fighter1" name="fighter1NameManual" /> <span style="color: #777777">vs</span> <input type="text" id="fighter2" name="fighter2NameManual" /></td>
				<td colspan="2" style="text-align: center;"><input type="submit" value="Add" /></td>
			</tr>';
  echo '</form>';
  echo '</table><br />';
  echo '<div style=""><form method="post" action="logic/parseMJList.php"><input type="hidden" name="eventID" value="' . $_GET['eventID'] . '" /><textarea name="input" style="width: 450px; height: 200px;"></textarea><input type="submit" value="Parse" /></form></div>';
}
else if (isset($_GET['inFighter1']) && isset($_GET['inFighter2']))
{
  $aEvents = EventHandler::getAllUpcomingEvents();
  echo '<form method="post" action="logic/logic.php?action=addFight"  name="addFightForm">';
  echo '<input type="hidden" name="fighter1NameManual" value="' . $_GET['inFighter1'] . '" />';
  echo '<input type="hidden" name="fighter2NameManual" value="' . $_GET['inFighter2'] . '" />';
  echo 'Add new fight:<br /><br />&nbsp;&nbsp;&nbsp;<b>' . $_GET['inFighter1'] . '</b> vs <b>' . $_GET['inFighter2']  . '</b><br /><br />to&nbsp;&nbsp;';
  echo '<select name="eventID">';
  foreach($aEvents as $oEvent)
  {
    echo '<option value="' . $oEvent->getID() . '">' . $oEvent->getName() . '</option>';
  }
  echo '</select>&nbsp;&nbsp;<input type="submit" value="Add fight" /></form>';
}
else
{
  $aEvents = EventHandler::getAllUpcomingEvents();
  echo '<form method="get" action="index.php"><input type="hidden" name="p" value="addNewFightForm" />Event: <select name="eventID">';
  foreach($aEvents as $oEvent)
  {
    echo '<option value="' . $oEvent->getID() . '">' . $oEvent->getName() . '</option>';
  }
  echo '</select>&nbsp;&nbsp;<input type="submit" value="Select" /></form>';
}


?>

<script language="JavaScript" type="text/javascript">
<!--

window.onload = function() { document.addFightForm.fighter1NameManual.focus(); };

//-->
</script>
