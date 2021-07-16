<?php

/**
 * Bookie: Fanduel 
 * Sport: Boxing
 *
 * Timezone: UTC
 * 
 * Notes: Can be run in dev/test towards actual URLs (not using mock).
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;
use BFO\Utils\OddsTools;

define('BOOKIE_NAME', 'fanduel');
define('BOOKIE_ID', 21);
define(
    'BOOKIE_URLS',
    ['all' => 'https://affiliates.sportsbook.fanduel.com/betting/rest/v1/listCompetitions/']
);
define(
    'BOOKIE_MOCKFILES',
    ['UFC' => PARSE_MOCKFEEDS_DIR . "fanduel.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;
    private array $matchup_references = [];

    public function fetchContent(array $content_urls): array
    {
        $sport_id = '6';

        $headers = [
            "X-Application: WPulikw4grbXS2wf",
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        $payload = '{
            "listCompetitionsRequestParams": {
              "marketFilter": {
                  "eventTypeIds": [' . $sport_id . ']
              }
            }
          }';


        //First we parse the competitions feed that provides the available subtypes for the sport
        $competitions = ParseTools::retrievePageFromURL($content_urls['all'], [CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $payload]);
        $json = json_decode($competitions);
        if (!$json) {
            $this->logger->error('Unable to parse json' . substr($competitions, 0, 50) . '..');
            return ['all' => null];
        } else if (isset($json->faultcode)) {
            $this->logger->error('Error reported when fetching competitions: ' . $json->faultcode . ": " . $json->faultstring . " details: " . var_export($json->detail));
            return ['all' => null];
        }

        $urls = [];
        //Fanduel structure forces us to create this foreach pyramid
        foreach ($json as $competition_node) {
            sleep(1); //Avoid throttling
            $payload = '{
                "bulkListMarketPricesRequestParams": {
                  "eventTypeId": ' . $sport_id . ',
                  "competitionId": ' . $competition_node->competition->id . '
                }
              }';
            $this->logger->info("Fetching matchups for competition: " . $competition_node->competition->name . " (" . $competition_node->competition->id . ")");
            $content[$competition_node->competition->name] = ParseTools::retrievePageFromURL(
                'https://affiliates.sportsbook.fanduel.com/betting/rest/v1/bulkListMarketPrices/',
                [CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $payload]
            );
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport();
        $error_once = false;

        //Process each competition
        foreach ($content as $competition_name => $json_content) {
            if (!$this->parseCompetition($competition_name, $json_content)) {
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

    private function parseCompetition(string $competition_name, ?string $json_content): bool
    {
        $json = json_decode($json_content);
        //Error checking
        if (!$json) {
            $this->logger->error("Unable to parse proper json for " . $competition_name . '. Contents: ' . substr($json_content, 0, 50) . '...');
            return false;
        }
        if (isset($json->faultcode)) {
            $this->logger->error('Error reported when fetching competitions: ' . $json->faultcode . ": " . $json->faultstring . " details: " . var_export($json->detail));
            return false;
        }
        $this->logger->info("Processing competition " . $competition_name);

        if ($competition_name != 'Marquee Fights') {
            $parts = explode(' ', $competition_name);
            $competition_name = $parts[0];
        }

        //Process matchups first
        foreach ($json->marketDetails as $market) {
            if ($market->marketType == "HEAD_TO_HEAD") {
                $this->parseMatchup($competition_name, $market);
            }
        }

        //Process props
        foreach ($json->marketDetails as $market) {
            if ($market->marketType != "HEAD_TO_HEAD" && $market->marketType != "BOXING_MATCH_BETTING") {
                $this->parseProp($competition_name, $market);
            }
        }

        return true;
    }

    private function parseMatchup(string $competition_name, $market): void
    {
        if (
            isset($market->runnerDetails)
            && count($market->runnerDetails) == 2
            && !empty($market->runnerDetails[0]->selectionName)
            && !empty($market->runnerDetails[1]->selectionName)
            && !empty($market->runnerDetails[0]->winRunnerOdds->decimal)
            && !empty($market->runnerDetails[1]->winRunnerOdds->decimal)
            && !empty($market->marketStartTime
                && !$market->inplay)
        ) {
            //Skip live events
            $date_obj = new DateTime((string) $market->marketStartTime);
            if ($date_obj <= new DateTime()) {
                $this->logger->info("Skipping live odds for " . $market->runnerDetails[0]->selectionName . " vs " . $market->runnerDetails[1]->selectionName);
                return;
            }

            $parsed_matchup = new ParsedMatchup(
                $market->runnerDetails[0]->selectionName,
                $market->runnerDetails[1]->selectionName,
                OddsTools::convertDecimalToMoneyline($market->runnerDetails[0]->winRunnerOdds->decimal),
                OddsTools::convertDecimalToMoneyline($market->runnerDetails[1]->winRunnerOdds->decimal)
            );

            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            if ($competition_name != 'FanDuel') {
                $parsed_matchup->setMetaData('event_name', $competition_name);
            }

            $parsed_matchup->setCorrelationID((string) $market->eventId);

            $this->matchup_references[$market->eventId] = $market->runnerDetails[0]->selectionName . " vs. " . $market->runnerDetails[1]->selectionName;

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }

    private function parseProp(string $competition_name, $market): void
    {
        if (
            isset($market->runnerDetails)
            && count($market->runnerDetails) > 0
            && !$market->inplay
            && isset($this->matchup_references[$market->eventId])
        ) {

            //Skip live events
            $date_obj = new DateTime((string) $market->marketStartTime);
            if ($date_obj <= new DateTime()) {
                return;
            }

            if (
                count($market->runnerDetails) == 2
                && in_array(strtoupper($market->runnerDetails[0]->selectionName), ['YES', 'NO', 'OVER', 'UNDER'])
                && in_array(strtoupper($market->runnerDetails[1]->selectionName), ['YES', 'NO', 'OVER', 'UNDER'])
            ) {
                //Two-sided prop
                $handicap_1 = $market->runnerDetails[0]->handicap != 0.0 ? (string) $market->runnerDetails[0]->handicap : '';
                $handicap_2 = $market->runnerDetails[1]->handicap != 0.0 ? (string) $market->runnerDetails[1]->handicap : '';

                $parsed_prop = new ParsedProp(
                    $this->matchup_references[$market->eventId] . ' :: ' . $market->marketName . ' : '
                        . $market->runnerDetails[0]->selectionName . ' ' . $handicap_1,
                    $this->matchup_references[$market->eventId] . ' :: ' . $market->marketName . ' : '
                        . $market->runnerDetails[1]->selectionName . ' ' . $handicap_2,
                    $market->runnerDetails[0]->winRunnerOdds->decimal,
                    $market->runnerDetails[1]->winRunnerOdds->decimal
                );
                $parsed_prop->setCorrelationID((string) $market->eventId);
                $this->parsed_sport->addFetchedProp($parsed_prop);
            } else {
                //Multi-way prop
                foreach ($market->runnerDetails as $runner) {

                    if (
                        !str_contains(strtoupper($market->marketName), 'TIME OF FIGHT FINISH') && !str_contains(strtoupper($market->marketName), 'WHEN WILL THE FIGHT BE WON')
                        && !str_contains(strtoupper($market->marketName), 'DOUBLE CHANCE') && !str_contains(strtoupper($market->marketName), 'WHAT MINUTE WILL THE FIGHT END IN')
                    ) {

                        $handicap_1 = $runner->handicap != 0.0 ? (string) $runner->handicap : '';
                        $parsed_prop = new ParsedProp(
                            $this->matchup_references[$market->eventId] . ' :: ' . $market->marketName . ' : '
                                . $runner->selectionName . ' ' . $handicap_1,
                            '',
                            $runner->winRunnerOdds->decimal,
                            '-99999'
                        );
                        $parsed_prop->setCorrelationID((string) $market->eventId);
                        $this->parsed_sport->addFetchedProp($parsed_prop);
                    }
                }
            }
        }
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
