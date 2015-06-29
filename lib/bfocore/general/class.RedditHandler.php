<?php



require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.redditConfig.php');
require_once('lib/bfocore/utils/reddit/class.Redditor.php');
require_once('lib/bfocore/dao/class.RedditDAO.php');
require_once('lib/bfocore/general/class.EventHandler.php');



//Temporary singleton stuff
RedditHandler::postPendingEvents();




/**
 * Handles all Reddit updates to r/mma for new odds
 *
 * TODO: Make generic
 *
 * @author Christian Nordvaller
 */
class RedditHandler
{

    public static function postPendingEvents()
    {

        $oRedditor = new Redditor();

        //Get all upcoming events (< 1 week, only UFC for now)
        $aEvents = EventHandler::getAllUpcomingEvents();

        //Filter out events that are within a week, are UFC
        $aFiltered = array();
        foreach ($aEvents as $oEvent)
        {
            //Only within a week
            if( strtotime($oEvent->getDate() . ' -7 day') <= time() ) {
                //Only UFC
                if (strtolower(substr($oEvent->getName(), 0, 3)) == 'ufc')
                {
                    $aFiltered[] = $oEvent;    
                }
            }
        }
        $aEvents = $aFiltered;

        //Check what events need to be updated
        foreach ($aEvents as $oEvent)
        {
            $sLatestChangeDate = EventHandler::getLatestChangeDate($oEvent->getID());
            $aRedditDetails = RedditDAO::getLatestRedditDetails($oEvent->getID());
            if ($aRedditDetails == false || strtotime($aRedditDetails['last_change']) < strtotime($sLatestChangeDate))
            {
                //Event needs update on reddit
                echo $oEvent->getID() . ' needs update
';
                if ($oRedditor->isLoggedIn() == false)
                {
                    $oRedditor->login();
                }

                if ($aRedditDetails == false)
                {
                    //Create new post   
                    $oRedditor->createPost('Title: ' . $oEvent->getID(), 'Contents for ' . $oEvent->getID());
                    echo 'Creating new';
                }
                else
                {
                    //Update existing post
                    $oRedditor->updatePost('t3_' . $aRedditDetails['reddit_id'], self::generatePost($oEvent->getID()));
                    echo 'Updating old';
                    //'t3_296olk'
                }
            }
            else
            {
                echo $oEvent->getID() . ' No update required';
            }
        }
    }

    private static function generatePost($a_iEventID)
    {

        $aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
        $sTable = 'Matchup|Moneyline|Decimal
:--|:-:|:-:
';

        foreach ($aMatchups as $oMatchup) 
        {
            $oFightOdds = EventHandler::getBestOddsForFight($oMatchup->getID());
            $sTable .= $oMatchup->getFighterAsString(1) . '|' . $oFightOdds->getFighterOddsAsString(1) . '|' . $oFightOdds->getFighterOddsAsDecimal(1) . '
';
            $sTable .= $oMatchup->getFighterAsString(2) . '|' . $oFightOdds->getFighterOddsAsString(2) . '|' . $oFightOdds->getFighterOddsAsDecimal(2) . '
';


            /*
                                $sFighter1Odds = sprintf("%1\$.2f", $oFightOdds->getFighterOddsAsDecimal(1));
                    $sFighter2Odds = sprintf("%1\$.2f", $oFightOdds->getFighterOddsAsDecimal(2));
                }
                else
                {
                    //Moneyline
                    $sFighter1Odds = $oFightOdds->getFighterOddsAsString(1);
                    $sFighter2Odds = $oFightOdds->getFighterOddsAsString(2);
            */
        }
        return $sTable;
    }
}

?>
