<?php

/**
 * Bookie: Fanduel
 * Sport: MMA
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

define('BOOKIE_NAME', 'fanduel');
define('BOOKIE_ID', 21);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsbook.fanduel.com/cache/psbonav/1/UK/top.json']
);
define(
    'BOOKIE_MOCKFILES',
    ['UFC' => PARSE_MOCKFEEDS_DIR . "fanduel.json"]
);

class ParserJob extends ParserJobBase
{
    private ParsedSport $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        //First we parse the competitions feed that provides the available subtypes for the sport
        $groups_content = ParseTools::retrievePageFromURL($content_urls['all']);
        $json = json_decode($groups_content);
        if (!$json || !isset($json->bonavigationnodes)) {
            $this->logger->error('Unable to parse json' . substr($groups_content, 0, 20) . '..');
            return ['all' => null];
        }
        $urls = [];
        //Fanduel structure forces us to create this foreach pyramid
        foreach ($json->bonavigationnodes as $nav_node) {
            if ($nav_node->name == 'MMA') {
                foreach ($nav_node->bonavigationnodes as $sport_nodes) {
                    foreach ($sport_nodes->bonavigationnodes as $sub_node) {
                        foreach ($sub_node->bonavigationnodes as $third_node) {
                            if (isset($third_node->marketgroups) && count($third_node->marketgroups) > 0) {
                                foreach ($third_node->marketgroups as $market_group) {
                                    $urls[$third_node->name] = 'https://sportsbook.fanduel.com/cache/psmg/UK/' . $market_group->idfwmarketgroup . '.json';
                                }
                            }
                        }
                    }
                }
            }
        }
        //With the subtypes gathered, fetch the content for each
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
            $this->logger->error("Unable to parse proper json for " . $competition_name . '. Contents: ' . substr($json_content, 0, 20) . '...');
            return false;
        }
        if (!$json->events) {
            $this->logger->error("No specified sport name in market. Probably failure in fetching page");
            return false;
        }
        $this->logger->info("Processing competition " . $competition_name);

        if ($competition_name != 'Marquee Fights') {
            $parts = explode(' ', $competition_name);
            $competition_name = $parts[0];
        }

        foreach ($json->events as $event) {
            $this->parseEvent($competition_name, $event);
        }

        return true;
    }

    private function parseEvent(string $competition_name, $event): void
    {
        foreach ($event->markets as $market) {
            if ($market->name == "Moneyline") {
                //Regular matchup
                $this->parseMatchup($competition_name, $event, $market);
            } else {
                //Prop - When supported by sportsbook
            }
        }
    }
   
    private function parseMatchup(string $competition_name, $event, $market): void
    {

        if (
            isset($market->selections)
            && count($market->selections) == 2
            && !empty($market->selections[0]->name)
            && !empty($market->selections[1]->name)
            && !empty($market->selections[0]->price)
            && !empty($market->selections[1]->price)
            && !empty($event->tsstart)
        ) {
            //Skip live events
            $date_obj = new DateTime((string) $event->tsstart, new DateTimeZone('America/New_York'));
            if ($date_obj <= new DateTime()) {
                $this->logger->info("Skipping live odds for " . $market->selections[0]->name . " vs " . $market->selections[1]->name);
                return;
            }

            $parsed_matchup = new ParsedMatchup(
                $market->selections[0]->name,
                $market->selections[1]->name,
                OddsTools::convertDecimalToMoneyline($market->selections[0]->price),
                OddsTools::convertDecimalToMoneyline($market->selections[1]->price)
            );
           
            $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
            $parsed_matchup->setMetaData('event_name', $competition_name);

            $parsed_matchup->setCorrelationID((string) $event->idfoevent);

            $this->parsed_sport->addParsedMatchup($parsed_matchup);
        }
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
