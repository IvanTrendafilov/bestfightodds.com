<?php

/**
 * Odds Job
 *
 *  This is v2 of cron.OddsParser.php and does not handle feed parsing but the following activities:
 * 
 * - Checks if any alerts needs to be dispatched or cleaned out
 * - Clears the image cache for graphs
 * - Generates a new front page with latest odds
 * - Generates a new fighter list for browsing purposes
 * - Tweet new fight odds
 * - NEW: In progress: Automatically create matchups that are unmatched AND found in scheduler
 * - Clear flagged odds for old matchups
 *
 */
require_once('config/inc.config.php');

require_once('lib/bfocore/general/class.Alerter.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/general/class.TwitterHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.ScheduleHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/parser/utils/class.ParseRunLogger.php');

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.oddsjob.' . time() . '.log']);

$oj = new OddsJob($logger);
$oj->run();

class OddsJob
{
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        if (PARSE_CREATEMATCHUPS == true) {
            //Check if there are changes proposed by scheduler AND logged as unmatched. These should be automatically created
            $matches = ScheduleHandler::getAllUnmatchedAndScheduled();
        }

        //Clean and check alerts
        if (ALERTER_ENABLED == true) {
            $cleaned = Alerter::cleanAlerts();
            $this->logger->info("Cleaning expired alerts. Alerts cleaned: " . $cleaned);
            $alerts = Alerter::checkAllAlerts();
            $this->logger->info("Checking alerts. Alerts dispatched: " . $alerts);
        }

        //Clear cache
        $cached_deleted = CacheControl::cleanGraphCache();
        if ($cached_deleted) {
            $this->logger->info("Deleting image cache: " . $cached_deleted);
        } else {
            $this->logger->error("Deleting image cache: " . $cached_deleted);
        }

        CacheControl::cleanPageCacheWC('graphdata-*');
        $this->logger->info("Deleting graph cache");

        //Cleanup old correlations
        $success = OddsHandler::cleanCorrelations();
        $this->logger->info("Old correlations cleaned: " . $success);

        //Generate new front page with latest odds
        $plates = new League\Plates\Engine(GENERAL_BASEDIR . '/app/front/templates/');
        $events = EventHandler::getAllUpcomingEvents();

        $view_data = [];
        $view_data['bookies'] = BookieHandler::getAllBookies();
        $view_data['events'] = [];
        foreach ($events as $event) {
            if ($event->isDisplayed()) {
                $event_data = OddsHandler::getEventViewData($event->getID());
                if (count($event_data['matchups']) > 0) {
                    $view_data['events'][] = $event_data;
                }
            }
        }
        $rendered_page = $plates->render('gen_oddspage', $view_data);
        $page = fopen(PARSE_PAGEDIR . 'oddspage.php', 'w');
        if ($page != null) {
            //Minify
            $rendered_page = preg_replace('/\>\s+\</m', '><', $rendered_page);
            fwrite($page, $rendered_page);
            fclose($page);
            $this->logger->info("Plates odds page (oddspage) generated: 1");
        } else {
            $this->logger->info("Failed to generate odds page (oddspage)");
        }

        //Tweet new fight odds
        if (TWITTER_ENABLED == true) {
            $results = TwitterHandler::twitterNewFights();
            if ($results['pre_untwittered_events'] == $results['post_twittered']) {
                $this->logger->info("Tweeted new matchups/events: " . $results['post_twittered'] . " of " . $results['pre_untwittered_events']);
            } else {
                $this->logger->error("Tweeted new matchups/events: " . $results['post_twittered'] . " of " . $results['pre_untwittered_events']);
            }
        }

        //Clear old logged runs in database
        $cleared_runs = (new ParseRunLogger())->clearOldRuns();
        $this->logger->info("Cleared old logged runs: " . $cleared_runs);

        $this->logger->info("Parsing/alerting/generating/twittering done!");

        //Clear old flagged odds that belong to historic matchups
        $this->logger->info("Clearing old flagged odds for historic matchups");
        $result = OddsHandler::removeAllOldFlagged();
        $this->logger->info($result . ' cleared');

        //Delete odds that have been flagged for over 24 hours
        $this->logger->info("Deleting odds that have been flagged for over 24 hours");
        $result = OddsHandler::deleteFlaggedOdds();
        $this->logger->info('(Would have) Deleted ' . $result['matchup_odds'] . ' matchup odds');
        $this->logger->info('(Would have) Deleted ' . $result['prop_odds'] . ' prop odds');
        $this->logger->info('(Would have) Deleted ' . $result['event_prop_odds'] . ' event prop odds');

    }
}
