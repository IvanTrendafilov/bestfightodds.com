<?php

/**
 * This is an interface used for Ajax requests.
 */
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('app/front/pages/inc.FrontLogic.php');
require_once('config/inc.generalConfig.php');

checkRequiredParam('function');

switch ($_GET['function'])
{
    case 'searchFighter':
        AjaxInterface::searchFighter();
        break;
    case 'addAlert':
        AjaxInterface::addAlert();
        break;
    case 'getLineDetails':
        AjaxInterface::getLineDetails();
        break;
    case 'getPropLineDetails':
        AjaxInterface::getPropLineDetails();
        break;
    default:
        echo 'Invalid function: ' . $_GET['function'];
}

class AjaxInterface
{
    public static function addAlert()
    {
        require_once('lib/bfocore/general/class.Alerter.php');
        $iResult = Alerter::addNewAlert($_GET['alertFight'], $_GET['alertFighter'], $_GET['alertMail'], $_GET['alertOdds'], $_GET['alertBookie'], $_GET['alertOddsType']);
        echo $iResult;
    }

    public static function searchFighter()
    {
        if (checkRequiredParam('q', false) == false || strlen($_GET['q']) < 3)
        {
            exit();
        }

        $aFighters = FighterHandler::searchFighter($_GET['q']);
        if ($aFighters != null)
        {
            foreach ($aFighters as $oFighter)
            {
                echo $oFighter->getNameAsString() . "\n";
            }
        }
    }


    public static function getLineDetails()
    {
        checkRequiredParam('m', false);
        checkRequiredParam('p', false);

        $oOpeningOdds = null;
        $oLatestOdds = null;
        //If bookie is supplied, we will check for that one specifically
        if (checkRequiredParam('b', false))
        {
            $oOpeningOdds = OddsHandler::getOpeningOddsForMatchupAndBookie($_GET['m'], $_GET['b']);
            $oLatestOdds = EventHandler::getLatestOddsForFightAndBookie($_GET['m'], $_GET['b']);
        }
        else
        {
            $oOpeningOdds = OddsHandler::getOpeningOddsForMatchup($_GET['m']);
            $oLatestOdds = EventHandler::getCurrentOddsIndex($_GET['m'], $_GET['p']);
        }

        if ($oOpeningOdds == null || $oLatestOdds == null || ($_GET['p'] != 1 && $_GET['p'] != 2))
        {
            echo 'n/a';
        }
        else
        {

            //Create JSON structure and return it
            $toJson = array('open' => array('changed' => $oOpeningOdds->getDate(), 'odds' => $oOpeningOdds->getFighterOddsAsString($_GET['p'])),
                'current' => array('changed' => getTimeDifference(strtotime($oLatestOdds->getDate()), strtotime(GENERAL_TIMEZONE . ' hours')), 'odds' => $oLatestOdds->getFighterOddsAsString($_GET['p'])));

            echo json_encode($toJson);
        }
        return;
    }

    public static function getPropLineDetails()
    {
        checkRequiredParam('m', false);
        checkRequiredParam('p', false);
        checkRequiredParam('pt', false);
        checkRequiredParam('tn', false);

        $oOpeningOdds = null;
        $oLatestOdds = null;
        //If bookie is supplied, we will check for that one specifically
        if (checkRequiredParam('b', false))
        {
            $oOpeningOdds = OddsHandler::getOpeningOddsForPropAndBookie($_GET['m'], $_GET['pt'], $_GET['b'], $_GET['tn']);
            $oLatestOdds = OddsHandler::getLatestPropOdds($_GET['m'], $_GET['b'], $_GET['pt'], $_GET['tn']);
        }
        else
        {
            $oOpeningOdds = OddsHandler::getOpeningOddsForProp($_GET['m'], $_GET['pt'], $_GET['tn']);
            $oLatestOdds = OddsHandler::getCurrentPropIndex($_GET['m'], $_GET['p'], $_GET['pt'], $_GET['tn']);
        }

        if ($oOpeningOdds == null || $oLatestOdds == null || ($_GET['p'] != 1 && $_GET['p'] != 2))
        {
            echo 'n/a';
        }
        else
        {
            //Create JSON structure and return it
            $toJson = array('open' => array('changed' => $oOpeningOdds->getDate(), 'odds' => ($_GET['p'] == 1 ? $oOpeningOdds->getPropOddsAsString() : $oOpeningOdds->getNegPropOddsAsString())),
                'current' => array('changed' => getTimeDifference(strtotime($oLatestOdds->getDate()), strtotime(GENERAL_TIMEZONE . ' hours')), 'odds' => ($_GET['p'] == 1 ? $oLatestOdds->getPropOddsAsString() : $oLatestOdds->getNegPropOddsAsString())));

            echo json_encode($toJson);
        }
        return;
    }

}

/**
 * Checks if a parameter is set.
 *
 * If second parameter is set to false, no prinout will be done, instead the function will just return false if the parameter is not set and true if it is set
 */
function checkRequiredParam($sParamName, $bPrintOut = true)
{
    if (!isset($_GET[$sParamName]) || $_GET[$sParamName] == '')
    {
        if ($bPrintOut == false)
        {
            return false;
        }
        //Do not print out missing params for security reasons:
        //echo 'Missing param: ' . $sParamName;
        exit();
    }

    return true;
}

?>