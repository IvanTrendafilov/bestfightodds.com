<?php

/**
 * Bookie: Unibet
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

define('BOOKIE_NAME', 'unibet');
define('BOOKIE_ID', 26);
define(
    'BOOKIE_URLS',
    ['all' => 'http://api.unicdn.net/v1/feeds/sportsbookv2/betoffer/group/<GROUP_ID>.json?app_id=9f76dee0&app_key=ca4dc0226dcfcf031277321237e421e8&site=nj.unibet.com&excludeLive=true&outComeSortBy=lexical&outComeSortDir=desc']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "unibet.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $groups_url = 'http://api.unicdn.net/v1/feeds/sportsbookv2/groups.json?app_id=9f76dee0&app_key=ca4dc0226dcfcf031277321237e421e8';

        $groups_content = ParseTools::retrievePageFromURL($groups_url);
        $json = json_decode($groups_content);
        $group_id = null;
        foreach ($json->group->groups as $group) {
            if ($group->sport == 'BOXING') {
                $group_id = $group->id;
            }
        }
        if (!$group_id) {
            $this->logger->error('Unable to fetch BOXING group ID from ' . $groups_url);
            return ['all' => null];
        }

        $content_urls['all'] = str_replace('<GROUP_ID>', $group_id, $content_urls['all']);
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
        if (isset($json->error)) {
            //Other error occurred
            $this->logger->error("Unknown error: " . $json->error->message);
            return $this->parsed_sport;
        }


        //Loop through and store event references
        $stored_events = [];
        foreach ($json->events as $event) {
            if ($event->sport == 'BOXING') {
                $stored_events[$event->id] = $event;
            }
        }

        //Loop through betoffers and parse these
        foreach ($json->betOffers as $betoffer) {
            $this->parseBetOffer($betoffer, $stored_events);
        }

        //Declare full run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) > 3) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }


    private function parseBetOffer($betoffer, array &$stored_events)
    {
        $event = $stored_events[$betoffer->eventId] ?? null;

        if (isset(
            $event,
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

    private function parseMatchup($betoffer, $event): void
    {
        if (
            !empty($event->homeName) &&
            !empty($event->awayName) &&
            !empty($betoffer->outcomes[0]->oddsAmerican) &&
            !empty($betoffer->outcomes[1]->oddsAmerican) &&
            !empty($event->start)
        ) {
            //Convert names from lastname, firstname to firstname lastname
            $team1_name = ParseTools::convertCommaNameToFullName($event->homeName);
            $team2_name = ParseTools::convertCommaNameToFullName($event->awayName);

            $parsed_matchup = new ParsedMatchup(
                $team1_name,
                $team2_name,
                $betoffer->outcomes[0]->oddsAmerican,
                $betoffer->outcomes[1]->oddsAmerican
            );

            $date_obj = new DateTime((string) $event->start);
            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            $parsed_matchup->setMetaData('event_name', $event->path[count($event->path) - 1]->name);
            $parsed_matchup->setCorrelationID($event->id);

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }

    private function parseProp($betoffer, $event): void
    {
        //Convert names from lastname, firstname to firstname lastname in event name
        $event_name_adjusted = $event->name;
        $parts = explode(' - ', $event->name);
        if (count($parts) > 1) {
            $event_name_adjusted = ParseTools::convertCommaNameToFullName($parts[0]) . ' - ' . ParseTools::convertCommaNameToFullName($parts[1]);
        }

        if (count($betoffer->outcomes) == 2 &&
            in_array($betoffer->betOfferType->name, ["Yes/No", "Over/Under", "Head to Head"])) {
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
                    $event_name_adjusted . ' :: ' . (isset($betoffer->outcomes[0]->participant) ? ParseTools::convertCommaNameToFullName($betoffer->outcomes[0]->participant) . ' ' : '') 
                        . $betoffer->criterion->label . (isset($betoffer->outcomes[0]->line) ? ' ' . $betoffer->outcomes[0]->line : '') . ' : ' . $label1,
                    $event_name_adjusted . ' :: ' . (isset($betoffer->outcomes[1]->participant) ? ParseTools::convertCommaNameToFullName($betoffer->outcomes[1]->participant) . ' ' : '') 
                        . $betoffer->criterion->label . (isset($betoffer->outcomes[1]->line) ? ' ' . $betoffer->outcomes[1]->line : '') . ' : ' . $label2,
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
                        $event_name_adjusted . ' :: ' . (isset($outcome->participant) ? ParseTools::convertCommaNameToFullName($outcome->participant) . ' ' : '') 
                            . $betoffer->criterion->label . (isset($outcome->line) ? ' ' . $outcome->line : '') . ' : ' . $new_label,
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
