<?php

/**
 * XML Parser
 *
 * Bookie: Pinnacle
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes (only totals)
 *
 * URL: https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/*
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'pinnacle');
define('BOOKIE_ID', 9);
define(
    'BOOKIE_URLS',
    [
        'ufc' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/1624',
        'bellator' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/1619',
        'cagewarriors' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/208746',
        'invicta' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/208187',
        'pfl' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/207442',
        'lfa' => 'https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/22/208185'
    ]
);
define(
    'BOOKIE_MOCKFILES',
    [
        'ufc' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml",
        'bellator' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml",
        'cagewarriors' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml",
        'invicta' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml",
        'pfl' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml",
        'lfa' => PARSE_MOCKFEEDS_DIR . "pinnacle.xml"
    ]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $content = [];
        foreach ($this->content_urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        ParseTools::retrieveMultiplePagesFromURLs($content_urls);
        foreach ($this->content_urls as $key => $url) {
            $content[$key] = ParseTools::getStoredContentForURL($content_urls[$key]);
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $parsed_sport = new ParsedSport('MMA');
        $failed_once = false;
        foreach ($content as $key => $part) {
            $counter = 0;
            $json = json_decode($part, true);
            if (!$json || $part == '') {
                $this->logger->error('Content fail for ' . $key . '(' . $this->content_urls[$key] . ')');
                $failed_once = true;
            }

            foreach ($json['Leagues'] as $league_node); {
                foreach ($league_node['Events'] as $event) {
                    if (count($event['Participants']) == 2) {
                        //Regular matchup

                        //Replace anylocation indicator with blank
                        $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][1]['Name']);
                        $event['Participants'][0]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][1]['Name']);
                        $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][1]['Name']);

                        $parsed_matchup = new ParsedMatchup(
                            $event['Participants'][0]['Name'],
                            $event['Participants'][1]['Name'],
                            round($event['Participants'][0]['MoneyLine']),
                            round($event['Participants'][1]['MoneyLine'])
                        );
                        $parsed_matchup->setCorrelationID((string) $event['EventId']);

                        //Add time of matchup as metadata
                        if (isset($event['Cutoff'])) {
                            $oGameDate = new DateTime((string) $event['Cutoff']);
                            $parsed_matchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        $parsed_sport->addParsedMatchup($parsed_matchup);
                        $counter++;

                        //Adds over/under if available
                        if (isset($event['Totals'])) {
                            $parsed_prop = new ParsedProp(
                                (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Over ' . $event['Totals']['Min'] . ' rounds',
                                (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Under ' . $event['Totals']['Min'] . ' rounds',
                                round($event['Totals']['OverPrice']),
                                round($event['Totals']['UnderPrice'])
                            );
                            $parsed_prop->setCorrelationID((string) $event['EventId']);
                            $parsed_sport->addFetchedProp($parsed_prop);
                        }
                    }
                }
            }

            $this->logger->info('URL ' . $this->content_urls[$key] . ' provided ' . $counter . ' matchups');
        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 10) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
