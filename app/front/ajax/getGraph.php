<?php

//Creates a graph for a fightodds object

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/graphtool/class.GraphTool.php');
require_once('config/inc.generalConfig.php');

if (!isset($_GET['bookieID']) || !isset($_GET['fightID']) ||
        !isset($_GET['fighter']) || !is_numeric($_GET['bookieID']) ||
        !is_numeric($_GET['fightID']) || !is_numeric($_GET['fighter']))
{
    GraphTool::showNoGraphToUser();
}

$bShowDecimal = (isset($_GET['format']) && $_GET['format'] == "decimal");

$iFighter = $_GET['fighter'];
$iBookieID = $_GET['bookieID'];
$iFightID = $_GET['fightID'];
$iOddsType = $_GET['oddsType'];

//Test http://www.bestfightodds.com/ajax/getGraph.php?bookieID=5&fighter=1&fightID=10

$sGraphName = 'graph-normal_' . $iFightID . '-' . $iFighter . '-' . $iBookieID . '-' . $iOddsType;

if (CacheControl::isCached($sGraphName))
{
    GraphTool::showCachedGraphToUser($sGraphName);
}
else
{
    $oMatchup = EventHandler::getFightByID($iFightID);

    if ($oMatchup == null)
    {
        GraphTool::showNoGraphToUser();
    }
    else
    {
        $oEvent = EventHandler::getEvent($oMatchup->getEventID());
        $sEventDate = $oEvent->getDate();

        $aFightOdds = EventHandler::getAllOddsForFightAndBookie($iFightID, $iBookieID);
        $rImage = GraphTool::createGraph($aFightOdds, $iFighter, $sGraphName, $iOddsType, $sEventDate);
        GraphTool::showGraphToUser($rImage);
    }
}
?>