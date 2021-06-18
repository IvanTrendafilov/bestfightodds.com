<?php

/**
 * Main schedule parser cron job that does the following
 *
 * - Fetches schedule from external source
 * - Parses fetched schedule for missing content
 * - Checks existing content if outdated
 */

require_once __DIR__ . "/../bootstrap.php";

use BFO\Parser\Scheduler\ScheduleParser;
use BFO\Parser\Utils\ParseTools;

echo date('Y-m-d H:i:s') . " - Schedule parser start
";

$schedule = WikiScheduleParser::fetchSchedule();
$sp = new ScheduleParser();
$sp->run($schedule);

echo date('Y-m-d H:i:s') . " - Done. Found: " . count($schedule) . " upcoming events
";

/**
 * Collects upcoming UFC events and matchups from Wikipedia and parses these in a structure that can be passed to ScheduleParser
 */
class WikiScheduleParser
{
    public static function fetchSchedule(): array
    {
        $parsed_events = [];
        $error_once = false;

        $section_num = self::findWikipediaSection('List_of_UFC_events', 'Scheduled events');
        if (!$section_num) {
            echo "Failed, no section number for List of UFC events";
            exit;
        }

        //Fetch event names from the upcoming UFC events
        $wiki_text = self::fetchWikipediaPage('List_of_UFC_events', $section_num);
        $upcoming_events = null;
        preg_match_all('/\[\[(?<event_name>[^\]]+)\]\][\\n\s\|]+\{\{dts\|(?<event_date>[^\}]+)\}\}/i', $wiki_text, $upcoming_events, PREG_SET_ORDER);

        foreach ($upcoming_events as &$upcoming_event) {
            //Check if match contains |, then split and take first
            $parts = explode('|', $upcoming_event['event_name']);
            $upcoming_event['event_name'] = $parts[0];
            $announced_parsed_str = self::normalizePageContent(self::fetchWikipediaPage($upcoming_event['event_name']));

            $event_details_name = null;
            $event_details_date = null;
            preg_match('/\{\{Infobox MMA event.*?\|name=\s*(?<event_name>[^\|]+)\|/i', $announced_parsed_str, $event_details_name);
            preg_match('/\{\{start date.*?\|(?<event_date>\d{4}\|\d{2}\|\d{2})/i', $announced_parsed_str, $event_details_date);
            if (!$event_details_name || !$event_details_date || empty($event_details_name['event_name']) || empty($event_details_date['event_date'])) {
                echo 'Failed to retrieve event details for ' . $upcoming_event['event_name'];
                echo "\nevent_details_name: " . var_export($event_details_name ?? 'null') . "\n";
                echo "event_details_date: " . var_export($event_details_date ?? 'null') . "\n";
                exit;
            }

            $date_obj = DateTime::createFromFormat('Y\|m\|d H:i:s', $event_details_date['event_date'] . ' 23:59:59');
            $new_event = [
                'title' => self::renameEvents($event_details_name['event_name']),
                'date' => $date_obj->getTimestamp(),
                'matchups' => [],
                'failed' => false
            ];

            $fightcard_matches = null;
            preg_match_all("/\{\{MMAevent bout[\s]*\|[^|]*\|(?<team1_name>[a-zA-Z0-9\s'-\.]+)\|\s?vs\.?\s?\|(?<team2_name>[a-zA-Z0-9\s'-\.]+)\|/", $announced_parsed_str, $fightcard_matches, PREG_SET_ORDER);
            foreach ($fightcard_matches as $fightcard_match) {
                if (strpos(strtoupper($fightcard_match['team1_name']), 'TBA') === false && strpos(strtoupper($fightcard_match['team2_name']), 'TBA') === false &&
                    strpos(strtoupper($fightcard_match['team1_name']), 'TBD') === false && strpos(strtoupper($fightcard_match['team2_name']), 'TBD') === false) {
                    $new_event['matchups'][] = [ParseTools::formatName($fightcard_match['team1_name']), ParseTools::formatName($fightcard_match['team2_name'])];
                }
            }

            //Find announced bouts by first getting the section for it
            $section_num = self::findWikipediaSection($upcoming_event['event_name'], 'Announced_bouts');
            if ($section_num) {
                $announced_parsed_str = self::normalizePageContent(self::fetchWikipediaPage($upcoming_event['event_name'], $section_num));
                if ($announced_parsed_str && !empty($announced_parsed_str)) {
                    //Parse the announce content
                    $team_matches = null;
                    preg_match_all("/[bB]out:\s(?<team1_name>[a-zA-Z0-9\s'-\.]+) vs. (?<team2_name>[a-zA-Z0-9\s'-\.]+)/", $announced_parsed_str, $team_matches, PREG_SET_ORDER);
                    foreach ($team_matches as $team_match) {
                        if (strpos($team_match['team1_name'], 'TBA') === false && strpos($team_match['team2_name'], 'TBA') === false) {
                            $new_event['matchups'][] = [ParseTools::formatName($team_match['team1_name']), ParseTools::formatName($team_match['team2_name'])];
                        }
                    }
                } else {
                    $new_event['failed'] = true;
                    echo "Failed to retrieve announced matchup page for " . $upcoming_event['event_name'] ."
";
                }
            }

            if (count($new_event['matchups']) > 0 && $date_obj >= new DateTime()) {
                $parsed_events[] = $new_event;
            }
        }
        return $parsed_events;
    }

    private static function renameEvents(string $event_name): string
    {
        $event_name = trim($event_name);
        //Starts with "The Ultimate Fighter"
        if (0 === strpos(strtolower($event_name), 'the ultimate fighter')) {
            $event_name = 'UFC: ' . $event_name;
        }
        return $event_name;
    }

    private static function normalizePageContent(string $content): string
    {
        $content = str_replace(['[', ']', '(c)'], '', $content);
        $content = str_replace("\n", "", $content);
        $content = ParseTools::stripForeignChars($content);
        //Trims multiple spaces to single space:
        $content = preg_replace('/\h{2,}/', ' ', $content);
        return $content;
    }

    private static function findWikipediaSection(string $page_name, string $section_name): ?int
    {
        $anchored_title = str_replace(' ', '_', $section_name);
        $sections_url = 'https://en.wikipedia.org/w/api.php?action=parse&format=json&page=' . urlencode($page_name) . '&prop=sections';
        $sections_content = ParseTools::retrievePageFromURL($sections_url);
        $json = json_decode($sections_content, true);
        foreach ($json['parse']['sections'] as $sections) {
            if (strtolower(trim($sections['line'])) == strtolower(trim($section_name)) || strtolower(trim($sections['anchor'])) == strtolower(trim($anchored_title))) {
                return intval($sections['index']);
            }
        }
        return null;
    }

    private static function fetchWikipediaPage(string $page_name, int $section = null): ?string
    {
        $url = 'https://en.wikipedia.org/w/api.php?redirects=true&action=parse&format=json&page=' . urlencode($page_name) . '&prop=wikitext';
        if ($section) {
            $url .= '&section=' . $section;
        }
        $content = ParseTools::retrievePageFromURL($url);
        $json = json_decode($content, true);
        return $json['parse']['wikitext']['*'] ?? null;
    }
}
