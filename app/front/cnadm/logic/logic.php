<?php

/**
 * This is the main logic class for the admin panel. When an action is performed it is done through this script.
 */
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

if (isset($_GET['returnPage']))
{
    //Convert any $ to & in return page string
    define('RETURN_PAGE', str_replace('$', '&', $_GET['returnPage']));
}

checkGETParam('action');

switch ($_GET['action'])
{
    case 'addFight':
        checkPOSTParam('fighter1NameManual');
        checkPOSTParam('fighter2NameManual');
        checkPOSTParam('eventID');
        $oFight = new Fight(0, $_POST['fighter1NameManual'], $_POST['fighter2NameManual'], $_POST['eventID']);
        EventHandler::addNewFight($oFight);
        define('RETURN_PAGE', 'addNewFightForm&eventID=' . $_POST['eventID']);
        finishCall();
        break;

    case 'addMultipleFights':
        checkPOSTParam('fights');
        checkPOSTParam('eventID');
        $aMatchups = explode(';', $_POST['fights']);
        foreach ($aMatchups as $sMatchup)
        {
            $sTeams = explode('/', $sMatchup);
            echo 'Adding ' . $sTeams[0] . ' vs ' . $sTeams[1] . '<br />';
            $oFight = new Fight(0, $sTeams[0], $sTeams[1], $_POST['eventID']);
            EventHandler::addNewFight($oFight);
        }
        define('RETURN_PAGE', 'addNewFightForm&eventID=' . $_POST['eventID']);
        finishCall();
        break;

    case 'addEvent':
        checkPOSTParam('eventName');
        checkPOSTParam('eventDate');
        $oEvent = new Event(0, $_POST['eventDate'], $_POST['eventName'], isset($_POST['eventDisplay']));
        if (($iEventID = EventHandler::addNewEvent($oEvent)))
        {
            define('RETURN_PAGE', 'addNewFightForm&eventID=' . $iEventID);
            finishCall();
        }
        else
        {
            define('RETURN_PAGE', 'addNewEventForm');
            finishCall('Event not added');
        }
        break;

    case 'removeFight':
        checkGETParam('fightID');
        EventHandler::removeFight($_GET['fightID']);
        finishCall('Fight ' . $_GET['fightID'] . ' with all odds removed.');
        break;

    case 'setFightAsMainEvent':
        checkGETParam('fightID');
        checkGETParam('isMain');
        EventHandler::setFightAsMainEvent($_GET['fightID'], $_GET['isMain']);
        finishCall();
        break;

    case 'updateEvent':
        checkPOSTParam('eventID');
        checkPOSTParam('eventName');
        checkPOSTParam('eventDate');
        define('RETURN_PAGE', 'addNewFightForm&eventID=' . $_POST['eventID']);
        if (EventHandler::changeEvent($_POST['eventID'], $_POST['eventName'], $_POST['eventDate'], isset($_POST['eventDisplay'])))
        {
            finishCall('Event ' . $_POST['eventID'] . ' updated.');
        }
        else
        {
            finishCall('Event ' . $_POST['eventID'] . ' NOT updated due to error.');
        }
        break;

    case 'updateFight':
        checkGETParam('fightID');
        checkGETParam('eventID');
        EventHandler::changeFight($_GET['fightID'], $_GET['eventID']);
        finishCall('Fight ' . $_GET['fightID'] . ' updated.');
        break;


    case 'addPropTemplate':
        checkPOSTParam('bookieID');
        checkPOSTParam('propTypeID');
        checkPOSTParam('fieldsTypeID');
        checkPOSTParam('template');
        checkPOSTParam('negTemplate');
        //($a_iID, $a_iBookieID, $a_sTemplate, $a_sTemplateNeg, $a_iPropTypeID, $a_iFieldsTypeID)
        $oPropTemplate = new PropTemplate(0, $_POST['bookieID'], $_POST['template'], $_POST['negTemplate'], $_POST['propTypeID'], $_POST['fieldsTypeID']);

        define('RETURN_PAGE', 'addNewPropTemplate');
        if ((BookieHandler::addNewPropTemplate($oPropTemplate)))
        {
            finishCall('Prop template added');
        }
        else
        {
            finishCall('Prop template not added');
        }
        break;

    case 'dispatchAlerts':
        require_once('lib/bfocore/general/class.Alerter.php');
        finishCall('Alerts dispatched: ' . Alerter::checkAllAlerts());
        break;

    case 'addManualPropCorrelation':
        require_once('lib/bfocore/general/class.OddsHandler.php');
        checkPOSTParam('bookieID');
        checkPOSTParam('correlation');
        checkPOSTParam('matchupID');
        define('RETURN_PAGE', 'addManualPropCorrelation');
        OddsHandler::storeCorrelations($_POST['bookieID'], array(array('correlation' => $_POST['correlation'], 'matchup_id' => $_POST['matchupID'])));
        finishCall();
        break;

    case 'clearUnmatched':
        require_once('lib/bfocore/general/class.EventHandler.php');
        define('RETURN_PAGE', 'main');
        EventHandler::clearUnmatched();
        finishCall();
        break;
    case 'addTeamTwitterHandle':
        require_once('lib/bfocore/general/class.TwitterHandler.php');
        checkPOSTParam('teamID');
        checkPOSTParam('twitterHandle');
        define('RETURN_PAGE', 'eventsOverview');
        TwitterHandler::addTwitterHandle($_POST['teamID'], $_POST['twitterHandle']);
        finishCall();
        break;

}

/**
 * Checks if a parameter is supplied. Exits if it's not
 */
function checkGETParam($a_sParam)
{
    if (!isset($_GET[$a_sParam]))
    {
        echo 'Missing parameter: ' . $a_sParam;
        exit();
    }
}

function checkPOSTParam($a_sParam)
{
    if (!isset($_POST[$a_sParam]))
    {
        echo 'Missing parameter: ' . $a_sParam;
        exit();
    }
}

/**
 * Finishes a call and prints any messages from the operation
 */
function finishCall($a_sMessage = '')
{
    header('Location: ../?p=' . (defined('RETURN_PAGE') ? RETURN_PAGE : '') . ($a_sMessage != '' ? '&message=' : '') . $a_sMessage);
}

?>