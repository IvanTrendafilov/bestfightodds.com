<?php

/**
 * Main schedule parser cron job that does the following
 *
 * - Fetches schedule from MMAJunkie
 * - Parses fetched schedule for missing content
 * - Checks existing content if outdated
 *
 * @author Christian Nordvaller
 */

require_once('lib/bfocore/parser/schedule/class.ScheduleParser.php');

echo 'Schedule parser start
';

//Set timezone to match that of parsed schedule
date_default_timezone_set('America/New_York');

$rSP = new ScheduleParser();
$rSP->parseSched();

echo 'Done
';

?>