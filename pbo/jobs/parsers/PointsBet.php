<?php

/**
 * Bookie: PointsBet
 * Sport: Boxing
 *
 * Timezone: ES, converted to UTC
 * 
 * Notes: Can be run in dev/test towards actual URLs (not using mock).
 *        Props not available from sportsbook at time of creation
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
    ['all' => 'https://api-usa.pointsbet.com/api/v2/sports/boxing/competitions']
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
        //First we parse the competitions feed that provides the available subtypes for the sport
        $groups_content = ParseTools::retrievePageFromURL($content_urls['all']);
        $json = json_decode($groups_content);
        if (!$json || !isset($json->locales)) {
            $this->logger->error('Unable to parse json' . substr($groups_content, 0, 100) . '..');
            return [];
        }
        $urls = [];
        foreach ($json->locales as $locale) {
            foreach ($locale->competitions as $competition) {
                $urls[$competition->name] = 'https://api-usa.pointsbet.com/api/v2/competitions/' . $competition->key . '/events/featured?includeLive=false';
            }
        }

        //With the subtypes gathered, fetch the content for each
        foreach ($urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        ParseTools::retrieveMultiplePagesFromURLs($urls);
        $content = [];
        foreach ($urls as $key => $url) {
            $content[$key] = ParseTools::getStoredContentForURL($urls[$key]);
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

    private function parseCompetition(string $league_name, ?string $json_content): bool
    {
        $json = json_decode($json_content);

        //Error checking
        if (!$json) {
            $this->logger->error("Unable to parse proper json for " . $league_name . '. Contents: ' . substr($json_content, 0, 100) . '...');
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

        return true;
    }

    private function parseEvent($event)
    {
        foreach ($event->fixedOddsMarkets as $market) {
            if ($market->eventName == "Fight Result (Draw No Bet)") {
                //Regular matchup
                $this->parseMatchup($event, $market);
            } else {
                //Prop - When supported by sportsbook
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

            $date_obj = new DateTime((string) $market->advertisedStartTime, new DateTimeZone('America/New_York'));
            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            $parsed_matchup->setMetaData('event_name', $event->competitionMetaData->featuredName);

            $parsed_matchup->setCorrelationID($event->key);

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
