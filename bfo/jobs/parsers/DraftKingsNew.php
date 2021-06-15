<?php

/**
 * Bookie: DraftKings
 * Sport: MMA
 *
 * Timezone: UTC
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'draftkings');
define('BOOKIE_ID', 22);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsbook.draftkings.com']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "draftkings.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        //Grab league IDs for MMA 
        $leagues = ParseTools::retrievePageFromURL($content_urls['all'] . '/api/odds/v1/leagues.json');
        $json = json_decode($leagues, true);
        $league_urls = [];
        foreach ($json['leagues'] as $league) {
            if ($league['sportName'] == 'MMA') {
                $league_urls[$league['name']] = $content_urls['all'] . '/api/odds/v1/leagues/' . $league['leagueId'] . '/offers/gamelines';
                $league_urls['FUTURES ' . $league['name']] = $content_urls['all'] . '/api/odds/v1/leagues/' . $league['leagueId'] . '/offers/futures';
                $league_urls['PROPS ' . $league['name']] = $content_urls['all'] . '/api/odds/v1/leagues/' . $league['leagueId'] . '/offers/props';
            }
        }

        $content = [];
        foreach ($league_urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        ParseTools::retrieveMultiplePagesFromURLs($league_urls);
        foreach ($league_urls as $key => $url) {
            $content[$key] = ParseTools::getStoredContentForURL($league_urls[$key]);
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport('MMA');
        $error_once = false;

        //Process each league
        foreach ($content as $league_name => $json_content) {
            if (!$this->parseLeague($league_name, $json_content)) {
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

    private function parseLeague(string $league_name, string $json_content): bool
    {
        $json = json_decode($json_content);

        //Error checking
        if (!$json) {
            $this->logger->error("Unable to parse proper json for " . $league_name . '. Contents: ' . substr($json_content, 0, 20) . '...');
            return false;
        }
        if (isset($json->errorStatus)) {
            if (in_array($json->errorStatus->code, ['BET419', 'BET420', 'BET421'])) {
                //No matchups found. Not an error
                $this->logger->info("League " . $league_name . " has no matchups");
                return true;
            } else {
                //Error occurred
                $this->logger->error("Unknown error: " . $json->errorStatus->code . " " . $json->errorStatus->developerMessage);
                return false;
            }
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
