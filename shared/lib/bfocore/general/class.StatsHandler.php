<?php

require_once('lib/bfocore/dao/class.StatsDAO.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.OddsTools.php');
require_once('lib/bfocore/general/class.PropTypeHandler.php');
require_once('lib/bfocore/utils/class.LinkTools.php');

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

		//Odds for all prop types in category is required to be able to draw some conclusion
		if ($oTeam1ITD == null || $oTeam1DEC == null || $oTeam2ITD == null || $oTeam2DEC == null || $oDraw == null)
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

	/* Generalized function based on getExpectedOutcomeForMatchup above
	
	- This works by combining the avg of each prop in category and checking how big part of the sum that single prop makes up
	- Will not make sense for certain categories and may need to be restricted to a specific team in order to give a usable split 
	 
	Open point, currently assumes posprop = looking only at the positive part of the prop. Should this be parameterized or should we combine the two somehow?
	*/
	public static function getExpectedOutcome($matchup_id, $prop_category_id, $team = null)
	{	
		$matchup = EventHandler::getFightByID($matchup_id);
		$sum = 0;
		$posprop = 1; //See restriction on using only posprop above
		$prop_odds_store = []; //Used to temporary store prop odds for later reference when summarizing

		$ret = [];

		//Get all props related to the prop_category
		$proptypes = PropTypeHandler::getPropTypes($prop_category_id);
		//Loop through and get index
		foreach ($proptypes as $proptype)
		{
			$i = 0;
			$i_limit = 1;
			//If prop is team based we need to get both sides so we will run it twice
			if ($proptype->isTeamSpecific() == true)
			{
				$i_limit = 2;
			}
			$prop_odds_store[$proptype->getID()] = [];
			for ($x = 1; $x <= $i_limit; $x++)
			{
				//Flip if internal order of matchup has changed or if we are in the second iteration
				$matchup_side = ($matchup->hasOrderChanged() ? 2 : 1);
				if ($x == 2)
				{
					$matchup_side = $matchup_side % 2 + 1;
				}

				$prop_avg_odds = OddsHandler::getCurrentPropIndex($matchup->getID(), $posprop, $proptype->getID(), $matchup_side);
				//Odds for all prop types in category is required to be able to draw some conclusion. Return if one is missing
				
				if ($prop_avg_odds == null)
				{
					return null;
				}
				//Summarize to later determine fraction for each
				$sum += OddsTools::convertMoneylineToDecimal($prop_avg_odds->getPropOdds($posprop)) - 1;

				//Store for later use
				$prop_odds_store[$proptype->getID()][$x] = $prop_avg_odds;
			}
		}

		foreach ($proptypes as $proptype)
		{
			$i = 0;
			$i_limit = 1;
			//If prop is team based we need to get both sides so we will run it twice
			if ($proptype->isTeamSpecific() == true)
			{
				$i_limit = 2;
			}

			for ($x = 1; $x <= $i_limit; $x++)
			{
				//Cannonnicalize the name of the prop
				//If team specific, replace <T> with <T1> and <T2>
				$prop_desc = str_replace('<T>','<T' . $x . '>', $proptype->getPropDesc($posprop));
				$proptype_technicalname = LinkTools::slugString($prop_desc);
				//Calculate part for each
				$ret[$proptype_technicalname]['score'] = round($sum / (OddsTools::convertMoneylineToDecimal($prop_odds_store[$proptype->getID()][$x]->getPropOdds($posprop)) - 1));
				$ret[$proptype_technicalname]['odds_ml'] = $prop_odds_store[$proptype->getID()][$x]->getPropOdds($posprop);
			}
		}

		return $ret;
	}


}


?>