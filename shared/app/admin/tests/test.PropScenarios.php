<?php
require_once('config/inc.config.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');


//Scenario 1: Matching prop with second team having an altname that changes the lexographical order of the matchup
//              e.g. Rashad Evans vs Quinton Jackson  =  Rampage vs Rashad Evans
if ($_POST['execute'] == true) {
    if (GENERAL_PRODUCTION_MODE == true) {
        echo 'This test cannot be run in production mode';
        exit;
    }

    Scenario1();
}

//Scenario 1: Matching prop with second team having an altname that changes the lexographical order of the matchup
//              e.g. Rashad Evans vs Quinton Jackson  =  Rampage vs Rashad Evans
function Scenario1()
{
    //Step 1: Create the matchup
    $oMatchup = new Fight(-1, 'Bfirstname Blastname', 'Cfirstname Clastname', 197, '');
    EventHandler::addNewFight($oMatchup);
    $oMatchedFight = EventHandler::getMatchingFight($oMatchup);
    echo 'Step 1: Added new matchup : ' . $oMatchedFight->getID() . '<br />';

    //Step 2: Add odds to the matchup so that the prop can be added to it

    $oNewOdds = new FightOdds($oMatchedFight->getID(), 1, '-200', '+150');
    $bSuccess = EventHandler::addNewFightOdds($oNewOdds);
    echo 'Step 2: Added odds to new matchup<br />';

    //Step 3: Add alt name to secondary fighter
    $aFighters = TeamHandler::searchFighter($oMatchup->getFighter2());
    TeamHandler::addFighterAltName($aFighters[0]->getID(), 'Alastname');
    echo 'Step 3: Added alt name to secondary fighter<br />';

    //Step 4: Parse prop for template Bookie ID 1, prop template ID 2,  <T> WINS INSIDE DISTANCE
    //TODO!:
    $oParsedProp = new ParsedProp('Alastname WINS INSIDE DISTANCE', '', '-115', '-115');
    $oTemplate = $oPropParser->matchParsedPropToTemplate(1, $oParsedProp);
    if ($oTemplate != null) {
        echo 'Found template <br />';
        $aMatchup = $oPropParser->matchParsedPropToMatchup($oParsedProp, $oTemplate);
        if ($aMatchup['matchup'] == null) {
            echo 'Found no matchup<br />';
        } else {
            echo 'Found matchup<br />';
        }
    }
    echo 'Step 4: Found prop<br />';
}
?>


<html>
    <form method="post">
        <input type="hidden" name="execute" value="true" />
        <input type="submit" value="Execute" />
    </form>
</html>