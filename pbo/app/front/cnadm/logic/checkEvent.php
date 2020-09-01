<?php

define('MATCH_REGEXP_BLOCK', '/<div id="jSft_CPCtr_evC[0-9]*_pnlCopy" class="copy">[\\sa-zA-Z:,.<>0-9="\/&;()-?]*<\/div>/');
define('MATCH_REGEXP_DATE', '/Date: [a-zA-Z]* [0-9]*, [0-9]*/');
define('MATCH_REGEXP_FIGHT', '/<li>([^<]*)<\/li>/');

/**
 *  Checks an event against MMA Junkie. URL passed through must be the url of the event
 */

if (!isset($_GET['eventID']) || !isset($_GET['url']))
{
    echo 'Missing arguments';
    exit();
}

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php'); //TODO: Try to avoid having dependecies to the parsing component. Move this functionality to another class in the general library

$oEvent = EventHandler::getEvent($_GET['eventID']);
//TODO: Replace retrieveFromFile with retriveFromURL with $_GET['url'] as input
$sURLPage = ParseTools::retrievePageFromFile('./ufc-99-franklin-vs-silva-the-comeback.mma.htm');

if ($oEvent == null || $sURLPage == 'FAILED' || $sURLPage == null)
{
    echo 'Failed to retrieve event or open URL';
    exit();
}

preg_match(MATCH_REGEXP_BLOCK, $sURLPage, $aBlockMatches);
if (sizeof($aBlockMatches) == 0)
{
    echo 'Failed to pick up block, check regexp or URL';
    exit();
}

$aDateMatches = array();
preg_match(MATCH_REGEXP_DATE, $sURLPage, $aDateMatches);
if (sizeof($aDateMatches) == 0)
{
    echo 'Failed to pick up date, check regexp or url';
    exit();
}

$iDate = strtotime(substr($aDateMatches[0], 6));
if (strtotime($oEvent->getDate()) != $iDate)
{
    echo 'Date has changed. Old date: ' . $oEvent->getDate() . ' - New Date: ' . date('Y-m-d', $iDate);
}
else
{
    echo 'Date is OK';
}

$aFightMatches = array();
preg_match_all(MATCH_REGEXP_FIGHT, $aBlockMatches[0], $aFightMatches);
foreach($aFightMatches[0] as $aFightMatch)
{
    $aSplit = explode('vs.', strip_tags($aFightMatch));
    $oTempFight = new Fight(-1, $aSplit[0], $aSplit[1], $oEvent->getID());

    $oMatchedFight = EventHandler::getMatchingFight($oTempFight);
    if ($oMatchedFight != null)
    {
        echo 'Matched fight: ' . $oTempFight->getFighterAsString(1) . ' vs ' . $oTempFight->getFighterAsString(2) . '<br />';
    }
    else
    {
        echo 'Unmatched fight: ' . $oTempFight->getFighterAsString(1) . ' vs ' . $oTempFight->getFighterAsString(2) . '<br />';
    }
}

//echo 'New date is: ' . date('Y-m-d', $iDate);



//preg_match_all(MATCH_REGEXP_, $subject, &$matches)



?>
