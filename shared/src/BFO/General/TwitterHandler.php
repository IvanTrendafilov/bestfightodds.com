<?php

namespace BFO\General;

use BFO\DB\TwitterDB;
use BFO\General\EventHandler;
use BFO\Utils\Twitter\Tweeter;

/**
 * Handles all Twitter-updates for new odds
 */
class TwitterHandler
{
    public static function tweetNewMatchups()
    {
        $tweet_counter = 0;
        $matchups = TwitterDB::getUntweetedMatchups();

        // Fights are mainly grouped based on event, however main events are always seperated into their own group
        $tweet_groups = array();

        foreach ($matchups as $matchup) {
            //We don't group if this is a UFC matchup so fetch the event
            $event = EventHandler::getEvent($matchup->getEventID());

            if (!TWITTER_GROUP_MATCHUPS || $matchup->isMainEvent() || substr(strtoupper($event->getName()), 0, 3) == 'UFC') {
                $tweet_groups[$matchup->getEventID() . $matchup->getID()]['matchups'][] = $matchup;
                $tweet_groups[$matchup->getEventID() . $matchup->getID()]['event_id'] = $matchup->getEventID();
            } else {
                $tweet_groups[$matchup->getEventID()]['matchups'][] = $matchup;
                $tweet_groups[$matchup->getEventID()]['event_id'] = $matchup->getEventID();
            }
        }

        $tweeter = new Tweeter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_OAUTH_TOKEN, TWITTER_OATUH_TOKEN_SECRET);
        $tweeter->setDebugMode(TWITTER_DEV_MODE);

        foreach ($tweet_groups as $group) {
            $event = EventHandler::getEvent($group['event_id']);

            $text_to_tweet = '';
            if (count($group['matchups']) > 1) {
                //Multiple fights for the same event needs to be tweeted
                $text_to_tweet = str_replace(
                    array('<T1>', '<T2>'),
                    array($group['matchups'][0]->getFighterAsString(1), $group['matchups'][0]->getFighterAsString(2)),
                    TWITTER_TEMPLATE_MULTI
                );
            } elseif (count($group['matchups']) == 1) {
                //Only one fight for the event needs to be tweeted
                $odds = OddsHandler::getOpeningOddsForMatchup($group['matchups'][0]->getID());
                if ($odds != null) {
                    $text_to_tweet = str_replace(
                        array('<T1>', '<T2>', '<T1O>', '<T2O>'),
                        array($group['matchups'][0]->getFighterAsString(1), $group['matchups'][0]->getFighterAsString(2), $odds->getFighterOddsAsString(1), $odds->getFighterOddsAsString(2)),
                        TWITTER_TEMPLATE_SINGLE
                    );
                }
            }

            $tweet_length = strlen($text_to_tweet) + 13; //12 is to reach the URL character limit (23) with <EVENT_URL> already counted for
            //Substitute event URL (doing this here because we need to count characters without URL prior to this)
            $text_to_tweet = str_replace('<EVENT_URL>', $event->getEventAsLinkString(), $text_to_tweet);

            //Depending on how many characters we have left, add either full event name or the shortened version
            if (strlen($text_to_tweet) + strlen($event->getName()) <= 280) {
                $text_to_tweet = str_replace('<E>', $event->getName(), $text_to_tweet);
            } else {
                $text_to_tweet = str_replace('<E>', $event->getShortName(), $text_to_tweet);
            }

            //Add dynamic hashtags if less than 280 chars and if event name format allows for it
            $regexp_matches = null;
            $sRegExp = preg_match("/^([a-zA-Z]{2,8}\s[0-9]{1,5}):/", $event->getName(), $regexp_matches);
            if ($regexp_matches != null && isset($regexp_matches[1])) {
                $hashtag = ' #' . strtolower(str_replace(' ', '', $regexp_matches[1]));
                if (strlen($hashtag) + $tweet_length <= 280) {
                    $text_to_tweet .= $hashtag;
                }
            }

            //Add fighter twitter handles if we are only tweeting one matchup (and if they exist)
            if (count($group['matchups']) == 1) {
                for ($x = 1; $x <= 2; $x++) {
                    $handle = self::getTwitterHandle((int) $group['matchups'][0]->getFighterID($x));
                    if ($handle && $handle != '') {
                        $text_to_tweet .= ' @' . $handle;
                    }
                }
            }

            //Trim to 280 if we made any mistakes along the way
            $text_to_tweet = substr($text_to_tweet, 0, 280);

            if ($text_to_tweet != '' && $tweeter->updateStatus($text_to_tweet)) {
                foreach ($group['matchups'] as $matchup_tweeted) {
                    TwitterDB::saveFightAsTweeted((int) $matchup_tweeted->getID());
                }
                $tweet_counter++;
            }
        }

        return array('pre_untweeted_fights' => count($matchups), 'pre_untweeted_events' => count($tweet_groups), 'post_tweeted' => $tweet_counter);
    }

    public static function addTwitterHandle(int $team_id, string $handle): bool
    {
        return TwitterDB::addTwitterHandle($team_id, $handle);
    }

    public static function getTwitterHandle(int $team_id): ?string
    {
        $result = TwitterDB::getTwitterHandle($team_id);
        return isset($result->handle) ? $result->handle : null;
    }
}
