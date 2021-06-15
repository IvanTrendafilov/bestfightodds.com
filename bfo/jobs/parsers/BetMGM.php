<?php

/**
 * Bookie: BetMGM
 * Sport: MMA
 *
 * Timezone: TBD
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\General\BookieHandler;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Jobs\ParserJobBase;

define('BOOKIE_NAME', 'betmgm');
define('BOOKIE_ID', 23);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsapi.nj.betmgm.com/']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "betmgm.xml"]
);

class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $headers = [
        'Bwin-AccessId' => 'NDM0MjhhNmQtYzE2Yi00NjNmLWJlNWQtZmJlZGUxYTIxOTAw',
        'Bwin-AccessIdToken' => 'vqk2p2sXoyxx/BQIHyFTGg=='];
        
        $this->logger->info("Fetching matchups through URL: " . $content_urls['all']);
        return ['all' => ParseTools::retrievePageFromURL($content_urls['all'])];
    }

    public function parseContent(array $content): ParsedSport
    {
        $xml = simplexml_load_string($content['all']);
        if (!$xml) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('MMA');

        $oParsedMatchup = new ParsedMatchup(
            (string) $event_node->HomeTeamID,
            (string) $event_node->VisitorTeamID,
            (string) $event_node->HomeMoneyLine,
            (string) $event_node->VisitorMoneyLine
        );

        $prop = new ParsedProp(
            (string) ':: ' . $event_node->Header . ' : ' . $event_node->VisitorTeamID,
            (string) ':: ' . $event_node->Header . ' : ' . $event_node->HomeTeamID,
            (string) $event_node->VisitorMoneyLine,
            (string) $event_node->HomeMoneyLine
        );

        //Declare full run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10 && $parsed_sport->getPropCount() > 10 && $this->change_num == -1) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
