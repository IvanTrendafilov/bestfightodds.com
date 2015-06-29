<?php

require_once('lib/bfocore/dao/class.StatsDAO.php');
require_once('lib/bfocore/general/class.EventHandler.php');

class StatsHandler
{
	public static function getTopSwingsForEvent($a_iEventID)
	{
		$aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
		$aSwings = array();
		foreach ($aMatchups as $oMatchup)
		{
			$aSwings[] = array($oMatchup, StatsHandler::getSwingForMatchup($oMatchup->getID()));
		}

		function cmpswing($a, $b)
		{
		    return $a[1] < $b[1];
		}
		usort($aSwings, "cmpswing");

		return $aSwings;
	}

	public static function getSwingForMatchup($a_iMatchupID)
	{
		if (!is_numeric($a_iMatchupID))
		{
			return null;
		}
		return StatsDAO::getSwingForMatchup($a_iMatchupID);
	}

	public static function getAllDiffsForEvent($a_iEventID)
	{
		$aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
		$aSwings = array();
		foreach ($aMatchups as $oMatchup)
		{
			$aStats = StatsHandler::getDiffForMatchup($oMatchup->getID());

			$aSwings[] = array($oMatchup, 1, $aStats['f1']);
			$aSwings[] = array($oMatchup, 2, $aStats['f2']);
		}

		function cmpdiff($a, $b)
		{
		    return $a[2]['swing'] < $b[2]['swing'];
		}
		usort($aSwings, "cmpdiff");

		return $aSwings;
	}

	public static function getDiffForMatchup($a_iMatchupID)
	{
		if (!is_numeric($a_iMatchupID))
		{
			return null;
		}
		return StatsDAO::getDiffForMatchup($a_iMatchupID);
	}
}


?>