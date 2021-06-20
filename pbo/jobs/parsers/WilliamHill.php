<?php

/**
 * Bookie: William Hill
 * Sport: Boxing
 *
 * Timezone: UTC
 * 
 * Notes: Can be run in dev/test towards actual URLs (not using mock)
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'williamhill');
define('BOOKIE_ID', 24);
define(
    'BOOKIE_URLS',
    ['all' => 'https://odds.us.williamhill.com/api/v1/competitions?sportId=boxing']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "williamhill.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $api_key = 'hgpAaGGYqpSNljzBG2iHfm03Er4ZlFkxSTcfPQEtF';

        $competitions = ParseTools::retrievePageFromURL($content_urls['all'], [CURLOPT_HTTPHEADER => ['X-Api-Key: ' . $api_key]]);
        $competitions_json = json_decode($competitions);

        if (isset($competitions_json->message) && $competitions_json->message == 'Forbidden') {
            $this->logger->error("Feed responded with forbidden. Check API key");
            return [];
        }

        $comp_urls = [];
        foreach ($competitions_json as $competition) {
            $comp_urls[$competition->name] = 'https://odds.us.williamhill.com/api/v1/events?competitionId=' . $competition->id . '&includeMarkets=true';;
        }
        $content = [];
        foreach ($comp_urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        foreach ($comp_urls as $key => $url) {
            sleep(1); //Avoid throttling
            $content[$key] = ParseTools::retrievePageFromURL($url, [CURLOPT_HTTPHEADER => ['X-Api-Key: ' . $api_key]]);
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport();
        $error_once = false;

        //Process each league
        foreach ($content as $event_name => $json_content) {
            if (!$this->parseEvent($event_name, $json_content)) {
                $error_once = true;
            }
        }

        //Declare full run if we fill the criteria
        if (!$error_once && count($this->parsed_sport->getParsedMatchups()) > 3) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseEvent(string $event_name, string $json_content): bool
    {
        $json = json_decode($json_content);

        //Error checking
        if (!$json) {
            $this->logger->error('Unable to parse proper json for ' . $event_name . '. Contents: ' . substr($json_content, 0, 20) . '...');
            return false;
        }
        if (isset($json->message)) {
            if ($json->message == 'Forbidden') {
                $this->logger->error("Feed responded with forbidden. Check API key");
            } else {
                //Other error occurred
                $this->logger->error("Unknown error: " . $json->message);
            }
            return false;
        }
        $this->logger->info("Processing event " . $event_name);

        //Loop through events and grab matchups
        foreach ($json as $matchup) {
            foreach ($matchup->markets as $market) {
                if (!$market->tradedInPlay) {
                    if (
                        $market->name == 'Bout Betting 2 Way' &&
                        isset(
                            $matchup->id,
                            $matchup->startTime,
                            $market?->selections[0]->name,
                            $market?->selections[0]->price->a,
                            $market?->selections[1]->name,
                            $market?->selections[1]->price->a
                        )
                    ) {
                        $this->parseMatchup($matchup, $market);
                    } else {
                        $this->parseProp($matchup, $market);
                    }
                }
            }
        }

        return true;
    }

    private function parseMatchup($matchup, $market): void
    {
        if (
            !empty($market->selections[0]->name) &&
            !empty($market->selections[1]->name) &&
            !empty($market->selections[0]->price?->a) &&
            !empty($market->selections[1]->price?->a)
        ) {
            $date_obj = new DateTime((string) $matchup->startTime);
            if ($date_obj < new DateTime()) {
                return;
            }

            $parsed_matchup = new ParsedMatchup(
                $market->selections[0]->name,
                $market->selections[1]->name,
                $market->selections[0]->price->a,
                $market->selections[1]->price->a
            );

            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            $parsed_matchup->setMetaData('event_name', $matchup->competitionName);
            $parsed_matchup->setCorrelationID($matchup->id);

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }

    private function parseProp($matchup, $market): void
    {
        if (count($market->selections) == 2) {
            //Two way prop
            if (
                !empty($market->selections[0]->name) &&
                !empty($market->selections[1]->name) &&
                !empty($market->selections[0]->price?->a) &&
                !empty($market->selections[1]->price?->a)
            ) {

                $parsed_prop = new ParsedProp(
                    $matchup->name . ' :: ' . $market->name . ' : ' . $market->selections[0]->name,
                    $matchup->name . ' :: ' . $market->name . ' : ' . $market->selections[1]->name,
                    $market->selections[0]->price?->a,
                    $market->selections[1]->price?->a
                );
                $parsed_prop->setCorrelationID($matchup->id);
                $this->parsed_sport->addFetchedProp($parsed_prop);
            }
        } else {
            //Single line prop
            foreach ($market->selections as $selection) {
                if (
                    !empty($selection->name) &&
                    !empty($selection->price?->a)
                ) {
                    $parsed_prop = new ParsedProp(
                        $matchup->name . ' :: ' . $market->name . ' : ' . $selection->name,
                        '',
                        $selection->price->a,
                        '-99999'
                    );
                    $parsed_prop->setCorrelationID($matchup->id);
                    $this->parsed_sport->addFetchedProp($parsed_prop);
                }
            }
        }
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
