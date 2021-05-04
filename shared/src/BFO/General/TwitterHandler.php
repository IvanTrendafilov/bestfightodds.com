<?php

namespace BFO\General;

use BFO\DB\TwitterDB;
use BFO\General\EventHandler;
use BFO\Utils\Twitter\Tweeter;

/**
 * Handles all Twitter-updates for new odds
 *
 * TODO: Make generic
 */
class TwitterHandler
{
    public static function tweetNewMatchups()
    {
        $iTwits = 0;
        $aFights = TwitterDB::getUntweetedMatchups();

        // Fights are mainly grouped based on event, however main events are always seperated into their own group
        $aGroups = array();

        foreach ($aFights as $oFight) {
            //We don't group if this is a UFC matchup so fetch the event
            $oEvent = EventHandler::getEvent($oFight->getEventID());

            if (!TWITTER_GROUP_MATCHUPS || $oFight->isMainEvent() || substr(strtoupper($oEvent->getName()), 0, 3) == 'UFC') {
                $aGroups[$oFight->getEventID() . $oFight->getID()]['matchups'][] = $oFight;
                $aGroups[$oFight->getEventID() . $oFight->getID()]['event_id'] = $oFight->getEventID();
            } else {
                $aGroups[$oFight->getEventID()]['matchups'][] = $oFight;
                $aGroups[$oFight->getEventID()]['event_id'] = $oFight->getEventID();
            }
        }

        $tweeter = new Tweeter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_OAUTH_TOKEN, TWITTER_OATUH_TOKEN_SECRET);
        $tweeter->setDebugMode(TWITTER_DEV_MODE);

        foreach ($aGroups as $aGroup) {
            $oEvent = EventHandler::getEvent($aGroup['event_id']);

            $bUpdateSuccess = false;
            $sTwitText = '';
            if (count($aGroup['matchups']) > 1) {
                //Multiple fights for the same event needs to be tweeted
                $sTwitText = str_replace(
                    array('<T1>','<T2>'),
                    array($aGroup['matchups'][0]->getFighterAsString(1), $aGroup['matchups'][0]->getFighterAsString(2)),
                    TWITTER_TEMPLATE_MULTI
                );
            } elseif (count($aGroup['matchups']) == 1) {
                //Only one fight for the event needs to be tweeted
                $oFightOdds = OddsHandler::getOpeningOddsForMatchup($aGroup['matchups'][0]->getID());
                if ($oFightOdds != null) {
                    $sTwitText = str_replace(
                        array('<T1>','<T2>','<T1O>','<T2O>'),
                        array($aGroup['matchups'][0]->getFighterAsString(1), $aGroup['matchups'][0]->getFighterAsString(2), $oFightOdds->getFighterOddsAsString(1), $oFightOdds->getFighterOddsAsString(2)),
                        TWITTER_TEMPLATE_SINGLE
                    );
                }
            }

            $iTweetLength = strlen($sTwitText) + 13; //12 is to reach the URL character limit (23) with <EVENT_URL> already counted for
            //Substitute event URL (doing this here because we need to count characters without URL prior to this)
            $sTwitText = str_replace('<EVENT_URL>', $oEvent->getEventAsLinkString(), $sTwitText);

            //Depending on how many characters we have left, add either full event name or the shortened version
            if (strlen($sTwitText) + strlen($oEvent->getName()) <= 280) {
                $sTwitText = str_replace('<E>', $oEvent->getName(), $sTwitText);
            } else {
                $sTwitText = str_replace('<E>', $oEvent->getShortName(), $sTwitText);
            }

            //Add dynamic hashtags if less than 280 chars and if event name format allows for it
            $aMatches = null;
            $sRegExp = preg_match("/^([a-zA-Z]{2,8}\s[0-9]{1,5}):/", $oEvent->getName(), $aMatches);
            if ($aMatches != null && isset($aMatches[1])) {
                $sHashtag = ' #' . strtolower(str_replace(' ', '', $aMatches[1]));
                if (strlen($sHashtag) + $iTweetLength <= 280) {
                    $sTwitText .= $sHashtag;
                }
            }

            //Add fighter twitter handles if we are only tweeting one matchup (and if they exist)
            if (count($aGroup['matchups']) == 1) {
                for ($x = 1; $x <= 2; $x++) {
                    $handle = self::getTwitterHandle($aGroup['matchups'][0]->getFighterID($x));
                    if ($handle && $handle != '') {
                        $sTwitText .= ' @' . $handle;
                    }
                }
            }

            //Trim to 280 if we made any mistakes along the way
            $sTwitText = substr($sTwitText, 0, 280);

            if ($sTwitText != '' && $tweeter->updateStatus($sTwitText)) {
                foreach ($aGroup['matchups'] as $oEventFight) {
                    TwitterDB::saveFightAsTweeted($oEventFight->getID());
                }
                $iTwits++;
            }
        }

        return array('pre_untweeted_fights' => count($aFights), 'pre_untweeted_events' => count($aGroups), 'post_tweeted' => $iTwits);
    }

    public static function addTwitterHandle($team_id, $handle)
    {
        return TwitterDB::addTwitterHandle($team_id, $handle);
    }

    public static function getTwitterHandle($team_id)
    {
        $result = TwitterDB::getTwitterHandle($team_id);
        return isset($result->handle) ? $result->handle : false;
    }
}
