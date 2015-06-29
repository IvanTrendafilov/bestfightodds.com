<?php

require_once('lib/bfocore/general/class.Alerter.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');


if (!isset($_GET['arbitrage']) || $_GET['arbitrage'] == '')
{
	$iArbitrage = 100;
}
else
{
	$iArbitrage = $_GET['arbitrage'];
}


if (isset($_GET['fightID']) && $_GET['fightID'] != '')
{
	$oFight = EventHandler::getFightByID($_GET['fightID']);
	$aInfo = Alerter::getArbitrageInfo($_GET['fightID'], $iArbitrage);

	echo 'Amount: ' . $iArbitrage . '<br />';
	echo 'Bet &nbsp;<b>$' . $aInfo['fighter1bet'] . '</b>&nbsp; on &nbsp;<b>' . $oFight->getFighterAsString(1) . '</b>&nbsp; at &nbsp;<b>' . $aInfo['fighter1odds'] . '</b><br />';
	echo 'Bet &nbsp;<b>$' . $aInfo['fighter2bet'] . '</b>&nbsp; on &nbsp;<b>' . $oFight->getFighterAsString(2) . '</b>&nbsp; at &nbsp;<b>' . $aInfo['fighter2odds'] . '</b><br />';
	echo '<br />Profit: &nbsp;<b>$' . $aInfo['profit'] . '</b><br /><br />';

}

?>