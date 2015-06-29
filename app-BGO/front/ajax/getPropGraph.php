<?php

//Creates a graph for a prop-object
//TODO: Merge all prop graph files into one single one dispatching to multiple graphtool functions

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/graphtool/class.GraphTool.php');
require_once('config/inc.generalConfig.php');
require_once('lib/bfocore/general/class.EventHandler.php');

if (!isset($_GET['bookieID']) || !isset($_GET['matchupID']) ||
        !isset($_GET['posProp']) || !isset($_GET['propTypeID']) ||
        !is_numeric($_GET['bookieID']) || !is_numeric($_GET['matchupID'])
        || !is_numeric($_GET['posProp']) || !is_numeric($_GET['propTypeID'])
        || !isset($_GET['teamNum']) || !is_numeric($_GET['teamNum']))
{
    GraphTool::showNoGraphToUser();
}

$bShowDecimal = (isset($_GET['format']) && $_GET['format'] == "decimal");

$iPosProp = $_GET['posProp'];
$iBookieID = $_GET['bookieID'];
$iMatchupID = $_GET['matchupID'];
$iOddsType = $_GET['oddsType'];
$iPropTypeID = $_GET['propTypeID'];
$iTeamNum = $_GET['teamNum'];

//Test http://www.bestfightodds.com/ajax/getGraph.php?bookieID=5&posProp=1&matchupID=10

$sGraphName = 'graph-prop_' . $iMatchupID . '-' . $iPropTypeID . '-' . $iPosProp . '-' . $iBookieID . '-' . $iOddsType . '-' . $iTeamNum;

if (CacheControl::isCached($sGraphName))
{
    GraphTool::showCachedGraphToUser($sGraphName);
}
else
{

    $oMatchup = EventHandler::getFightByID($iMatchupID);
    $oEvent = EventHandler::getEvent($oMatchup->getEventID());
    $sEventDate = $oEvent->getDate();

    $aOdds = OddsHandler::getAllPropOddsForMatchupPropType($iMatchupID, $iBookieID, $iPropTypeID, $iTeamNum);
    $rImage = GraphTool::createPropGraph($aOdds, $iPosProp, $sGraphName, $iOddsType, $sEventDate);
    GraphTool::showGraphToUser($rImage);
}
?>