<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.OddsTools.php');

/**
 * This class is used to do creative matches for matchups that were not initially matched
 *
 * Currently not so creative but we'll get there..
 */

class CreativeMatcher
{
	/**
	 * Checks a matchup and determines if there is an existing matchup to match it to and if not, a new one is created
	 */
	public static function checkMatching($a_oParsedMatchup)
	{
		$aPotentialMatches = array();
		//Fetch all upcoming matchups first and check if one side matches
		$aMatchups = EventHandler::getAllUpcomingMatchups();
		foreach ($aMatchups as $oMatchup) 
		{
			if (self::compareSingleMatchup($oMatchup, $a_oParsedMatchup))
			{
				$aPotentialMatches[] = $oMatchup;
			}
		}

		if (count($aPotentialMatches) == 0)
		{
			return null;
		}

		//If potential matches are more than 1 we compare them again for 
		while ($aPotentialMatches > 1)
		{
			$bCheck = self::challengeMatching($aPotentialMatches[0], $aPotentialMatches[1], $a_oParsedMatchup);
			$aPotentialMatches = array_splice($aPotentialMatches, ($bCheck == true ? 0 : 1), 1);
		}
		return $aPotentialMatches[0];
	}

	private static function compareSingleMatchup($a_oStoredMatchup, $a_oParsedMatchup)
	{
		$aPotentialMatches = array();
		//Check both sides
		for ($iX = 0; $iX < 1; $iX++)
		{
			for ($iY = 0; $iY < 1; $iY++)
			{
				if (OddsTools::compareNames($a_oStoredMatchup->getTeam($iX), $a_oParsedMatchup->getTeamName($iY)) > 82)
				{
					//Found a single side match!
					return true;
				}
			}
		}
		return false;
	}

	private static function challengeMatching($a_oCurrentMatchup, $a_oChallengerMatchup, $a_oParsedMatchup)
	{
		$iCurrentScore = OddsTools::compareNames($a_oParsedMatchup->getTeamName(1), $a_oCurrentMatchup->getTeam(1))
						+ OddsTools::compareNames($a_oParsedMatchup->getTeamName(2), $a_oCurrentMatchup->getTeam(2));

		$iChallengerScore = OddsTools::compareNames($a_oParsedMatchup->getTeamName(1), $a_oChallengerMatchup->getTeam(1))
							+ OddsTools::compareNames($a_oParsedMatchup->getTeamName(2), $a_oChallengerMatchup->getTeam(2));

		if ($iCurrentScore >= $iChallengerScore)
		{
			//Current stays
			return false;
		}
		else
		{
			//Challenger wins
			return true;
		}
	}
}

?>