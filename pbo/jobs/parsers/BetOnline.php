<?php

/**
 * XML Parser
 *
 * Bookie: BetOnline
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: Yes (Only one as specified)
 * Props: Yes
 * Authoritative run: Yes* (Won't be usable since we are running props in separate parser. The two should be combined into a standalone cron job later)
 *
 * Comment: Prod version
 * 
 * Pregames URL: https://api.linesfeed.info/v1/pregames/lines/pu?sport=Boxing
 * Props URL: https://api.linesfeed.info/v1/contest/lines/pu?sport=Boxing
 * 
 * Timezone in feed: Eastern (NY) 
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'ref');
define('BOOKIE_ID', 12);
define(
    'BOOKIE_URLS',
    [
        'matchups' => 'https://api.linesfeed.info/v1/pregames/lines/pu?sport=Boxing',
        'props' => 'https://api.linesfeed.info/v1/contest/lines/pu?sport=Boxing'
    ]
);
define(
    'BOOKIE_MOCKFILES',
    [
        'matchups' => PARSE_MOCKFEEDS_DIR . 'betonline.json',
        'props' => PARSE_MOCKFEEDS_DIR . 'betonlineprops.json'
    ]
);

class ParserJob extends ParserJobBase
{
    private $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $content = [];
        $this->logger->info("Fetching matchups through URL: " . $content_urls['matchups']);
        $this->logger->info("Fetching props through URL: " . $content_urls['props']);
        ParseTools::retrieveMultiplePagesFromURLs([$content_urls['matchups'], $content_urls['props']]);

        $content['matchups'] = ParseTools::getStoredContentForURL($content_urls['matchups']);
        $content['props'] = ParseTools::getStoredContentForURL($content_urls['props']);;

        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $this->parsed_sport = new ParsedSport('Boxing');

        $this->parseMatchups($content['matchups']);
        $this->parseProps($content['props']);

        $missing_content = false;
        if ($content['matchups'] == '') {
            $this->logger->error('Retrieving matchups failed');
            $missing_content = true;
        }
        if ($content['props'] == '') {
            $this->logger->error('Retrieving props failed');
            $missing_content = true;
        }

        if (!$missing_content && count($this->parsed_sport->getParsedMatchups()) >= 3) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseMatchups($content)
    {
        $json = json_decode($content);
        if ($json == false) {
            $this->logger->error("Unable to decode JSON: " . substr($content, 0, 50) . "...");
            return false;
        }

        foreach ($json->preGameEvents as $matchup) {
            $this->parseMatchup($matchup);
        }
    }

    private function parseMatchup($matchup)
    {
        //Check for metadata
        if (!isset($matchup->gameId, $matchup->event_DateTimeGMT)) {
            $this->logger->warning('Missing metadata (game ID and/or DateTimeGMT) for matchup');
            return false;
        }
        $event_name = $matchup->scheduleText == null ? '' : trim((string) $matchup->scheduleText);

        $gd = new DateTime($matchup->event_DateTimeGMT, new DateTimeZone('America/New_York'));
        $event_timestamp = $gd->getTimestamp();

        //Validate existance participants fields and odds
        if (!isset(
            $matchup->participants[0]?->participantName,
            $matchup->participants[1]?->odds?->moneyLine,
            $matchup->participants[0]?->participantName,
            $matchup->participants[1]?->odds?->moneyLine
        )) {
            $this->logger->warning('Missing participant and odds fields for matchup ' . trim((string) $matchup->gameId) . ' at ' . $event_name);
            return false;
        }

        //Validate format of participants and odds
        $team_1 = ParseTools::formatName((string) $matchup->participants[0]->participantName);
        $team_2 = ParseTools::formatName((string) $matchup->participants[1]->participantName);

        //Change order of naming from Mayweather, Floyd to Floyd Mayweather
        $parts = explode(',', $team_1);
        $team_1 = trim($parts[1] . ' ' . $parts[0]);
        $parts = explode(',', $team_2);
        $team_2 = trim($parts[1] . ' ' . $parts[0]);

        if (
            !OddsTools::checkCorrectOdds((string) $matchup->participants[0]->odds->moneyLine)
            || !OddsTools::checkCorrectOdds((string) $matchup->participants[1]->odds->moneyLine)
            || empty($team_1)
            || empty($team_2)
        ) {
            $this->logger->warning('Invalid formatting for participant and odds fields for matchup ' . trim((string) $matchup->gameId) . ' at ' . $event_name);
            return false;
        }

        //Logic to determine correlation ID: We check who is home and visiting team and construct this into a string that matches what can be found in the prop parser:
        $corr_index = trim((string) strtolower($matchup->participants[0]->visitingHomeDraw)) == 'home' ? 0 : 1;
        $correlation_id = strtolower($matchup->participants[$corr_index]->participantName . ' vs ' . $matchup->participants[!$corr_index]->participantName);

        $parsed_matchup = new ParsedMatchup(
            $team_1,
            $team_2,
            (string) $matchup->participants[0]->odds->moneyLine,
            (string) $matchup->participants[1]->odds->moneyLine
        );
        if (!empty($event_name)) {
            $parsed_matchup->setMetaData('event_name', $event_name);
        }
        $parsed_matchup->setMetaData('gametime', $event_timestamp);
        $parsed_matchup->setCorrelationID($correlation_id);
        $this->parsed_sport->addParsedMatchup($parsed_matchup);

        //If existant, also add total rounds (e.g. over/under 4.5 rounds)
        if (
            @!empty($matchup->period->total->totalPoints) && @!empty($matchup->period->total->overAdjust) && @!empty($matchup->period->total->underAdjust)
            && OddsTools::checkCorrectOdds((string) $matchup->period->total->overAdjust) && OddsTools::checkCorrectOdds((string) $matchup->period->total->underAdjust)
        ) {
            $parsed_prop = new ParsedProp(
                $team_1 . ' VS ' . $team_2 . ' OVER ' . $matchup->period->total->totalPoints . ' ROUNDS',
                $team_1 . ' VS ' . $team_2 . ' UNDER ' . $matchup->period->total->totalPoints . ' ROUNDS',
                (string) $matchup->period->total->overAdjust,
                (string) $matchup->period->total->underAdjust
            );
            $parsed_prop->setCorrelationID($correlation_id);
            $this->parsed_sport->addFetchedProp($parsed_prop);
        }

        return true;
    }

    private function parseProps($content)
    {
        $json = json_decode($content);
        if ($json == false) {
            $this->logger->error("Unable to decode JSON: " . substr($content, 0, 50) . "...");
            return false;
        }

        foreach ($json->events as $prop) {
            if (trim((string) $prop->sport) == "Boxing Props") {
                $this->parseProp($prop);
            }
        }
    }

    private function parseProp($prop)
    {
        $correlation_id = trim(strtolower((string) $prop->league));

        //Convert names from lastname, firstname to firstname lastname in league name
        $event_name_adjusted = $prop->league;
        $parts = explode(' vs ', $prop->league);
        if (count($parts) > 1) {
            $event_name_adjusted = ParseTools::convertCommaNameToFullName($parts[0]) . ' VS ' . ParseTools::convertCommaNameToFullName($parts[1]);
        }

        if (
            count($prop->participants) == 2
            && (trim(strtolower((string) $prop->participants[0]->name)) == 'yes' && trim(strtolower((string) $prop->participants[1]->name)) == 'no')
            || (trim(strtolower((string) $prop->participants[0]->name)) == 'no' && trim(strtolower((string) $prop->participants[1]->name)) == 'yes')
        ) {
            //Validate existance participants fields and odds
            if (
                !isset(
                    $prop->participants[0]?->name,
                    $prop->participants[0]?->odds?->moneyLine,
                    $prop->participants[1]?->name,
                    $prop->participants[1]?->odds?->moneyLine
                )
                || !OddsTools::checkCorrectOdds($prop->participants[0]->odds->moneyLine)
                || !OddsTools::checkCorrectOdds($prop->participants[1]->odds->moneyLine)
            ) {
                $this->logger->warning('Missing/invalid options and odds fields for prop ' . trim((string) $prop->description) . ' at ' . $prop->league);
                return false;
            }

            //Two way prop
            $prop_obj = new ParsedProp(
                trim($event_name_adjusted) . ' : ' . trim((string) $prop->description) . ' - ' . trim((string) $prop->participants[0]->name),
                trim($event_name_adjusted) . ' : ' . trim((string) $prop->description) . ' - ' . trim((string) $prop->participants[1]->name),
                trim((string) $prop->participants[0]->odds->moneyLine),
                trim((string) $prop->participants[1]->odds->moneyLine)
            );
            $prop_obj->setCorrelationID($correlation_id);
            $this->parsed_sport->addFetchedProp($prop_obj);
        } else {
            //Multiple one way props
            foreach ($prop->participants as $prop_line) {
                //Validate existance participants fields and odds
                if (
                    !isset(
                        $prop_line?->name,
                        $prop_line?->odds?->moneyLine
                    )
                    || !OddsTools::checkCorrectOdds($prop_line->odds->moneyLine)
                ) {
                    $this->logger->warning('Missing/invalid options and odds fields for prop ' . trim((string) $prop->description) . ' at ' . $prop->league);
                } else {

                    //Find lastname, firstname occurences in props and convert o firstname lastname
                    $new_label = $prop_line->name;
                    $parts = explode(' by ', $prop_line->name);
                    if (count($parts) > 1) {
                        //Convert names from lastname, firstname to firstname lastname
                        $new_label = ParseTools::convertCommaNameToFullName($parts[0]) . ' by ' . $parts[1];
                    }


                    $prop_obj = new ParsedProp(
                        trim($event_name_adjusted) . ' : ' . trim((string) $prop->description) . ' - ' . trim($new_label),
                        '',
                        trim((string) $prop_line->odds->moneyLine),
                        '-99999'
                    );
                    $prop_obj->setCorrelationID($correlation_id);
                    $this->parsed_sport->addFetchedProp($prop_obj);
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
