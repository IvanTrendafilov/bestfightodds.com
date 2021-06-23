<?php

/**
 * Bookie: BetRivers
 * Sport: Boxing
 *
 * Timezone: UTC
 * 
 * Notes: Appears to be geoblocking access to the feed. Meaning it can be accessed from dev/test but dev/test needs to be located in the US
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'betrivers');
define('BOOKIE_ID', 25);
define(
    'BOOKIE_URLS',
    ['all' => 'https://mi.betrivers.com/api/service/sportsbook/offering/feed?key=d45545a5-9d01-4930-825c-e262528bd9ae']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "betrivers.json"]
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

        foreach ($json->events as $event) {
            $this->parseEvent($event);
        }

        //Declare full run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) > 3) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseEvent($event): void
    {
        //Validate required fields
        if (
            !isset($event->id)
            || !isset($event->participants)
            || count($event->participants) != 2
            || !isset($event->start)
            || $event->state != 'NOT_STARTED'
            || !isset($event->betOffers)
            || count($event->betOffers) < 1
        ) {
            return;
        }

        //Convert names from lastname, firstname to firstname lastname
        $team1_name = ParseTools::convertCommaNameToFullName($event->participants[0]->name);
        $team2_name = ParseTools::convertCommaNameToFullName($event->participants[1]->name);

        foreach ($event->betOffers as $betoffer) {
            if ($betoffer->betOfferType == 'Match' && $betoffer->betDescription == 'Tie No Bet - Bout Odds') {
                //Regular matchup
                $this->parseMatchup($event, $team1_name, $team2_name, $betoffer);
            } else {
                //Prop bet
                $this->parseProp($event, $team1_name, $team2_name, $betoffer);
            }
        }
    }

    private function parseMatchup($event, string $team1_name, string $team2_name, $betoffer): bool
    {
        //Validate input
        if (
            empty($team1_name)
            || empty($team2_name)
            || !isset($betoffer->outcomes)
            || count($betoffer->outcomes) != 2
            || empty($betoffer->outcomes[0]->oddsAmerican)
            || empty($betoffer->outcomes[1]->oddsAmerican)
        ) {
            return false;
        }

        $parsed_matchup = new ParsedMatchup(
            $team1_name,
            $team2_name,
            $betoffer->outcomes[0]->oddsAmerican,
            $betoffer->outcomes[1]->oddsAmerican
        );

        $date_obj = new DateTime((string) $event->start);
        $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
        $parsed_matchup->setMetaData('event_name', $event->eventInfo[1]->name);
        $parsed_matchup->setCorrelationID($event->id);

        $this->parsed_sport->addParsedMatchup($parsed_matchup);
        return true;
    }

    private function parseProp($event, string $team1_name, string $team2_name, $betoffer): bool
    {
        if (count($betoffer->outcomes) == 2) {
            //Two way prop
            if (
                empty($betoffer->outcomes[0]->label) ||
                empty($betoffer->outcomes[1]->label) ||
                empty($betoffer->outcomes[0]->oddsAmerican) ||
                empty($betoffer->outcomes[1]->oddsAmerican)
            ) {
                return false;
            }

            $parsed_prop = new ParsedProp(
                $team1_name . ' vs. ' . $team2_name . ' :: ' . $betoffer->betOfferType . ' ' . (isset($betoffer->participant) ? $betoffer->participant . ' ' : '') . $betoffer->betDescription . ' : ' . $betoffer->outcomes[0]->label,
                $team1_name . ' vs. ' . $team2_name . ' :: ' . $betoffer->betOfferType . ' ' . (isset($betoffer->participant) ? $betoffer->participant . ' ' : '') . $betoffer->betDescription . ' : ' . $betoffer->outcomes[1]->label,
                $betoffer->outcomes[0]->oddsAmerican,
                $betoffer->outcomes[1]->oddsAmerican
            );
            $parsed_prop->setCorrelationID($event->id);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        } else if ($betoffer->betDescription == 'Moneyline (3-way)') {
            //Three way, parse draw as prop
            if (
                !empty($betoffer->outcomes[1]->label)
                && !empty($betoffer->outcomes[1]->oddsAmerican)
                && $betoffer->outcomes[1]->label == 'X'
            ) {
                $parsed_prop = new ParsedProp(
                    $team1_name . ' vs. ' . $team2_name . ' :: FIGHT IS A DRAW',
                    '',
                    $betoffer->outcomes[1]->oddsAmerican,
                    '-99999'
                );
                $parsed_prop->setCorrelationID($event->id);
                $this->parsed_sport->addFetchedProp($parsed_prop);
            }
        } else {
            //Single line prop
            foreach ($betoffer->outcomes as $outcome) {
                if (
                    !empty($outcome->label) &&
                    !empty($outcome->oddsAmerican)
                ) {
                    //Find lastname, firstname occurences in props and convert o firstname lastname
                    $new_label = $outcome->label;
                    $parts = explode(' - ', $outcome->label);
                    if (count($parts) > 1) {
                        //Convert names from lastname, firstname to firstname lastname
                        $new_label = ParseTools::convertCommaNameToFullName($parts[0]) . ' - ' . $parts[1];
                    } else {
                        $parts = explode(' by ', $outcome->label);
                        if (count($parts) > 1) {
                            //Convert names from lastname, firstname to firstname lastname
                            $new_label = ParseTools::convertCommaNameToFullName($parts[0]) . ' by ' . $parts[1];
                        }
                    }

                    $parsed_prop = new ParsedProp(
                        $team1_name . ' vs. ' . $team2_name . ' :: ' . $betoffer->betOfferType . ' ' . (isset($betoffer->participant) ? $betoffer->participant . ' ' : '') . $betoffer->betDescription . ' : ' . $new_label,
                        '',
                        $outcome->oddsAmerican,
                        '-99999'
                    );
                    $parsed_prop->setCorrelationID($event->id);
                    $this->parsed_sport->addFetchedProp($parsed_prop);
                } else {
                    $this->logger->warning('Potentially unhandled prop: ' . $betoffer->betOfferType . ' ' . $betoffer->betDescription);
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
