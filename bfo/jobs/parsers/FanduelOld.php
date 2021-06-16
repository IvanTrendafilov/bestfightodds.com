<?php

/**
 * XML Parser
 *
 * Bookie: Fanduel
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: No
 * Authoritative run: No
 *
 * Comment: Note: Does not support mockfeeds at the moment
 * 
 * URL: https://sportsbook.fanduel.com/sports/navigation/7287.1
 *
 * Timezone in feed: GMT -4 (New York)
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Jobs\ParserJobBase;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;

use Symfony\Component\Panther\Client;
use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'fanduel');
define('BOOKIE_ID', 21);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsbook.fanduel.com/sports/navigation/7287.1/9886.3']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "fanduel.xml"]
);

class ParserJob extends ParserJobBase
{
    private $parsed_sport;

    public function fetchContent(array $content_urls): array
    {
        $this->logger->info("Fetching matchups through URL: " . BOOKIE_URLS['all']);
        //Actualy fetching is performed in parseContent due to Panther complexity
        return ['all' => ''];
    }

    public function parseContent(array $source): ParsedSport
    {
        //$source is essentially ignored here since we will be using panther to simulate the browsing

        $this->parsed_sport = new ParsedSport('MMA');
        $matchups = [];
        try {
            $client = Client::createChromeClient(null, [
                '--no-sandbox',
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36',
                '--window-size=1200,1100',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--headless',
                '--no-zygote',
                '--single-process',
                '--disable-gpu',
                '--blink-settings=imagesEnabled=false'
            ], ['port' => intval('95' . BOOKIE_ID)]);
            $crawler = $client->request('GET', BOOKIE_URLS['all']);

            $crawler = $client->waitFor('.events_futures');

            $tab_count = $crawler->filter('div.events_futures button.btn')->count();
            if ($tab_count > 10) {
                $this->logger->error('Unusual amount of tabs, bailing' . $tab_count);
                return $this->parsed_sport;
            }
            for ($x = 0; $x < $tab_count; $x++) {
                $this->logger->debug('Clicking on tab on page');
                $client->executeScript("document.querySelectorAll('.events_futures button')[" . $x . "].click()");
                $crawler = $client->waitFor('.event');
                $crawler->filter('.event')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$client, &$matchups) {
                    if ($event_node->filter('.MMA')->count()) {
                        $date = new DateTime($event_node->filter('div.time')->text(), new DateTimeZone('America/New_York'));

                        if (strpos(strtoupper($event_node->filter('div.time')->text()), 'LIVE') !== false) {
                            $this->logger->info('Live event, will skip : ' . $date);
                        } else {
                            $matchup = [];
                            $i = 1;
                            $event_node->filter('.selection')->each(function (\Symfony\Component\DomCrawler\Crawler $team_node) use (&$matchup, &$i) {
                                $matchup['team' . $i . '_name'] = $team_node->filter('.selection-name')->text();
                                $matchup['team' . $i . '_odds'] = $team_node->filter('.selectionprice')->text();
                                $i++;
                            });

                            $matchup['date'] = $date->getTimestamp();
                            $matchups[] = $matchup;
                        }
                    }
                });
            }
        } catch (Exception $e) {
            $this->logger->error('Exception when retrieving page contents: ' . $e->getMessage());
        } finally {
            $client->quit();
        }
        $client->quit();

        foreach ($matchups as $matchup) {
            $this->parseMatchup($matchup);
        }

        if (count($this->parsed_sport->getParsedMatchups()) >= 10) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseMatchup($matchup)
    {
        //Check for metadata
        if (!isset($matchup['date'])) {
            $this->logger->warning('Missing metadata (date) for matchup');
            return false;
        }

        //Validate matchup before adding
        if (
            !v::stringVal()->length(5, null)->validate($matchup['team1_name'])
            || !v::stringVal()->length(5, null)->validate($matchup['team2_name'])
            || !v::stringVal()->length(2, null)->validate($matchup['team1_odds'])
            || !v::stringVal()->length(2, null)->validate($matchup['team2_odds'])
            || !OddsTools::checkCorrectOdds((string) $matchup['team1_odds'])
            || !OddsTools::checkCorrectOdds((string) $matchup['team2_odds'])
        ) {
            $this->logger->warning('Invalid matchup fetched: ' . $matchup['team1_name'] . ' ' . $matchup['team1_odds'] . ' / ' . $matchup['team2_name'] . ' ' . $matchup['team2_odds']);
            return false;
        }

        //All ok, add matchup
        $parsed_matchup = new ParsedMatchup(
            ParseTools::formatName($matchup['team1_name']),
            ParseTools::formatName($matchup['team2_name']),
            $matchup['team1_odds'],
            $matchup['team2_odds']
        );
        $parsed_matchup->setMetaData('gametime', $matchup['date']);
        $this->parsed_sport->addParsedMatchup($parsed_matchup);

        return true;
    }
}

$options = getopt("", ["mode::"]);
$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob(BOOKIE_ID, $logger, new RuleSet(), BOOKIE_URLS, BOOKIE_MOCKFILES);
$parser->run($options['mode'] ?? '');
