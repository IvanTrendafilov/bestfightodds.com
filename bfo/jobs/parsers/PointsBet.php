<?php

/**
 * Bookie: PointsBet
 * Sport: MMA
 *
 * Timezone: Unknown. Maybe NJ
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
use BFO\Utils\OddsTools;

define('BOOKIE_NAME', 'pointsbet');
define('BOOKIE_ID', 27);
define(
    'BOOKIE_URLS',
    ['all' => 'https://api-usa.pointsbet.com/api/v2/sports/mma/competitions']
);
define(
    'BOOKIE_MOCKFILES',
    ['UFC' => PARSE_MOCKFEEDS_DIR . "pointsbet.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $groups_url = 'http://api.unicdn.net/v1/feeds/sportsbookv2/groups.json?app_id=9f76dee0&app_key=ca4dc0226dcfcf031277321237e421e8';

        $groups_content = ParseTools::retrievePageFromURL($content_urls['all']);
        $json = json_decode($groups_content);
        if (!$json || !isset($json->locales?->competitions)) {
            $this->logger->error('Unable to parse json' . substr($groups_content, 0, 20) . '..');
            return ['all' => null];
        }

        $urls = [];
        foreach ($json->locales->competitions as $competition) {
            $urls[$competition->name] = 'https://api-usa.pointsbet.com/api/v2/competitions/' . $competition->key . '/events/featured?includeLive=false';
        }

        foreach ($urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        ParseTools::retrieveMultiplePagesFromURLs($urls);
        foreach ($urls as $key => $url) {
            $content[$key] = ParseTools::getStoredContentForURL($urls[$key]);
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport();
        $error_once = false;

        //Process each league
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

    private function parseCompetition(string $league_name, string $json_content): bool
    {
        $json = json_decode($json_content);

        //Error checking
        if (!$json) {
            $this->logger->error("Unable to parse proper json for " . $league_name . '. Contents: ' . substr($json_content, 0, 20) . '...');
            return false;
        }
        if (!$json->name) {
            $this->logger->error("No specified name in competition. Probably failure in fetching page");
            return false;
        }
        $this->logger->info("Processing league " . $json->name);

        foreach ($json->events as $event) {
            $this->parseEvent($event);
        }

        exit;

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

    private function parseEvent($event, $market)
    {
        foreach ($event->fixedOddsMarkets as $markets) {
            if ($market->eventName == "Fight Result") {
                //Regular matchup
                $this->parseMatchup($event, $market);
            } else {
                //
            }
        }
    }


    private function parseMatchup($event, $market)
    {
        if (
            isset($market->outcomes)
            && count($market->outcomes) == 2
            && !empty($market->outcomes[0]->name)
            && !empty($market->outcomes[1]->name)
            && !empty($market->outcomes[0]->price)
            && !empty($market->outcomes[1]->price)
            && !empty($market->advertisedStartTime)
        ) {

            $parsed_matchup = new ParsedMatchup(
                $market->outcomes[0]->name,
                $market->outcomes[1]->name,
                OddsTools::convertDecimalToMoneyline($market->outcomes[0]->price),
                OddsTools::convertDecimalToMoneyline($market->outcomes[1]->price)
            );

            $date_obj = new DateTime((string) $market->advertisedStartTime);
            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            $parsed_matchup->setMetaData('event_name', $event->path[count($event->path) - 1]->name);
            $parsed_matchup->setCorrelationID($event->id);

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }

    private function parseBetOffer($betoffer, array &$stored_events)
    {
        $event = $stored_events[$betoffer->eventId];

        if (isset(
            $event->homeName,
            $event->start
        )) {
            if ($betoffer->criterion->label == "Draw No Bet - Bout Odds" && $betoffer->betOfferType->name == 'Match') {
                $this->parseMatchup($betoffer, $event);
            } else {
                $this->parseProp($betoffer, $event);
            }
        }
    }

    private function parseProp($betoffer, $event): void
    {
        if (count($betoffer->outcomes) == 2) {
            //Two way prop
            if (
                !empty($betoffer->outcomes[0]->label) &&
                !empty($betoffer->outcomes[1]->label) &&
                !empty($betoffer->outcomes[0]->oddsAmerican) &&
                !empty($betoffer->outcomes[1]->oddsAmerican)
            ) {

                //Convert names from lastname, firstname to firstname lastname
                $label1 = ParseTools::convertCommaNameToFullName($betoffer->outcomes[0]->label);
                $label2 = ParseTools::convertCommaNameToFullName($betoffer->outcomes[1]->label);

                $parsed_prop = new ParsedProp(
                    $event->name . ' :: ' . $betoffer->criterion->label . ' : ' . $label1,
                    $event->name . ' :: ' . $betoffer->criterion->label . ' : ' . $label2,
                    $betoffer->outcomes[0]->oddsAmerican,
                    $betoffer->outcomes[1]->oddsAmerican
                );
                $parsed_prop->setCorrelationID($event->id);
                $this->parsed_sport->addFetchedProp($parsed_prop);
            }
        } else if ($betoffer->criterion->label == 'Bout Odds') {
            //Three way, parse draw as prop
            if (
                !empty($betoffer->outcomes[1]->label)
                && !empty($betoffer->outcomes[1]->oddsAmerican)
                && $betoffer->outcomes[1]->label == 'X'
            ) {
                //Convert names from lastname, firstname to firstname lastname
                $team1_name = ParseTools::convertCommaNameToFullName($event->homeName);
                $team2_name = ParseTools::convertCommaNameToFullName($event->awayName);
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
                    //Convert names from lastname, firstname to firstname lastname
                    $label = ParseTools::convertCommaNameToFullName($outcome->label);

                    $parsed_prop = new ParsedProp(
                        $event->name . ' :: ' . $betoffer->criterion->label . ' : ' . $label,
                        '',
                        $outcome->oddsAmerican,
                        '-99999'
                    );
                    $parsed_prop->setCorrelationID($event->id);
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
