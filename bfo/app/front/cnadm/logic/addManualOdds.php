<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.OddsTools.php');

if (!isset($_POST['bookieID']) || !isset($_POST['fightID']) || $_POST['fighter1odds'] == "" || $_POST['fighter2odds'] == "")
{
	echo 'Missing parameters';
	exit();
}

if ($_POST['fightID'] == -1)
{
	echo 'No fight selected';
	exit();
}

if (!OddsTools::checkCorrectOdds($_POST['fighter1odds']) || !OddsTools::checkCorrectOdds($_POST['fighter2odds']))
{
	echo 'Odds in invalid format';
	exit();
}

$oTempFightOdds = new FightOdds($_POST['fightID'], $_POST['bookieID'], $_POST['fighter1odds'], $_POST['fighter2odds'], OddsTools::standardizeDate(date('Y-m-d')));

if (EventHandler::checkMatchingOdds($oTempFightOdds))
{
	echo 'Odds havent changed';
	exit();
}

$bSuccess = EventHandler::addNewFightOdds($oTempFightOdds);

if ($bSuccess == true)
{
	echo 'Odds manually added';
}
else
{
	echo 'Error adding odds';
}



?>