<?php

/**
 * Bookie: William Hill
 * Sport: MMA
 *
 * Timezone: TBD
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

define('BOOKIE_NAME', 'williamhillus');
define('BOOKIE_ID', 23);
define(
    'BOOKIE_URLS',
    ['all' => 'https://odds.us.williamhill.com/api/v1/competitions?sportId=ufcmma']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "williamhillus.json"]
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
        $this->parsed_sport = new ParsedSport('MMA');
        $error_once = false;

        //Process each league
        foreach ($content as $league_name => $json_content) {
            if (!$this->parseEvent($league_name, $json_content)) {
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

    private function parseEvent(string $league_name, string $json_content): bool
    {
        $json = json_decode($json_content);

        //Error checking
        if (!$json) {
            $this->logger->error("Unable to parse proper json for " . $league_name . '. Contents: ' . substr($json_content, 0, 20) . '...');
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
        $this->logger->info("Processing league " . $league_name);

        //Loop through events and grab matchups
        foreach ($json->events as $matchup) {
            foreach ($matchup->offers as $offer) {
                if (
                    $offer->label == 'Moneyline' &&
                    isset(
                        $matchup->id,
                        $matchup->startDate,
                        $offer?->outcomes[0]->oddsAmerican,
                        $offer?->outcomes[0]->participant,
                        $offer?->outcomes[1]->oddsAmerican,
                        $offer?->outcomes[1]->participant
                    )
                ) {
                    $this->parseMatchup($league_name, $matchup, $offer);
                } else if (str_starts_with($league_name, 'PROPS ')) {
                    $this->parseProp($matchup, $offer);
                }
            }
        }

        return true;
    }

    private function parseMatchup($league_name, $matchup, $offer): void
    {
        $parsed_matchup = new ParsedMatchup(
            $offer->outcomes[0]->participant,
            $offer->outcomes[1]->participant,
            $offer->outcomes[0]->oddsAmerican,
            $offer->outcomes[1]->oddsAmerican
        );

        $date_obj = new DateTime((string) $matchup->startDate);
        $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
        if (str_starts_with($league_name, 'FUTURES ')) {
            $parsed_matchup->setMetaData('event_name', substr($league_name, 8)); //Remove FUTURE part
        } else {
            $parsed_matchup->setMetaData('event_name', $league_name);
        }

        $parsed_matchup->setCorrelationID($matchup->id);

        $this->parsed_sport->addParsedMatchup($parsed_matchup);
    }

    private function parseProp($matchup, $offer): void
    {
        if (count($offer->outcomes) == 2) {
            //Two way prop
            $parsed_prop = new ParsedProp(
                $matchup->homeTeamName . ' vs. ' . $matchup->awayTeamName . ' :: ' . $offer->label . ' : ' . $offer->outcomes[0]->label,
                $matchup->homeTeamName . ' vs. ' . $matchup->awayTeamName . ' :: ' . $offer->label . ' : ' . $offer->outcomes[1]->label,
                $offer->outcomes[0]->oddsAmerican,
                $offer->outcomes[1]->oddsAmerican
            );
            $parsed_prop->setCorrelationID($matchup->id);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        } else {
            //Single line prop
            foreach ($offer->outcomes as $outcome) {
                $parsed_prop = new ParsedProp(
                    $matchup->homeTeamName . ' vs. ' . $matchup->awayTeamName . ' :: ' . $offer->label . ' : ' . $outcome->label,
                    '',
                    $outcome->oddsAmerican,
                    '-99999'
                );
                $parsed_prop->setCorrelationID($matchup->id);
                $this->parsed_sport->addFetchedProp($parsed_prop);
            }
        }
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
