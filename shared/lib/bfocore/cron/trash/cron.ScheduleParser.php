<?php

/**
 * Main schedule parser cron job that does the following
 *
 * - Fetches schedule from MMAJunkie
 * - Parses fetched schedule for missing content
 * - Checks existing content if outdated
 */

require_once('lib/bfocore/parser/schedule/class.ScheduleParser.php');

echo 'Schedule parser start
';

$rSP = new ScheduleParser();
$rSP->parseSched();

echo 'Done
';
