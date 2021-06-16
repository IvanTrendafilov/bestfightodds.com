<?php

/**
 * Bookie: BetMGM
 * Sport: Boxing
 *
 * Timezone: UTC
 * 
 * Notes: Can be run without mockfeed from dev/test
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'betmgm');
define('BOOKIE_ID', 23);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsapi.nj.betmgm.com/offer/api/24/us/fixtures?language=en&isInPlay=false&onlyMainMarkets=true']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "betmgm.json"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $api_key_header = [CURLOPT_HTTPHEADER =>
        [
            'Bwin-AccessId: NDM0MjhhNmQtYzE2Yi00NjNmLWJlNWQtZmJlZGUxYTIxOTAw',
            'Bwin-AccessIdToken: 4L78QyBjWyKPeSHHIJ0KIg=='
        ]];

        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($content_urls['all'], $api_key_header)];
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport();

        $json = json_decode($content['all']);

        //Error checking
        if (!$json) {
            $this->logger->error('Unable to parse proper json. Contents: ' . substr($content['all'], 0, 20) . '...');
            return $this->parsed_sport;
        }
        if (isset($json->message)) {
            $this->logger->error("Unknown error: " . $json->message);
            return $this->parsed_sport;
        }

        foreach ($json->items as $market) {
            $this->parseMarket($market);
        }

        //Declare full run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) > 3) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseMarket($matchup): void
    {
        //Validate required fields
        if (
            !isset($matchup->id?->full)
            || !isset($matchup->startDateUtc)
            || empty($matchup->startDateUtc)
            || $matchup->isInPlay
            || !isset($matchup->markets)
            || count($matchup->markets) < 1
        ) {
            return;
        }

        foreach ($matchup->markets as $market) {

            if ($market->name->text == 'Fight Result (3-way)') {
                //Regular matchup
                $this->parseMatchup($matchup, $market);
            } else {
                //Prop
                $this->parseProp($matchup, $market);
            }
        }
    }

    private function parseMatchup($matchup, $market): bool
    {
        if (
            empty($matchup->participants[0]->name->text) ||
            empty($matchup->participants[1]->name->text) ||
            empty($market->options[0]->price?->usOdds) ||
            empty($market->options[2]->price?->usOdds)
        ) {
            return false;
        }

        $parsed_matchup = new ParsedMatchup(
            $matchup->participants[0]->name->text,
            $matchup->participants[1]->name->text,
            $market->options[0]->price?->usOdds,
            $market->options[2]->price?->usOdds
        );

        $date_obj = new DateTime((string) $matchup->startDateUtc);
        $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
        $parsed_matchup->setMetaData('event_name', $matchup->competition?->name?->text);
        $parsed_matchup->setCorrelationID($matchup->id->full);

        $this->parsed_sport->addParsedMatchup($parsed_matchup);

        //Add draw
        $matchup_text = ParseTools::formatName($matchup->participants[0]->name->text) . ' vs. ' . ParseTools::formatName($matchup->participants[1]->name->text);
        $parsed_prop = new ParsedProp(
            $matchup_text . ' :: FIGHT IS A DRAW',
            '',
            $market->options[1]->price?->usOdds,
            '-99999'
        );
        $parsed_prop->setCorrelationID($matchup->id->full);
        $this->parsed_sport->addFetchedProp($parsed_prop);

        return true;
    }

    private function parseProp($matchup, $market): bool
    {
        $matchup_text = ParseTools::formatName($matchup->participants[0]->name->text) . ' vs. ' . ParseTools::formatName($matchup->participants[1]->name->text);
        if (count($market->options) == 2) {
            //Two way prop
            if (
                empty($market->options[0]->name?->text) ||
                empty($market->options[1]->name?->text) ||
                empty($market->options[0]->price?->usOdds) ||
                empty($market->options[1]->price?->usOdds)
            ) {
                return false;
            }

            $parsed_prop = new ParsedProp(
                $matchup_text . ' :: ' . $market->name->text . ' : ' . $market->options[0]->name->text,
                $matchup_text . ' :: ' . $market->name->text . ' : ' . $market->options[1]->name->text,
                $market->options[0]->price?->usOdds,
                $market->options[1]->price?->usOdds
            );
            $parsed_prop->setCorrelationID($matchup->id->full);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        } else {
            //Single line prop
            foreach ($market->options as $option) {
                if (
                    !empty($option->name?->text) &&
                    !empty($option->price?->usOdds)
                ) {

                    $parsed_prop = new ParsedProp(
                        $matchup_text . ' :: ' . $market->name->text . ' : ' . $option->name->text,
                        '',
                        $option->price->usOdds,
                        '-99999'
                    );
                    $parsed_prop->setCorrelationID($matchup->id->full);
                    $this->parsed_sport->addFetchedProp($parsed_prop);
                }
            }
        }
        return true;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
