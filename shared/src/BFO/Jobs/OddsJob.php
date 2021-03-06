<?php

/**
 *  Handles automatic generation of content as well as housekeeping activities:
 * 
 * - Generate a new front page
 * - Tweet new odds
 * - Send out any alerts that have should be triggered
 *
 * - Creates and deletes matchups proposed by scheduler
 * - Clears the image cache for widgets
 * - Clears the cache for graph data
 * - Cleans up old parsing correlations
 * - Removes unused removal flags on odds
 * - Deletes odds that have been flagged for deletion
 * - Moves matchups to events based on sportsbook metadata
 * - Cleanup of un-used odds
 */

namespace BFO\Jobs;

use BFO\General\Alerter;
use BFO\Caching\CacheControl;
use BFO\General\OddsHandler;
use BFO\General\EventHandler;
use BFO\General\TwitterHandler;
use BFO\General\BookieHandler;
use BFO\General\ScheduleHandler;
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
        if (PARSE_CREATEMATCHUPS == true) {
            //Check if there are create changes proposed by scheduler. These should be automatically created
            $created = ScheduleHandler::acceptAllCreateActions();
            $this->logger->info("Auto creating matchups found by scheduler: " . $created);

            $removed = ScheduleHandler::acceptAllDeleteActions();
            $this->logger->info("Auto removing matchups no longer relevant in scheduler: " . $removed);
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

        //Clear old flagged odds that belong to historic matchups
        $result = OddsHandler::removeAllOldFlagged();
        $this->logger->info("Clearing old flags for historic matchups: " . $result);

        //Delete odds that have been flagged for over 24 hours
        $result = OddsHandler::deleteFlaggedOdds();
        $this->logger->info("Deleting odds that have been flagged for over 3 hours: " . $result . " removed");

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
            $counter = EventHandler::deleteMatchupsWithoutOdds();
            $this->logger->info("Remove empty future matchups previously automatically created: " . $counter);
        }
        
        //Clean up any historic events without matchups
        if (PARSE_REMOVE_EMPTY_EVENTS) {
            $counter = EventHandler::deleteAllOldEventsWithoutOdds();
            $this->logger->info("Remove empty historic events without matchups: " . $counter);
        }

        //Generate new front page with latest odds
        $plates = new \League\Plates\Engine(GENERAL_BASEDIR . '/app/front/templates/');
        $events = EventHandler::getEvents(future_events_only: true);

        $view_data = [];
        $view_data['bookies'] = BookieHandler::getAllBookies(exclude_inactive: true);

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
