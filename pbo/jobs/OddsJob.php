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
require_once __DIR__ . "/../bootstrap.php";

$logger = new \Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, \Psr\Log\LogLevel::INFO, ['filename' => 'cron.oddsjob.' . time() . '.log']);

$oj = new BFO\Jobs\OddsJob($logger);
$oj->run();