<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.twitterConfig.php');
require_once('lib/bfocore/utils/twitter/class.Twitterer.php');
require_once('lib/bfocore/general/class.EventHandler.php');

require_once('lib/bfocore/dao/class.TwitDAO.php');

/**
 * Handles all Twitter-updates for new odds
 *
 * TODO: Make generic
 *
 * @author Christian Nordvaller
 */
class TwitterHandler
{

    public static function twitterNewFights()
    {
        $iTwits = 0;
        $aFights = TwitDAO::getUntwitteredFights();

        // Fights are mainly grouped based on event, however main events are always seperated into their own group
        $aGroups = array();

        foreach ($aFights as $oFight)
        {
            if (!TWITTER_GROUP_MATCHUPS || $oFight->isMainEvent())
            {
                $aGroups[$oFight->getEventID() . $oFight->getID()]['matchups'][] = $oFight;
                $aGroups[$oFight->getEventID() . $oFight->getID()]['event_id'] = $oFight->getEventID();
            }
            else
            {
                $aGroups[$oFight->getEventID()]['matchups'][] = $oFight;
                $aGroups[$oFight->getEventID()]['event_id'] = $oFight->getEventID();
            }
        }

        $oTwitterer = new Twitterer(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_OAUTH_TOKEN, TWITTER_OATUH_TOKEN_SECRET);
        $oTwitterer->setDebugMode(TWITTER_DEV_MODE);

        foreach ($aGroups as $aGroup)
        {
            $oEvent = EventHandler::getEvent($aGroup['event_id']);

            $bUpdateSuccess = false;
            $sTwitText = '';
            if (count($aGroup['matchups']) > 1)
            {
                //Multiple fights for the same event needs to be twittered
                $sTwitText = str_replace(array('<E>','<T1>','<T2>','<EVENT_URL>'),
                                            array($oEvent->getName(), $aGroup['matchups'][0]->getFighterAsString(1), $aGroup['matchups'][0]->getFighterAsString(2), $oEvent->getEventAsLinkString()),
                                            TWITTER_TEMPLATE_MULTI);

                //$sTwitText = 'New lines for ' . $oEvent->getName() . ' posted https://bestfightodds.com';
            }
            else if (count($aGroup['matchups']) == 1)
            {
                //Only one fight for the event needs to be twittered
                $oFightOdds = OddsHandler::getOpeningOddsForMatchup($aGroup['matchups'][0]->getID());

                if ($oFightOdds != null)
                {
                    $sTwitText = str_replace(array('<E>','<T1>','<T2>','<T1O>','<T2O>','<EVENT_URL>'),
                                            array($oEvent->getName(), $aGroup['matchups'][0]->getFighterAsString(1), $aGroup['matchups'][0]->getFighterAsString(2), $oFightOdds->getFighterOddsAsString(1), $oFightOdds->getFighterOddsAsString(2), $oEvent->getEventAsLinkString()),
                                            TWITTER_TEMPLATE_SINGLE);

                    //$sTwitText = $oEvent->getName() . ': ' . $aGroup['matchups'][0]->getFighterAsString(1) . ' (' . $oFightOdds->getFighterOddsAsString(1) . ') vs. '
                    //        . $aGroup['matchups'][0]->getFighterAsString(2) . ' (' . $oFightOdds->getFighterOddsAsString(2) . ') https://bestfightodds.com';
                }
            }

            //Add dynamic hashtags if less than 140 chars and if event name format allows for it
            $aMatches = null;
            $sRegExp = preg_match("/^([a-zA-Z]{2,8}\s[0-9]{1,5}):/", $oEvent->getName(), $aMatches);
            if ($aMatches != null && isset($aMatches[1]))
            {
                $sHashtag = ' #' . strtolower(str_replace(' ', '', $aMatches[1]));
                if ($sTwitText + $sHashtag - 2 <= 140) //The -2 correction is for URL transformation to t.co address
                {
                    $sTwitText .= $sHashtag;
                }
            }

            if ($sTwitText != '' && $oTwitterer->updateStatus($sTwitText))
            {
                foreach ($aGroup['matchups'] as $oEventFight)
                {
                    TwitDAO::saveFightAsTwittered($oEventFight->getID());
                }
                $iTwits++;
            }
        }

        return array('pre_untwittered_fights' => count($aFights), 'pre_untwittered_events' => count($aGroups), 'post_twittered' => $iTwits);
    }

}

?>
