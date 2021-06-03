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

namespace BFO\Jobs;

use BFO\General\Alerter;
use BFO\Caching\CacheControl;
use BFO\General\OddsHandler;
use BFO\General\EventHandler;
use BFO\General\TwitterHandler;
use BFO\General\BookieHandler;
use BFO\Parser\Utils\ParseRunLogger;

class OddsJob
{
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        /*if (PARSE_CREATEMATCHUPS == true) {
            //Check if there are changes proposed by scheduler AND logged as unmatched. These should be automatically created
            $matches = ScheduleHandler::getAllUnmatchedAndScheduled();
        }*/

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

        //Clear old flagged odds that belong to historic matchups
        $this->logger->info("Clearing old flags for historic matchups");
        $result = OddsHandler::removeAllOldFlagged();
        $this->logger->info($result . ' cleared');

        //Delete odds that have been flagged for over 24 hours
        $this->logger->info("Deleting odds that have been flagged for over 24 hours");
        $result = OddsHandler::deleteFlaggedOdds();
        $this->logger->info('Removed ' . $result . ' odds');

        //Move matchups to generic dates if suggested by metadata (gametime)
        if (PARSE_MOVEMATCHUPS) {
            if (PARSE_USE_DATE_EVENTS) {
                $result = EventHandler::moveMatchupsToGenericEvents();
                $this->logger->info("Moved " . $result['moved_matchups'] . " to new dates (checked " . $result['checked_matchups'] . ")");
            } else {
                $result = EventHandler::moveMatchupsToNamedEvents();
                $this->logger->info("Moved " . $result['moved_matchups'] . " to new dates (checked " . $result['checked_matchups'] . ")");
            }
        }

        //Clean up any upcoming matchups without odds
        if (PARSE_REMOVE_EMPTY_MATCHUPS) {
            $matchups = EventHandler::getMatchups(future_matchups_only: true, only_without_odds: true, create_source: 1);
            $counter = 0;
            $audit_log = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'changeaudit.log']);
            foreach ($matchups as $matchup) {
                if ($matchup->getCreateSource() == 1) {
                    EventHandler::removeFight($matchup->getID());
                    $audit_log->info('Removed matchup ' . $matchup->getTeam(1) . ' vs. ' . $matchup->getTeam(2) . ' as it was once automatically created and it now has no odds');
                    $counter++;
                }
            }
            $this->logger->info("Remove empty future matchups previously automatically created: " . $counter);
        }

        //Generate new front page with latest odds
        $plates = new \League\Plates\Engine(GENERAL_BASEDIR . '/app/front/templates/');
        $events = EventHandler::getEvents(future_events_only: true);

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
            $results = TwitterHandler::tweetNewMatchups();
            if ($results['pre_untweeted_events'] == $results['post_tweeted']) {
                $this->logger->info("Tweeted new matchups/events: " . $results['post_tweeted'] . " of " . $results['pre_untweeted_events']);
            } else {
                $this->logger->error("Tweeted new matchups/events: " . $results['post_tweeted'] . " of " . $results['pre_untweeted_events']);
            }
        }

        //Clear old logged runs in database
        $cleared_runs = (new ParseRunLogger())->clearOldRuns();
        $this->logger->info("Cleared old logged runs: " . $cleared_runs);

        $this->logger->info("Finished");
    }
}
