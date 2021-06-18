<?php

/**
 * XML Parser
 *
 * Bookie: Bovada
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes
 *
 * URL: http://sportsfeeds.bovada.lv/v1/feed?clientId=1953464&categoryCodes=238&language=en
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'bovada');
define('BOOKIE_ID', 5);
define(
    'BOOKIE_URLS',
    ['all' => 'http://sportsfeeds.bovada.lv/v1/feed?clientId=1953464&categoryCodes=238&language=en'] //Note: PBO fetches the feed from BFO and not bookmaker.eu to avoid being throttled due to too many requests
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "bovada.xml"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($content_urls['all'])];
    }

    public function parseContent(array $content): ParsedSport
    {
        $feed = json_decode($content['all'], true);
        $parsed_sport = new ParsedSport('Boxing');

        foreach ($feed['events'] as $event) {
            //Store metadata and correlation ID
            $correlation_id = $event['id'];

            $date_obj = new DateTime($event['startTime']);
            $date = $date_obj->getTimestamp();

            //Get name from category
            $event_name = $this->getEventFromCategories($event);

            foreach ($event['markets'] as $market) {
                if ($market['status'] == 'OPEN') {
                    if ($market['description'] == 'To Win the Bout'
                        && isset($market['outcomes'][0]['price'], $market['outcomes'][1]['price'])) {
                        //Regular matchup
                        $parsed_matchup = new ParsedMatchup(
                            $market['outcomes'][0]['description'],
                            $market['outcomes'][1]['description'],
                            $market['outcomes'][0]['price']['american'],
                            $market['outcomes'][1]['price']['american']
                        );
                        $parsed_matchup->setCorrelationID($correlation_id);
                        $parsed_matchup->setMetaData('event_name', (string) $event_name);

                        $parsed_matchup->setMetaData('gametime', (string) $date);
                        $parsed_sport->addParsedMatchup($parsed_matchup);
                    } else {
                        //Prop bet
                        if (count($market['outcomes']) > 2) {
                            //Single line prop
                            foreach ($market['outcomes'] as $outcome) {
                                $parsed_prop = new ParsedProp(
                                    $market['description'] . ' :: ' . $outcome['description'],
                                    '',
                                    $outcome['price']['american'],
                                    '-99999'
                                );
                                $parsed_prop->setCorrelationID($correlation_id);
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        } else {
                            //Two sided prop
                            $parsed_prop = new ParsedProp(
                                $market['description'] . ' :: ' . $market['outcomes'][0]['description'],
                                $market['description'] . ' :: ' . $market['outcomes'][1]['description'],
                                $market['outcomes'][0]['price']['american'],
                                $market['outcomes'][1]['price']['american']
                            );
                            $parsed_prop->setCorrelationID($correlation_id);
                            $parsed_sport->addFetchedProp($parsed_prop);
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) >= 5) {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $parsed_sport;
    }

    private function getEventFromCategories($node)
    {
        //Loops through all categories child elements and picks out the one with the longest ID (most specific event)
        $found_desc = '';
        $largest = 0;
        foreach ($node['categories'] as $category) {
            if (intval($category['code']) > $largest) {
                $largest = intval($category['code']);
                $found_desc = $category['description'];
            }
        }
        return $found_desc;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
