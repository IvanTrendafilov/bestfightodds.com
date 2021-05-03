<?php

/**
 * Main schedule parser cron job that does the following
 *
 * - Fetches schedule from RingTV
 * - Parses fetched schedule for missing content
 * - Checks existing content if outdated
 *
 */

use BFO\Parser\Scheduler\ScheduleParser;
use BFO\Parser\Utils\ParseTools;

require_once('lib/simple_html_dom/simple_html_dom.php');

echo "Schedule parser start
";

//Set timezone to match that of parsed schedule

$rSP = new ScheduleParser();
$rSP->parseSchedPreFetched(fetchRingTVSchedule());

echo "Done
";

function fetchRingTVSchedule()
{
    $sURL = 'http://ringtv.craveonline.com/schedule';
    $aCurlOpts = array(CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
    $sContents = ParseTools::retrievePageFromURL($sURL, $aCurlOpts);

    $html = new simple_html_dom();
    $maindiv = $html->load($sContents)->find("div.schedule-content");
    $events = [];

    foreach ($maindiv[0]->find("h3") as $dateheader)
    {
        $event_timestamp = strtotime($dateheader->plaintext . ', 23:59');
        $event = array();
        $event['title'] = date('Y-m-d', $event_timestamp);
        $event['date'] = $event_timestamp;
        $event['matchups'] = array(); 

        $matchup = $dateheader->find(".each-event h1 a");

        $matchup_teams = explode(" vs. ", html_entity_decode($matchup[0]->plaintext));
        //Ignore entries containing TBA
        if (strpos($matchup_teams[0], 'TBA') === false && strpos($matchup_teams[1], 'TBA') === false)
        {
            $event['matchups'][] = [ParseTools::formatFighterName($matchup_teams[0]), ParseTools::formatFighterName($matchup_teams[1])];
        }

        //Only add events that have at least one matchup, also that are not in the past
        if (count($event['matchups']) > 0 && $event['date'] >= time())
        {
            $events[] = $event;
        }
    }

    return $events;
}

?>