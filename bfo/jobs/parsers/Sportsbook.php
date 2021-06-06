<?php

/**
 * XML Parser
 *
 * Bookie: Sportsbook
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: No
 *
 * Timezone in feed: ET
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use Symfony\Component\DomCrawler\Crawler;

define('BOOKIE_NAME', 'sportsbook');
define('BOOKIE_ID', 4);
define(
    'BOOKIE_URLS',
    [
        'ufc' => 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=92',
        'mma' => 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=122'
    ]
);
define(
    'BOOKIE_MOCKFILES',
    [
        'ufc' => PARSE_MOCKFEEDS_DIR . "sportsbook.xml",
        'mma' => PARSE_MOCKFEEDS_DIR . "sportsbook.xml"
    ]
);


class ParserJob extends ParserJobBase
{
    public function fetchContent(array $content_urls): array
    {
        $content = [];
        foreach ($this->content_urls as $key => $url) {
            $this->logger->info("Fetching " . $key . " matchups through URL: " . $url);
        }
        ParseTools::retrieveMultiplePagesFromURLs($content_urls);
        foreach ($this->content_urls as $key => $url) {
            $content[$key] = ParseTools::getStoredContentForURL($content_urls[$key]);
        }
        return $content;
    }

    public function parseContent(array $content): ParsedSport
    {
        $parsed_sport = new ParsedSport('MMA');
        $timezone = (new DateTime())->setTimezone(new DateTimeZone('America/New_York'))->format('T');
        $failed_once = false;
        foreach ($content as $key => $part) {
            $counter = 0;

            if ($part == '') {
                $this->logger->error('Content fail for ' . $key . '(' . $this->content_urls[$key] . ')');
                $failed_once = true;
            }

            //Clean up HTML
            $part = strip_tags($part);
            $part = str_replace("\r", " ", $part);
            $part = str_replace("\n", " ", $part);
            $part = str_replace("\t", " ", $part);
            $part = str_replace("&nbsp;", " ", $part);
            while (strpos($part, '  ') !== false) {
                $part = str_replace("  ", " ", $part);
            }
            $part = ParseTools::stripForeignChars($part);

            //Match fights in single page
            $fight_regexp = '/(\\d{2}\\/\\d{2}\\/\\d{2}) \\d{1,7} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF ([0-9]{2}:[0-9]{2}) [A-Za-z]{2} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF/';

            $fight_matches = ParseTools::matchBlock($part, $fight_regexp);

            foreach ($fight_matches as $fight) {
                if (
                    OddsTools::checkCorrectOdds(trim((string) $fight[3]))
                    && OddsTools::checkCorrectOdds(trim((string) $fight[6]))
                ) {
                    $date_obj = new DateTime($fight[1] . ' ' . $fight[4] . ' ' . $timezone);
                    $parsed_matchup = new ParsedMatchup(
                        (string) $fight[2],
                        (string) $fight[5],
                        (string) $fight[3],
                        (string) $fight[6]
                    );
                    $parsed_matchup->setMetaData('gametime', (string) $date_obj->getTimestamp());
                    $parsed_sport->addParsedMatchup($parsed_matchup);
                }
                $counter++;
            }

            $this->logger->info('URL ' . $this->content_urls[$key] . ' provided ' . $counter . ' matchups');
        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 5) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }

    public function parseContentNew($content)
    {
        $parsed_sport = new ParsedSport('MMA');
        $timezone = (new DateTime())->setTimezone(new DateTimeZone('America/New_York'))->format('T');
        $failed_once = false;
        foreach ($content as $key => $part) {
            $counter = 0;

            if ($part == '') {
                $this->logger->error('Content fail for ' . $key);
                $failed_once = true;
            }

            $crawler = new Crawler($part);

            $crawler->filter('#betOdds > tr')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$parsed_sport) {
                $event = $event_node->filter('td.oddsTitle');
                echo $event->text();
            });
            exit;


            //Clean up HTML
            $part = strip_tags($part);
            $part = str_replace("\r", " ", $part);
            $part = str_replace("\n", " ", $part);
            $part = str_replace("\t", " ", $part);
            $part = str_replace("&nbsp;", " ", $part);
            while (strpos($part, '  ') !== false) {
                $part = str_replace("  ", " ", $part);
            }
            $part = ParseTools::stripForeignChars($part);

            //Match fights in single page
            $fight_regexp = '/(\\d{2}\\/\\d{2}\\/\\d{2}) \\d{1,7} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF ([0-9]{2}:[0-9]{2}) [A-Za-z]{2} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF/';

            $fight_matches = ParseTools::matchBlock($part, $fight_regexp);

            foreach ($fight_matches as $fight) {
                if (
                    OddsTools::checkCorrectOdds(trim((string) $fight[3]))
                    && OddsTools::checkCorrectOdds(trim((string) $fight[6]))
                ) {
                    $date_obj = new DateTime($fight[1] . ' ' . $fight[4] . ' ' . $timezone);
                    $parsed_matchup = new ParsedMatchup(
                        (string) $fight[2],
                        (string) $fight[5],
                        (string) $fight[3],
                        (string) $fight[6]
                    );
                    $parsed_matchup->setMetaData('gametime', (string) $date_obj->getTimestamp());
                    $parsed_sport->addParsedMatchup($parsed_matchup);
                }
                $counter++;
            }

            $this->logger->info('URL ' . $this->content_urls[$key] . ' provided ' . $counter . ' matchups');
        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 5) {
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
