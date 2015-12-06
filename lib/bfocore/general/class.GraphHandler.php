<?php 

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');

class GraphHandler 
{


	public static function getMatchupData($a_iMatchupID, $a_iBookieID)
	{
		return EventHandler::getAllOddsForFightAndBookie($a_iMatchupID, $a_iBookieID);
	}


	public static function getPropData($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
	{
		return OddsHandler::getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);
	}




	public static function getMatchupIndexData($a_iMatchupID, $a_iTeamNum)
	{
	    if ($a_iTeamNum != 1 && $a_iTeamNum != 2)
	    {
	        return null;
	    }

	    $aBookies = BookieHandler::getAllBookies();

	    $aOdds = array();
	    $aDates = array();

	    $iBookieCount = 0;

	    foreach ($aBookies as $oBookie)
	    {
	        $aOdds[$iBookieCount] = array();

	        $aFightOdds = EventHandler::getAllOddsForFightAndBookie($a_iMatchupID, $oBookie->getID());
	        if ($aFightOdds != null)
	        {
	            foreach ($aFightOdds as $oFightOdds)
	            {
	                $aOdds[$iBookieCount][] = $oFightOdds;
	                if (!in_array($oFightOdds->getDate(), $aDates))
	                {
	                    $aDates[] = $oFightOdds->getDate();
	                }
	            }
	        }

	        $iBookieCount++;
	    }

	    sort($aDates);

	    $aDateOdds = array();

	    foreach ($aDates as $sDate)
	    {
	        $iCurrentOddsMean = 0;
	        $iCurrentOwners = 0;

	        for ($iX = 0; $iX < $iBookieCount; $iX++)
	        {
	            $oCurrentClosestOdds = null;

	            foreach ($aOdds[$iX] as $oOdds)
	            {
	                if ($oOdds->getDate() <= $sDate)
	                {
	                    if ($oCurrentClosestOdds == null)
	                    {
	                        $oCurrentClosestOdds = $oOdds;
	                    }
	                    else
	                    {
	                        if ($oOdds->getDate() > $oCurrentClosestOdds->getDate())
	                        {
	                            $oCurrentClosestOdds = $oOdds;
	                        }
	                    }
	                }
	            }

	            if ($oCurrentClosestOdds != null)
	            {
	                if ($iCurrentOddsMean == 0)
	                {
	                    $iCurrentOddsMean = $oCurrentClosestOdds->getFighterOddsAsDecimal($a_iTeamNum, true);
	                    $iCurrentOwners = 1;
	                }
	                else
	                {
	                    $iCurrentOddsMean = $iCurrentOddsMean + $oCurrentClosestOdds->getFighterOddsAsDecimal($a_iTeamNum, true);
	                    $iCurrentOwners++;
	                }
	            }
	        }

	        $aDateOdds[] = new FightOdds($a_iMatchupID, -1, ($a_iTeamNum == 1 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0),
	                        ($a_iTeamNum == 2 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0), $sDate);
	    }

	    return $aDateOdds;
	}



	public static function getPropIndexData($a_iMatchupID, $a_iPosProp, $a_iPropTypeID, $a_iTeamNum)
	{
	    $aBookies = BookieHandler::getAllBookies();

	    $aOdds = array();
	    $aDates = array();

	    $iBookieCount = 0;
	    $bSkipBookie = false; //Keeps track if bookie does not give odds on the prop and if it is stored as -99999 in the database

	    foreach ($aBookies as $oBookie)
	    {
	        $aOdds[$iBookieCount] = array();

	        $aPropOdds = OddsHandler::getAllPropOddsForMatchupPropType($a_iMatchupID, $oBookie->getID(), $a_iPropTypeID, $a_iTeamNum);

	        if ($aPropOdds != null)
	        {
	            foreach ($aPropOdds as $oPropBet)
	            {
	                //Check if prop bet should be skipped, i.e. stored as -99999 in database
	                if (($a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999)
	                {
	                    $bSkipBookie = true;
	                }
	                else
	                {
	                    $aOdds[$iBookieCount][] = $oPropBet;
	                    if (!in_array($oPropBet->getDate(), $aDates))
	                    {
	                        $aDates[] = $oPropBet->getDate();
	                    }
	                }
	            }
	        }

	        if ($bSkipBookie == false)
	        {
	            $iBookieCount++;
	        }
	        $bSkipBookie = false;
	    }

	    sort($aDates);

	    $aDateOdds = array();

	    foreach ($aDates as $sDate)
	    {
	        $iCurrentOddsMean = 0;
	        $iCurrentOwners = 0;

	        for ($iX = 0; $iX < $iBookieCount; $iX++)
	        {
	            $oCurrentClosestOdds = null;

	            foreach ($aOdds[$iX] as $oOdds)
	            {
	                if ($oOdds->getDate() <= $sDate)
	                {
	                    if ($oCurrentClosestOdds == null)
	                    {
	                        $oCurrentClosestOdds = $oOdds;
	                    }
	                    else
	                    {
	                        if ($oOdds->getDate() > $oCurrentClosestOdds->getDate())
	                        {
	                            $oCurrentClosestOdds = $oOdds;
	                        }
	                    }
	                }
	            }

	            if ($oCurrentClosestOdds != null)
	            {
	                if ($iCurrentOddsMean == 0)
	                {
	                    $iCurrentOddsMean = ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
	                    $iCurrentOwners = 1;
	                }
	                else
	                {
	                    $iCurrentOddsMean = $iCurrentOddsMean + ($a_iPosProp == 1 ? PropBet::moneylineToDecimal($oCurrentClosestOdds->getPropOdds(), true) : PropBet::moneylineToDecimal($oCurrentClosestOdds->getNegPropOdds(), true));
	                    $iCurrentOwners++;
	                }
	            }
	        }

	        $aDateOdds[] = new PropBet($a_iMatchupID, -1, '', ($a_iPosProp == 1 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), '', ($a_iPosProp == 2 ? PropBet::decimalToMoneyline($iCurrentOddsMean / $iCurrentOwners) : 0), $a_iPropTypeID, $sDate, $a_iTeamNum);
	    }

	    return $aDateOdds;
	}


	public static function getEventPropData($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iEventID)
	{
		//TODO
	}    
	//getEventPropData($_GET['m'], $_GET['b'], $_GET['pt'], $_GET['e']);    

	public static function getEventPropIndexData($a_iMatchupID, $a_iPosProp, $a_iPropTypeID, $a_iEventID)
	{
		//TODO
	}
	//getEventPropIndexData($_GET['m'], $_GET['p'], $_GET['pt'], $_GET['e']);



	/*public static function getTeamSpreadData($a_iTeamID)
	{
		$aRetArr = array();
		$aMatchups = EventHandler::getAllFightsForFighter($a_iTeamID);

		if ($aMatchups != null)
		{
			foreach ($aMatchups as $oMatchup)
			{

				//Determine if target team is 1 or 2 in the event
				$iMatchupTeamID = 1;
				if ($a_iTeamID == $oMatchup->getFighterID(2))
				{
					$iMatchupTeamID = 2;
				}

				$aOdds = EventHandler::getAllLatestOddsForFight($oMatchup->getID());
				//Determine highest and lowest odds at fight time

				usort($aOdds, function($a, $b) use ($iMatchupTeamID)
					{
				    	return $a->getOdds($iMatchupTeamID) < $b->getOdds($iMatchupTeamID);
					}
				);	

				if (count($aOdds) > 0)
				{
					$aRetArr[] = array($aOdds[0]::moneylineToDecimal($aOdds[count($aOdds)-1]->getOdds($iMatchupTeamID), true), $aOdds[0]::moneylineToDecimal($aOdds[0]->getOdds($iMatchupTeamID), true)) ;
				}
//   $oMatchup->getTeamAsString(!($iMatchupTeamID - 1) + 1)     , 
			}

			return $aRetArr;
		//Retrieve high and low for team and each matchup
		}

		return null;

	}*/

	public static function getMedianSparkLine($a_iMatchupID, $a_iTeamNum)
	{
		$iSparklineSteps = 10;

		$aLines = EventHandler::getAllOddsForMatchup($a_iMatchupID);
		if ($aLines == null || sizeof($aLines) < 1)
		{
			return null;
		}

		//Determine high/low/step
		$iLow = (new DateTime($aLines[0]->getDate(), new DateTimeZone('America/New_York')))->getTimestamp() * 1000;
		$iHigh = (new DateTime($aLines[sizeof($aLines)-1]->getDate(), new DateTimeZone('America/New_York')))->getTimestamp() * 1000;
		$fStep = ($iHigh - $iLow) / ($iSparklineSteps - 1);

		$aBookieLatestLine = array();
		$iStepCounter = 0;
		$sRetStr = '';

		foreach ($aLines as $aLine)
		{

			$sLineDate = (new DateTime($aLine->getDate(), new DateTimeZone('America/New_York')))->getTimestamp() * 1000;
			$aBookieLatestLine[$aLine->getBookieID()] = $aLine;
			// Once we reach a line that passes the step date, flush the stored ones and create an index for that
				if ($sLineDate >= $iLow + ($fStep * $iStepCounter))  {
					$fTotal = 0;
					foreach ($aBookieLatestLine as $oBookieLine)
					{	
						$fTotal += $oBookieLine->getFighterOddsAsDecimal($a_iTeamNum, true);
					}
					$fMean = $fTotal/sizeof($aBookieLatestLine);
					//echo 'Step ' . $iStepCounter . ' mean is: ' . $fMean . ' when steps was ' . ($iLow + ($fStep * $iStepCounter)) . '<br>';
					$sRetStr .= $fMean . ', ';
					$iStepCounter++;

				}

		}
		return rtrim($sRetStr, ', ');
	}
}

function algorun($N,$A){
        $aRet = array();
        $step=(sizeof($A)-1)/($N-1);
        for ($i=0;$i<$N;$i++)
	        $aRet[] = $A[round($step*$i)];
    }

?>