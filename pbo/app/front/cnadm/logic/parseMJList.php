<?php

/**
 * This script will parse a list of matchups from MMAJunkie.com and check these
 * against the stored matchups for the specified event. Used for faster updating
 * of the fight schedule
 *
 * TODO: Needs to be extended so that it is possible to check if a fight has been removed
 *
 */
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseTools.php');

$sWordsToClear = array('Champ', '*');
$input = $_POST['input']; //TODO: Validate input
$iEventID = $_POST['eventID']; //TODO: Validate input
//Split on line break
$aMatchups = explode("\n", $input);

$oEvent = EventHandler::getEvent($iEventID);

echo '<script src="../../js/jquery-1.3.2.min.js" language="JavaScript" type="text/javascript"></script>';

echo $oEvent->getName() . ' <br /><br />';
$aFoundMatchups = array();

$sAllMatchups = '';
foreach ($aMatchups as $sMatchup)
{

    //Strip input of unused tags such as "Champ", (for featherweight title) and more
    $sMatchup = str_replace($sWordsToClear, '', $sMatchup);
    //Remove the suffix indicating title implications (e.g. ' - for bantamweight title')
    $iSuffixPos = strpos($sMatchup, ' - ');
    if ($iSuffixPos != false)
    {
        $sMatchup = substr($sMatchup, 0, $iSuffixPos);
    }
    
    $sMatchup = ParseTools::formatFighterName($sMatchup);

    //Create matchup objects and find matching matchup
    preg_match_all('/([A-Z0-9\'\.\\s-]*) VS\\. ([A-Z0-9\'\.\\s-]*)/', $sMatchup, $aMatches);

    if ($aMatches[1][0] != null && $aMatches[2][0] != null)
    {
        
        $oTempMatchup = new Fight(-1, $aMatches[1][0], $aMatches[2][0], $iEventID);
        $oMatchedMatchup = EventHandler::getMatchingFight($oTempMatchup);

        if ($oMatchedMatchup != null)
        {
            $aFoundMatchups[$oMatchedMatchup->getID()] = true;
            echo '<div style="width: 400px;">' . $oTempMatchup->getFighterAsString(1) . ' vs ' . $oTempMatchup->getFighterAsString(2) . ' <span style="float: right">OK!</a><br /></div>';
        }
        else
        {   
            if (strtoupper($oTempMatchup->getFighterAsString(1)) == 'TBA' || strtoupper($oTempMatchup->getFighterAsString(2)) == 'TBA' ||
                strtoupper($oTempMatchup->getFighterAsString(1)) == 'OPPONENT TBA' || strtoupper($oTempMatchup->getFighterAsString(2)) == 'OPPONENT TBA')
            {
                echo '<div style="width: 400px;">' . $oTempMatchup->getFighterAsString(1) . ' vs ' . $oTempMatchup->getFighterAsString(2) . ' <span style="float: right">Skip (TBA)</a><br /></div>';
            }
            else
            {
                echo '<div style="width: 400px;">' . $oTempMatchup->getFighterAsString(1) . ' vs ' . $oTempMatchup->getFighterAsString(2) . ' <form method="post" action="../logic/logic.php?action=addFight" style="border: 0; padding:0; margin: 0; float: right;"><input type="hidden" name="eventID" value="'  . $iEventID . '" /><input type="hidden" name="fighter1NameManual" value="' . $oTempMatchup->getFighter(1) . '" /><input type="hidden" name="fighter2NameManual" value="' . $oTempMatchup->getFighter(2) . '" /><a href="#" onclick="$(this).parents(\'form\').submit();">add</a></form> [<a href="http://www.google.se/search?q=' . $oTempMatchup->getFighterAsString(1) . ' vs ' . $oTempMatchup->getFighterAsString(2) . '">google</a>]<br /></div>';
                $sAllMatchups .= ($sAllMatchups == '' ? '' : ';') . $oTempMatchup->getFighterAsString(1) . '/' . $oTempMatchup->getFighterAsString(2);    
            }
           
        }

    }
}
echo '<div style="width: 400px;"><form method="post" action="../logic/logic.php?action=addMultipleFights" style="border: 0; padding:0; margin: 0; float: right;"><input type="hidden" name="eventID" value="'  . $iEventID . '" /><input type="hidden" name="fights" value="' . $sAllMatchups . '" /><a href="#" onclick="$(this).parents(\'form\').submit();">add all</a></form><br /></div>';

 $aMatchups = EventHandler::getAllFightsForEvent($iEventID);
        foreach ($aMatchups as $oMatchup) 
        {
            if (!array_key_exists($oMatchup->getID(), $aFoundMatchups))
            {
                echo '<div style="width: 400px;">' . $oMatchup->getFighterAsString(1) . ' vs ' . $oMatchup->getFighterAsString(2) . ' [<a href="http://www.google.se/search?q=' . $oMatchup->getFighterAsString(1) . ' vs ' . $oMatchup->getFighterAsString(2) . '">google</a>] <span style="float: right"> Not found <a href="../logic/logic.php?action=removeFight&fightID=' . $oMatchup->getID() . '&returnPage=eventsOverview" onclick="javascript:return confirm(\'Really remove ' . $oMatchup->getFighterAsString(1) . ' vs ' . $oMatchup->getFighterAsString(2) . '?\')" /><b>remove</b></a><br /></div>';
            }
        }
?>