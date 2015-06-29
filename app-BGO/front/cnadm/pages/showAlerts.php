<?php

require_once('lib/bfocore/general/class.Alerter.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

echo '<table border="1" cellspacing="3">
<tr><td>E-mail</td><td>Fight</td><td>Fighter</td><td>Bookie</td><td>Limit</td><td>Odds type</td></tr>';

$aAlerts = Alerter::getAllAlerts();
foreach ($aAlerts as $oAlert)
{
	$oFight = EventHandler::getFightByID($oAlert->getFightID());
	
	echo '<tr>';
	
	echo '<td>' . $oAlert->getEmail() . '</td>';
	echo '<td>' . $oFight->getFighterAsString(1) . ' vs ' . $oFight->getFighterAsString(2) . '</td>';
	echo '<td>' . ($oAlert->getLimit() == -9999 ? 'n/a' : $oFight->getFighterAsString($oAlert->getFighter())) . '</td>';
	echo '<td>' . ($oAlert->getBookieID() == -1 ? 'All' : $oAlert->getBookieID()) . '</td>';
	echo '<td>' . ($oAlert->getLimit() == -9999 ? 'Show' : $oAlert->getLimitAsString()) .  '</td>';
	echo '<td>' . $oAlert->getOddsType() . '</td>';
	
	echo '</tr>';
}


?>