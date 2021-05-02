<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');


$iEvent = $_GET['eventID'];



$aFights = EventHandler::getAllFightsForEvent($iEvent, true);
$oEvent = EventHandler::getEvent($iEvent);


echo '<table border=1>';
echo '<tr><td>event_id</td><td>event_name</td><td>event_date</td><td>fighter1_id</td><td>fighter1_name</td><td>fighter1_line</td><td>fighter2_id</td><td>fighter2_name</td><td>fighter2_line</td></tr>';

for ($iFightX = 0; $iFightX < count($aFights); $iFightX++) {
    $oFight = $aFights[$iFightX];
    $oFightOdds = EventHandler::getBestOddsForFight($oFight->getID());
    echo '<tr><td>' . $iEvent . '</td>
				<td>' . $oEvent->getName() . '</td>
				<td> ' . $oEvent->getDate() . '</td>
				<td>' . $oFight->getFighterID(1) . '</td>
				<td>' . $oFight->getTeam(1) . '</td>
				<td>' . $oFightOdds->getFighterOddsAsString(1) . '</td>
				<td>' . $oFight->getFighterID(2) . '</td>
				<td>' . $oFight->getTeam(2) . '</td>
				<td>' . $oFightOdds->getFighterOddsAsString(2) . '</td>
				</tr>';
}

echo '</table>';
