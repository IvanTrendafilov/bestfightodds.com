<?php

require_once('lib/bfocore/dao/class.OddsDAO.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

class OddsHandler
{

    /**
     * Store a spread. If no set ID is specified, a new set will be created
     *
     * @param SpreadOdds $a_oSpreadOdds The spread odds object to add
     * @param int $a_iSetID Set ID to add the spread to
     * @return boolean If successful or not
     */
    public static function addSingleSpread($a_oSpreadOdds, $a_iSetID = null)
    {
        return OddsDAO::addSingleSpread($a_oSpreadOdds, $a_iSetID);
    }

    public static function addMultipleSpreads($a_aSpreadOdds, $a_iSetID = null)
    {
        return OddsDAO::addMultipleSpreads($a_aSpreadOdds, $a_iSetID);
    }

    public static function addSingleTotals($a_oTotalOdds, $a_iSetID = null)
    {
        return OddsDAO::addSingleTotals($a_oTotalOdds, $a_iSetID);
    }

    public static function addMultipleTotals($a_aTotalOdds, $a_iSetID = null)
    {
        return OddsDAO::addMultipleTotals($a_aTotalOdds, $a_iSetID);
    }

    public static function getLatestSpreadsForMatchup($a_iMatchupID, $a_iOffset = 0)
    {
        return OddsDAO::getLatestSpreadsForMatchup($a_iMatchupID, $a_iOffset);
    }

    public static function getLatestTotalsForMatchup($a_iMatchupID, $a_iOffset = 0)
    {
        return OddsDAO::getLatestTotalsForMatchup($a_iMatchupID, $a_iOffset);
    }

    public static function getLatestSpreadsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        return OddsDAO::getLatestSpreadsForMatchupAndBookie($a_iMatchupID, $a_iBookieID);
    }

    public static function getLatestTotalsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        return OddsDAO::getLatestTotalsForMatchupAndBookie($a_iMatchupID, $a_iBookieID);
    }

    public static function isLatestTotalSet($a_oTotalSet)
    {
        $oExistingTotal = OddsHandler::getLatestTotalsForMatchupAndBookie($a_oTotalSet->getMatchupID(), $a_oTotalSet->getBookieID());
        if ($oExistingTotal != null)
        {
            return $oExistingTotal->equals($a_oTotalSet);
        }
        return false;
    }

    public static function isLatestSpreadSet($a_oSpreadSet)
    {
        $oExistingSpreadSet = OddsHandler::getLatestSpreadsForMatchupAndBookie($a_oSpreadSet->getMatchupID(), $a_oSpreadSet->getBookieID());
        if ($oExistingSpreadSet != null)
        {
            return $oExistingSpreadSet->equals($a_oSpreadSet);
        }
        return false;
    }

    public static function addPropBet($a_oPropBet)
    {
        return OddsDAO::addPropBet($a_oPropBet);
    }

    public static function getPropBetsForMatchup($a_iMatchupID)
    {
        return OddsDAO::getPropBetsForMatchup($a_iMatchupID);
    }

    public static function getAllPropTypesForMatchup($a_iMatchupID)
    {
        return OddsDAO::getAllPropTypesForMatchup($a_iMatchupID);
    }

    public static function getAllPropTypes()
    {
        return OddsDAO::getAllPropTypes();
    }

    public static function checkMatchingPropOdds($a_oPropBet)
    {

        $oExistingPropOdds = OddsHandler::getLatestPropOdds($a_oPropBet->getMatchupID(), $a_oPropBet->getBookieID(), $a_oPropBet->getPropTypeID(), $a_oPropBet->getTeamNumber());
        if ($oExistingPropOdds != null)
        {
            return $oExistingPropOdds->equals($a_oPropBet);
        }
        return false;
    }

    public static function getLatestPropOdds($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDAO::getLatestPropOdds($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function getAllLatestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam = 0, $a_iOffset = 0)
    {
        $aRetOdds = array();

        //Loop through each bookie and retrieve prop odds
        $aBookies = BookieHandler::getAllBookies();
        foreach ($aBookies as $oBookie)
        {
            $oOdds = OddsDAO::getLatestPropOdds($a_iMatchupID, $oBookie->getID(), $a_iPropTypeID, $a_iTeam, $a_iOffset);
            if ($oOdds != null)
            {
                $aRetOdds[] = $oOdds;
            }
        }

        return $aRetOdds;
    }

    public static function getBestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam)
    {
        return OddsDAO::getBestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam);
    }

    public static function getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDAO::getAllPropOddsForMatchupPropType($a_iMatchupID, $a_iBookieID, $a_iPropTypeID, $a_iTeamNum);
    }

    public static function getPropCountForMatchup($a_iMatchupID)
    {
        return count(OddsDAO::getAllPropTypesForMatchup($a_iMatchupID));
    }

    public static function getCurrentPropIndex($a_iMatchupID, $a_iPosProp, $a_iPropTypeID, $a_iTeam)
    {
        $iSkippedProps = 0; //Keeps track of skipped prop bets that are not available, i.e. stored as -99999 in the database

        if ($a_iTeam > 2 || $a_iTeam < 0)
        {
            return null;
        }

        $aOdds = OddsHandler::getAllLatestPropOddsForMatchup($a_iMatchupID, $a_iPropTypeID, $a_iTeam);

        if ($aOdds == null || sizeof($aOdds) == 0)
        {
            return null;
        }
        if (sizeof($aOdds) == 1)
        {
            return new PropBet($a_iMatchupID, -1, '', ($a_iPosProp == 1 ? $aOdds[0]->getPropOdds() : 0), '', ($a_iPosProp == 2 ? $aOdds[0]->getNegPropOdds() : 0), $a_iPropTypeID, -1, $a_iTeam);
        }
        $iCurrentOddsTotal = 0;
        foreach ($aOdds as $oPropBet)
        {
            //Check if prop bet should be skipped, i.e. stored as -99999 in database
            if (( $a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds()) == -99999)
            {
                $iSkippedProps++;
            }
            else
            {
                $iCurrOdds = $a_iPosProp == 1 ? $oPropBet->getPropOdds() : $oPropBet->getNegPropOdds();
                $iCurrentOddsTotal += $iCurrOdds < 0 ? ($iCurrOdds + 100) : ($iCurrOdds - 100);
            }
        }
        if (sizeof($aOdds) - $iSkippedProps != 0)
        {
            $iCurrentOddsTotal = round($iCurrentOddsTotal / (sizeof($aOdds) - $iSkippedProps) + ($iCurrentOddsTotal < 0 ? -100 : 100));
        }
        return new PropBet($a_iMatchupID, -1, '', ($a_iPosProp == 1 ? $iCurrentOddsTotal : 0), '', ($a_iPosProp == 2 ? $iCurrentOddsTotal : 0), $a_iPropTypeID, -1, $a_iTeam);
    }

    /**
     * Get the openings odds for a specific matchup
     * 
     * 
     * @param int Matchup ID
     * @return FightOdds The opening odds or null if none was found 
     */
    public static function getOpeningOddsForMatchup($a_iMatchupID)
    {
        return OddsDAO::getOpeningOddsForMatchup($a_iMatchupID);
    }

    /**
     * Get the opening odds for a specified matchup and bookie
     * 
     * @param int $a_iMatchupID Matchup ID
     * @param int $a_iBookieID Bookie ID
     * @return FightOdds The opening odds or null if no line was found 
     */
    public static function getOpeningOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID)
    {
        return OddsDAO::getOpeningOddsForMatchupAndBookie($a_iMatchupID, $a_iBookieID);
    }

    /**
     * Get openings odds for a specific prop
     * 
     * @param int Matchup ID
     * @param int Proptype ID
     * @return FightOdds The opening odds or null if none was found 
     */
    public static function getOpeningOddsForProp($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum)
    {
        return OddsDAO::getOpeningOddsForProp($a_iMatchupID, $a_iPropTypeID, $a_iTeamNum);
    }

    /**
     * Get openings odds for a specific prop and bookkie
     * 
     * @param int Matchup ID
     * @param int Proptype ID
     * @param int Bookie ID
     * @return FightOdds The opening odds or null if none was found
     */
    public static function getOpeningOddsForPropAndBookie($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum)
    {
        return OddsDAO::getOpeningOddsForPropAndBookie($a_iMatchupID, $a_iPropTypeID, $a_iBookieID, $a_iTeamNum);
    }

    /**
     * Get all correlations for the specified bookie
     * 
     * @param int $a_iBookieID Bookie ID
     * @return array Collection of correlations 
     */
    public static function getCorrelationsForBookie($a_iBookieID)
    {
        //TODO: Add call to new function that removes correlations that are not valid anymore
        
        return OddsDAO::getCorrelationsForBookie($a_iBookieID);
    }

    /**
     * Stores a collection of correlations
     * 
     * Accepts an array of correlations defined as follows:
     * 
     * array('correlation' => xxx, 'matchup_id' => xxx)
     * 
     * @param int $a_iBookieID Bookie ID
     * @param array $a_aCorrelations Collection of correlations as defined above
     */
    public static function storeCorrelations($a_iBookieID, $a_aCorrelations)
    {
        return OddsDAO::storeCorrelations($a_iBookieID, $a_aCorrelations);
    }


    public static function getMatchupForCorrelation($a_iBookieID, $a_sCorrelation)
    {
        return OddsDAO::getMatchupForCorrelation($a_iBookieID, $a_sCorrelation);
    }
    
    
    public static function getCompletePropsForMatchup($a_iMatchup, $a_iOffset = 0)
    {
        return OddsDAO::getCompletePropsForMatchup($a_iMatchup, $a_iOffset);
    }

    /**
     * Cleans correlations by removing the ones that are not needed anymore. This is determined
     * by checking if the matchup it is associated with is in the past
     */
    public static function cleanCorrelations()
    {
        return OddsDAO::cleanCorrelations();
    }

}

?>
