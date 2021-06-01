<?php

/**
 * XML Parser
 *
 * Bookie: DraftKings
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: No
 * Authoritative run: Yes
 *
 * Comment: Dev version
 * 
 * URL: https://sportsbook.draftkings.com/sports/mma
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

define('BOOKIE_NAME', 'draftkings');
define('BOOKIE_ID', 22);
define(
    'BOOKIE_URLS',
    ['all' => 'https://sportsbook.draftkings.com/sports/mma']
);
define(
    'BOOKIE_MOCKFILES',
    ['all' => PARSE_MOCKFEEDS_DIR . "draftkings.xml"]
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
                '--blink-settings=imagesEnabled=false,scriptEnabled=false'
            ], ['port' => intval('95' . BOOKIE_ID)]);
            $client->request('GET', BOOKIE_URLS['all']);

            $matchups = [];

            $crawler = $client->waitFor('.league-link__link');

            $tab_count = $crawler->filter('a.league-link__link')->count();
            if ($tab_count > 10) {
                $this->logger->error('Unusual amount of tabs, bailing' . $tab_count);
                return $this->parsed_sport;
            }
            for ($x = 0; $x < $tab_count; $x++) {
                $this->logger->debug('Clicking on tab on page');
                $client->executeScript("document.querySelectorAll('a.league-link__link')[" . $x . "].click()");
                $crawler = $client->waitFor('.sportsbook-offer-category-card');
                $crawler->filter('.sportsbook-event-accordion__wrapper')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$client, &$matchups) {
                    //Check for live indicator, if so we skip this entry
                    $live_crawler = $event_node->filter('.sportsbook__icon--live');
                    if ($live_crawler->count() > 0) {
                        $this->logger->info('Live event, will skip');
                    } else if ($event_node->filter('.sportsbook-outcome-body-wrapper')->count() == 2) {
                        $matchup = [];
                        $i = 1;
                        $event_node->filter('.sportsbook-outcome-body-wrapper')->each(function (\Symfony\Component\DomCrawler\Crawler $team_node) use (&$matchup, &$i) {
                            $matchup['team' . $i . '_name'] = $team_node->filter('.sportsbook-outcome-cell__label-line-container')->text();
                            $matchup['team' . $i . '_odds'] = $team_node->filter('.sportsbook-odds')->text();
                            $i++;
                        });

                        //Try future date format first
                        $this->logger->debug('Capturing date');
                        $date = DateTime::createFromFormat('D jS M g:ia', (string) $event_node->filter('.sportsbook-event-accordion__date')->text());
                        if ($date == false) {
                            $this->logger->debug('Falling back to secondary date format');
                            $date = new DateTime((string) $event_node->filter('.sportsbook-event-accordion__date')->text());
                        }
                        $matchup['date'] = $date->getTimestamp();
                        $matchups[] = $matchup;
                    }
                });
                $client->back();
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
