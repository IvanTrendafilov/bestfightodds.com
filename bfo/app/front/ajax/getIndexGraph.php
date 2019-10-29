<?php

//Creates an index graph for a fightodds-object

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/graphtool/class.GraphTool.php');
require_once('config/inc.generalConfig.php');


if (!isset($_GET['fightID']) || !isset($_GET['fighter'])
        || !is_numeric($_GET['fightID']) || !is_numeric($_GET['fighter']))
{
    GraphTool::showNoGraphToUser();
}

$bShowDecimal = (isset($_GET['format']) && $_GET['format'] == "decimal");

$iFighter = $_GET['fighter'];
$iFightID = $_GET['fightID'];
$iOddsType = $_GET['oddsType'];

$sGraphName = 'graph-index-' . $iFightID . '-' . $iFighter . '-' . $iOddsType;

if (CacheControl::isCached($sGraphName))
{
    GraphTool::showCachedGraphToUser($sGraphName);
}
else
{
    $oMatchup = EventHandler::getFightByID($iFightID);
    $oEvent = EventHandler::getEvent($oMatchup->getEventID());
    $sEventDate = $oEvent->getDate();

    $aFightOdds = getFightIndexData($iFightID, $iFighter);
    $rImage = GraphTool::createGraph($aFightOdds, $iFighter, $sGraphName, $iOddsType, $sEventDate);
    GraphTool::showGraphToUser($rImage);
}

function getFightIndexData($a_iFightID, $a_iFighter)
{
    if ($a_iFighter != 1 && $a_iFighter != 2)
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

        $aFightOdds = EventHandler::getAllOddsForFightAndBookie($a_iFightID, $oBookie->getID());
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
                    $iCurrentOddsMean = $oCurrentClosestOdds->getFighterOddsAsDecimal($a_iFighter, true);
                    $iCurrentOwners = 1;
                }
                else
                {
                    $iCurrentOddsMean = $iCurrentOddsMean + $oCurrentClosestOdds->getFighterOddsAsDecimal($a_iFighter, true);
                    $iCurrentOwners++;
                }
            }
        }

        $aDateOdds[] = new FightOdds($a_iFightID, -1, ($a_iFighter == 1 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0),
                        ($a_iFighter == 2 ? FightOdds::convertOddsEUToUS($iCurrentOddsMean / $iCurrentOwners) : 0), $sDate);
    }

    return $aDateOdds;
}

?>