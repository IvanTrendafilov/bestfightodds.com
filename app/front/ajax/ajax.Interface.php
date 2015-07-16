<?php

/**
 * This is an interface used for Ajax requests.
 */
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.GraphHandler.php');
require_once('app/front/pages/inc.FrontLogic.php');
require_once('config/inc.generalConfig.php');

checkRequiredParam('f');

switch ($_GET['f'])
{
    case 'sf':
        AjaxInterface::searchFighter();
        break;
    case 'aa':
        AjaxInterface::addAlert();
        break;
    case 'rp':
        AjaxInterface::refreshPage();
        break;
    case 'ggd':
        AjaxInterface::getGraphData();
        break;
    case 'gtsd':
        AjaxInterface::getTeamSpreadData();
        break;
    default:
        echo 'Invalid function: ' . $_GET['f'];
}

class AjaxInterface
{
    public static function addAlert()
    {
        require_once('lib/bfocore/general/class.Alerter.php');
        $iResult = Alerter::addNewAlert($_GET['alertFight'], $_GET['alertFighter'], $_GET['alertMail'], $_GET['alertOdds'], $_GET['alertBookie'], $_GET['alertOddsType']);
        echo $iResult;
    }

    public static function refreshPage()
    {
        include_once('app/front/pages/page.odds.php');
        return;
    }

    public static function getGraphData()
    {
        checkRequiredParam('m', false); //Matchup
        checkRequiredParam('p', false); //Position in line

        $aOdds = null;
        $bIsProp = false;

        if (checkRequiredParam('pt', false) && checkRequiredParam('tn', false))
        {
            $bIsProp = true;
            //For prop
            if (checkRequiredParam('b', false))
            {
                //For specific bookie
                $aOdds = GraphHandler::getPropData($_GET['m'], $_GET['b'], $_GET['pt'], $_GET['tn']);
            }
            else
            {
                //Mean
                $aOdds = GraphHandler::getPropIndexData($_GET['m'], $_GET['p'], $_GET['pt'], $_GET['tn']);
            }

        }
        else
        {
            //For normal matchup
            if (checkRequiredParam('b', false))
            {
                //For specific bookie
                $aOdds = GraphHandler::getMatchupData($_GET['m'], $_GET['b']);
            }
            else
            {
                $aOdds = GraphHandler::getMatchupIndexData($_GET['m'], $_GET['p']);
            }
        }


        if ($aOdds != null)
        {
            //Convert to JSON and return
            $sBookieName = 'Mean';
            if (isset($_GET['b']))
            {
                $sBookieName = BookieHandler::getBookieByID($_GET['b'])->getName();
            }

            $retArr = array('name' => $sBookieName, 'data' => array());
            date_default_timezone_set('America/Los_Angeles');
            $oEvent = EventHandler::getEvent(EventHandler::getFightByID($_GET['m'])->getEventID(), true);
            foreach ($aOdds as $iIndex => $oOdds)
            {
                    $retArr['data'][] = array('x' => 
                                    (new DateTime($oOdds->getDate(), new DateTimeZone('America/New_York')))->getTimestamp() * 1000,
                                    'y' => $oOdds->moneylineToDecimal($oOdds->getOdds($_GET['p']), true));

                    
                    if ($iIndex == 0)
                    {
                        $retArr['data'][0]['dataLabels'] = array('x' => 9);
                    }

                    if ($iIndex == count($aOdds) - 1)
                    {
                        $retArr['data'][$iIndex]['dataLabels'] = array('x' => -9);   
                    }
            }
            //Add last odds with current date if this is an upcoming event
            if ($oEvent != null)
            {
                $curTime = (new DateTime('', new DateTimeZone('America/New_York')));
                $retArr['data'][] = array('x' => $curTime->getTimestamp() * 1000, 'y' => $aOdds[count($aOdds) - 1]->moneylineToDecimal($aOdds[count($aOdds) - 1]->getOdds($_GET['p']), true), 'dataLabels' => array('x' => -9));    
            }

            //"Encrypt" with ROT47 + base64 before returning
            echo self::encryptResponse('[' . json_encode($retArr) . ']');
        }
        return 'error';


    }

    public static function getTeamSpreadData()
    {
        checkRequiredParam('t', false); //Team

        $aRetArr = GraphHandler::getTeamSpreadData($_GET['t']);
        if ($aRetArr == null)        
        {
            return 'error';
        }

        echo '' . json_encode($aRetArr) . '';

    }

    public static function encryptResponse($sResponse)
    {
        //ROT47
        //BASE64
        return base64_encode(strtr($sResponse, '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~', 'PQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNO'));
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