<?php

//Creates an index graph for a fightodds-object

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/graphtool/class.GraphTool.php');
require_once('config/inc.generalConfig.php');

if (!isset($_GET['matchupID']) ||
        !isset($_GET['posProp']) || !isset($_GET['propTypeID']) ||
        !is_numeric($_GET['matchupID']) || !is_numeric($_GET['posProp']) ||
        !is_numeric($_GET['propTypeID']) || !isset($_GET['teamNum']) ||
        !is_numeric($_GET['teamNum']))
{
    GraphTool::showNoGraphToUser();
}


$bShowDecimal = (isset($_GET['format']) && $_GET['format'] == "decimal");

$iPosProp = $_GET['posProp'];
$iMatchupID = $_GET['matchupID'];
$iOddsType = $_GET['oddsType'];
$iPropTypeID = $_GET['propTypeID'];
$iTeamNum = $_GET['teamNum'];

$sGraphName = 'graph-prop-index_' . $iMatchupID . '-' . $iPropTypeID . '-' . $iPosProp . '-' . $iOddsType . '-' . $iTeamNum;

if (CacheControl::isCached($sGraphName))
{
    GraphTool::showCachedGraphToUser($sGraphName);
}
else
{
    $oMatchup = EventHandler::getFightByID($iMatchupID);
    $oEvent = EventHandler::getEvent($oMatchup->getEventID());
    $sEventDate = $oEvent->getDate();

    $aOdds = getPropIndexData($iMatchupID, $iPosProp, $iPropTypeID, $iTeamNum);
    $rImage = GraphTool::createPropGraph($aOdds, $iPosProp, $sGraphName, $iOddsType, $sEventDate);
    GraphTool::showGraphToUser($rImage);
}

function getPropIndexData($a_iMatchupID, $a_iPosProp, $a_iPropTypeID, $a_iTeamNum)
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

?>