<?php
/**
 * This is an interface used for Ajax requests.
 */

checkRequiredParam('f');

require_once('app/front/pages/inc.FrontLogic.php');

switch($_GET['f'])
{
    case 'rp':
        AjaxInterface::refreshPage();
        break;
    default:
}

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.GraphHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('config/inc.config.php');

switch ($_GET['f'])
{
    case 'sf':
        AjaxInterface::searchFighter();
        break;
    case 'aa':
        AjaxInterface::addAlert();
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
        exit;
    }

    public static function getGraphData()
    {
        //Check if cached

        //graphdata-m-b-p-pt-tn-e
        //Matchup-Bookie-Posprop-Proptype-TeamNum-Event
        $sCacheKey = 'graphdata-';
        if (checkRequiredParam('pt', false))
        {
            //For prop
            if (checkRequiredParam('b', false))
            {
                //For specific bookie
                if (checkRequiredParam('e', false))
                {
                    //Event prop
                    $sCacheKey .= 'x-' . $_GET['b'] . '-' . $_GET['p'] . '-' . $_GET['pt'] . '-x-' . $_GET['e'];
                }
                else
                {
                    //Regular prop
                    $sCacheKey .= $_GET['m'] . '-' . $_GET['b'] . '-' . $_GET['p'] . '-' . $_GET['pt'] . '-' . $_GET['tn'];
                }
            }
            else
            {
                //Mean
                if (checkRequiredParam('e', false))
                {
                    $sCacheKey .= 'x-x-' . $_GET['p'] . '-' . $_GET['pt'] . '-x-' . $_GET['e'];
                }                
                else
                {
                    $sCacheKey .= $_GET['m'] . '-x-' . $_GET['p'] . '-' . $_GET['pt'] . '-' . $_GET['tn'];    
                }
                
            }
        }
        else
        {
            //For normal matchup
            if (checkRequiredParam('b', false))
            {
                //For specific bookie
                $sCacheKey .= $_GET['m'] . '-' . $_GET['b'] . '-' . $_GET['p'] . '-x-';
            }
            else
            {
                $sCacheKey .= $_GET['m'] . '-x-' . $_GET['p'] . '-x-';
            }
        }


        if (CacheControl::isPageCached($sCacheKey) && $_SERVER['REMOTE_ADDR'] != '77.2.84.76' && $_SERVER['REMOTE_ADDR'] != '77.4.124.22' && $_SERVER['REMOTE_ADDR'] != '77.9.20.120')
        {
            echo CacheControl::getCachedPage($sCacheKey);
            return true;
        }
        else
        {
            //Not cached, process
            checkRequiredParam('m', false); //Matchup
            checkRequiredParam('p', false); //Position in line

            $aOdds = null;
            if (checkRequiredParam('pt', false))
            {
                //For prop
                if (checkRequiredParam('b', false))
                {
                    //For specific bookie
                    if (checkRequiredParam('e', false))
                    {
                        //Event prop
                        $aOdds = GraphHandler::getEventPropData($_GET['e'], $_GET['b'], $_GET['pt']);    
                    }
                    else
                    {
                        //Regular prop
                        $aOdds = GraphHandler::getPropData($_GET['m'], $_GET['b'], $_GET['pt'], $_GET['tn']);    
                    }
                }
                else
                {
                    //Mean
                    if (checkRequiredParam('e', false))
                    {
                        //Event prop
                        $aOdds = GraphHandler::getEventPropIndexData($_GET['e'], $_GET['p'], $_GET['pt']);
                    }
                    else
                    {
                        //Regular prop
                        $aOdds = GraphHandler::getPropIndexData($_GET['m'], $_GET['p'], $_GET['pt'], $_GET['tn']);
                    }
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

                if (isset($_GET['e']))
                {
                    $oEvent = EventHandler::getEvent($_GET['e'], true);
                }
                else
                {
                    $oEvent = EventHandler::getEvent(EventHandler::getFightByID($_GET['m'])->getEventID(), true);    
                }
                
                foreach ($aOdds as $iIndex => $oOdds)
                {
                        if ($_SERVER['REMOTE_ADDR'] == '77.2.84.76' || $_SERVER['REMOTE_ADDR'] == '77.4.124.22' || $_SERVER['REMOTE_ADDR'] == '77.9.20.120')
                        {
                            $scale = pow(10, 3);
                            $dummy = mt_rand(1 * $scale, 3 * $scale) / $scale;

                            $retArr['data'][] = array('x' => 
                                        (new DateTime($oOdds->getDate()))->getTimestamp() * 1000,
                                        'y' => $dummy);
                                        //error_log('Giving bogus data');
                        }
                        else
                        {
                            $retArr['data'][] = array('x' => 
                                        (new DateTime($oOdds->getDate()))->getTimestamp() * 1000,
                                        'y' => $oOdds->moneylineToDecimal($oOdds->getOdds($_GET['p']), true));
                        }

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
                    $curTime = (new DateTime(''));
                    $retArr['data'][] = array('x' => $curTime->getTimestamp() * 1000, 'y' => $aOdds[count($aOdds) - 1]->moneylineToDecimal($aOdds[count($aOdds) - 1]->getOdds($_GET['p']), true), 'dataLabels' => array('x' => -9));    
                }

                //"Encrypt" with ROT47 + base64 before returning
                $sResp = self::encryptResponse('[' . json_encode($retArr) . ']');
                echo $sResp;
                CacheControl::cachePage($sResp, $sCacheKey . '.php');
                return true;
            }
            return false;
        }
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