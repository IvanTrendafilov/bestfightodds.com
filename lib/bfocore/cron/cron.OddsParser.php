<?php

/**
 * Main parsing cron job that does the following things:
 *
 * - Retrieves latest odds from bookies and stores these in the database
 * - Checks if any alerts needs to be dispatched or cleaned out
 * - Clears the image cache for graphs
 * - Generates a new front page with latest odds
 * - Generates a new fighter list for browsing purposes
 * - Tweet new fight odds
 *
 * @author Christian Nordvaller
 */
require_once('config/inc.parseConfig.php');
require_once('config/inc.alertConfig.php');
require_once('config/inc.twitterConfig.php');

require_once('lib/bfocore/parser/general/class.XMLParser.php');
require_once('lib/bfocore/utils/pagegen/class.PageGenerator.php');

require_once('lib/bfocore/general/class.Alerter.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/general/class.TwitterHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');


echo "Dispatching parser..";

$oLogger = Logger::getInstance();

$oLogger->start(PARSE_LOGDIR . date('Ymd-Hi') . '.log');
$oLogger->log("Start: " . date('Y-m-d H:i:s') . "", 0);
$oLogger->seperate();

$aParsers = explode(';', PARSE_PARSERS);
$aStoredParsers = BookieHandler::getParsers();

//Pre-fetch data from bookie URLs (skip if in mock feed mode)
if (PARSE_MOCKFEEDS_ON == false)
{
    $aURLs = array();
    $fStartTime = microtime(true);
    foreach ($aParsers as $sParser)
    {
        $filtered = array_filter($aStoredParsers, function($entry) use ($sParser) {
            return ($entry->getName() == $sParser);
        });
        $filtered = reset($filtered);
        if ($filtered != null)
        {
            $sURL = $filtered->getParseURL();
            if ($filtered->hasChangenumInUse())
            {
                $iChangeNum = BookieHandler::getChangeNum($filtered->getBookieID());
                if ($iChangeNum != -1)
                {
                    $sURL .= $filtered->getChangenumSuffix() . $iChangeNum;
                }
            }
            $aURLs[] = $sURL;
            
            $oLogger->log("Preparing prefetch of " . end($aURLs), 0);        
        }
    }
    ParseTools::retrieveMultiplePagesFromURLs($aURLs);
    $oLogger->log("Prefetch of " . count($aURLs) . " URLs completed in " . round(microtime(true) - $fStartTime, 3), 0);
    $oLogger->seperate();
}


//Dispatch all bookie-specific parsers as defined in config
foreach ($aParsers as $sParser)
{
    $filtered = array_filter($aStoredParsers, function($file) use ($sParser) {
        return ($file->getName() == $sParser);
    });
    if (reset($filtered) != null)
    {
        XMLParser::dispatch(reset($filtered));
    }
}

$oLogger->log("End: " . date('Y-m-d H:i:s') . "", 0);
$oLogger->seperate();
$oLogger->log("Parsing done. ", 0);
$oLogger->seperate();

//Clean and check alerts
if (ALERTER_ENABLED == true)
{
    $iAlertsCleaned = Alerter::cleanAlerts();
    $oLogger->log("Cleaning expired alerts. Alerts cleaned: " . $iAlertsCleaned . "", 0);
    $iAlerts = Alerter::checkAllAlerts();
    $oLogger->log("Checking alerts. Alerts dispatched: " . $iAlerts . "", 0);
    $oLogger->seperate();
}

//Clear cache
$bCacheDeleted = CacheControl::cleanGraphCache();
$oLogger->log("Deleting image cache: " . $bCacheDeleted, ($bCacheDeleted ? 0 : -2));
/*$bCacheDeleted = CacheControl::cleanPageCache();
$oLogger->log("Deleting page cache: " . $bCacheDeleted, ($bCacheDeleted ? 0 : -2));*/
CacheControl::cleanPageCacheWC('graphdata-*');
$oLogger->log("Deleting graph cache", 0);


//Cleanup old correlations
$iSuccess = OddsHandler::cleanCorrelations();
$oLogger->log("Old correlations cleaned: " . $iSuccess, 0);

//Generate new front page with latest odds
$bSuccess = PageGenerator::generatePage(PARSE_GENERATORDIR . 'gen.Bets.php', PARSE_PAGEDIR . 'page.odds.php');
$oLogger->log("Odds page generated: " . $bSuccess, ($bSuccess ? 0 : -2));

//Generate XML feed with latest odds
$bSuccess = PageGenerator::generatePage(PARSE_GENERATORDIR . 'gen.XMLFeed.php', PARSE_PAGEDIR . 'page.feed.xml');
$oLogger->log("XML feed generated: " . $bSuccess, ($bSuccess ? 0 : -2));


//Twitter new fight odds
if (TWITTER_ENABLED == true)
{
    $aTwitResults = TwitterHandler::twitterNewFights();
    $oLogger->log("Tweeted new matchups/events: " . $aTwitResults['post_twittered'] . " of " . $aTwitResults['pre_untwittered_events'],
            ($aTwitResults['pre_untwittered_events'] == $aTwitResults['post_twittered'] ? 0 : -2));
}

$oLogger->seperate();
$oLogger->log("Parsing/alerting/generating/twittering done!");

//End logging
$oLogger->end(PARSE_LOGDIR . date('Ymd-Hi') . '.log');

echo 'Done!';
?>