<?php

/**
 * Main schedule parser cron job that does the following
 *
 * - Fetches schedule from MMAJunkie
 * - Parses fetched schedule for missing content
 * - Checks existing content if outdated
 */

require_once __DIR__ . "/../bootstrap.php";

use BFO\Parser\Scheduler\ScheduleParser;
use BFO\Parser\Utils\ParseTools;

echo date('Y-m-d H:i:s') . " - Schedule parser start
";

$schedule = MMAJunkieParser::fetchSchedule();

$sp = new ScheduleParser();
$sp->run($schedule);

echo date('Y-m-d H:i:s') . " - Done
";

class MMAJunkieParser
{
    public static function fetchSchedule()
    {
        $url = 'http://api.mmajunkie.com/rumors/rss';
        //$url = 'http://localhost:8080/rss.txt';
        $curl_opts = array(CURLOPT_USERAGENT => 'MWFeedParser');
        $content = ParseTools::retrievePageFromURL($url, $curl_opts);
        //$content = ParseTools::retrievePageFromFile('C:\dev\bfo\bfo\app\front\rss.txt');

        $xml = simplexml_load_string($content);
        if ($xml == false) {
            echo "Error: XML failed";
        }

        $events = [];
        if (sizeof($xml->channel->item) < 5) {
            //List too small. suspicious..
            echo "Error: List too short";
        }

        foreach ($xml->channel->item as $item) {
            $new_event = [];

            $new_event['title'] = self::renameEvents((string) $item->title);

            $event_regexp = '/<p>Date: ([^<]+)<\/p>/';
            $matches = ParseTools::matchBlock($item->description, $event_regexp);
            $new_event['date'] = strtotime($matches[0][1]);
            $new_event['date'] = strtotime('-4 hour', $new_event['date']);

            $fight_regexp = '/<li>[^<]*<a[^>]*>([^<]*)<\/a[^>]*>[^<]*<a[^>]*>([^<]*)<\/a[^>]*>[^<]*<\/li>/';
            $matches = ParseTools::matchBlock($item->description, $fight_regexp);
            $new_event['matchups'] = array();
            foreach ($matches as $match) {
                //Ignore entries containing TBA
                if (strpos($match[1], 'TBA') === false && strpos($match[2], 'TBA') === false) {
                    $new_event['matchups'][] = array(ParseTools::formatName($match[1]), ParseTools::formatName($match[2]));
                }
            }
            //Only add events that have at least one matchup, also that are not in the past
            if (count($new_event['matchups']) > 0 && $new_event['date'] > time()) {
                $events[] = $new_event;
            }
        }
        return $events;
    }

    private static function renameEvents($event_name)
    {
        $event_name = trim($event_name);
        //Starts with "The Ultimate Fighter"
        if (0 === strpos(strtolower($event_name), 'the ultimate fighter')) {
            $event_name = 'UFC: ' . $event_name;
        }
        return $event_name;
    }
}
