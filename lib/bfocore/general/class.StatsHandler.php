<?php

require_once('lib/bfocore/dao/class.StatsDAO.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.OddsTools.php');

class StatsHandler
{
	/*public static function getTopSwingsForEvent($a_iEventID)
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
	}*/

	public static function getAllDiffsForEvent($a_iEventID, $a_iFrom = 0) //0 Opening, 1 = 1 day ago, 2 = 1 hour ago
	{
		$aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
		$aSwings = [];
		foreach ($aMatchups as $oMatchup)
		{
			$aStats = StatsHandler::getDiffForMatchup($oMatchup->getID(), $a_iFrom);

			$aSwings[] = array($oMatchup, 1, $aStats['f1']);
			$aSwings[] = array($oMatchup, 2, $aStats['f2']);
		}

		if(!function_exists('cmpdiff')) {
			function cmpdiff($a, $b)
			{
			    return $a[2]['swing'] < $b[2]['swing'];
			}
		}
		usort($aSwings, "cmpdiff");

		return $aSwings;
	}

	public static function getDiffForMatchup($a_iMatchupID, $a_iFrom = 0)
	{
		if (!is_numeric($a_iMatchupID))
		{
			return null;
		}
		return StatsDAO::getDiffForMatchup($a_iMatchupID, $a_iFrom);
	}


	public static function getExpectedOutcomesForEvent($a_iEventID)
	{
		$aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
		$aOutcomes = [];
		foreach ($aMatchups as $oMatchup)
		{
			$aMatchupOutcomes = self::getExpectedOutcomesForMatchup($oMatchup);
			if ($aMatchupOutcomes != null)
			{
				$aOutcomes[] = array($oMatchup, self::getExpectedOutcomesForMatchup($oMatchup));	
			}
		}
		return $aOutcomes;
	}

	public static function getExpectedOutcomesForMatchup($oMatchup)
	{
		//Hardcoded proptype IDs here.. might wanna fix this
		$oTeam1ITD = OddsHandler::getCurrentPropIndex($oMatchup->getID(), 1, 10, ($oMatchup->hasOrderChanged() ? 2 : 1)); //Proptype 10: wins inside distance
		$oTeam1DEC = OddsHandler::getCurrentPropIndex($oMatchup->getID(), 1, 11, ($oMatchup->hasOrderChanged() ? 2 : 1)); //Proptype 11: wins by decision
		$oTeam2ITD = OddsHandler::getCurrentPropIndex($oMatchup->getID(), 1, 10, ($oMatchup->hasOrderChanged() ? 1 : 2)); //Proptype 10: wins inside distance
		$oTeam2DEC = OddsHandler::getCurrentPropIndex($oMatchup->getID(), 1, 11, ($oMatchup->hasOrderChanged() ? 1 : 2)); //Proptype 11: wins by decision
		$oDraw = OddsHandler::getCurrentPropIndex($oMatchup->getID(), 1, 6, 0); //Proptype 6: fight is a draw

		//All is required to be able to draw some conclusion
		if ($oTeam1ITD == null && $oTeam1DEC == null && $oTeam2ITD == null && $oTeam2DEC == null && $oDraw == null)
		{
		return ['team1_itd' => 0,
				'team1_dec' => 0,
				'team2_itd' => 0,
				'team2_dec' => 0,
				'draw' => 0];
		}

		$sum = OddsTools::convertMoneylineToDecimal($oTeam1ITD->getPropOdds(1)) - 1
			+ OddsTools::convertMoneylineToDecimal($oTeam1DEC->getPropOdds(1)) - 1
			+ OddsTools::convertMoneylineToDecimal($oTeam2ITD->getPropOdds(1)) - 1
			+ OddsTools::convertMoneylineToDecimal($oTeam2DEC->getPropOdds(1)) - 1
			+ OddsTools::convertMoneylineToDecimal($oDraw->getPropOdds(1)) - 1;

		$ret = ['team1_itd' => round($sum / (OddsTools::convertMoneylineToDecimal($oTeam1ITD->getPropOdds(1)) - 1)),
				'team1_dec' => round($sum / (OddsTools::convertMoneylineToDecimal($oTeam1DEC->getPropOdds(1)) - 1)),
				'team2_itd' => round($sum / (OddsTools::convertMoneylineToDecimal($oTeam2ITD->getPropOdds(1)) - 1)),
				'team2_dec' => round($sum / (OddsTools::convertMoneylineToDecimal($oTeam2DEC->getPropOdds(1)) - 1)),
				'draw' => round($sum / (OddsTools::convertMoneylineToDecimal($oDraw->getPropOdds(1)) - 1))];


		return $ret;
	}

}


?>