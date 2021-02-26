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

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

use Symfony\Component\Panther\Client;
use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'draftkings');
define('BOOKIE_ID', '22');

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob($logger);
$parser->run();

class ParserJob
{
    private $full_run = false;
    private $parsed_sport;
    private $logger = null;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run($mode = 'normal')
    {
        $content = null;
        if ($mode == 'mock')
        {
        }

        $parsed_sport = $this->parseContent($content);

        $op = new OddsProcessor($this->logger, BOOKIE_ID);
        $op->processParsedSport($parsed_sport, $this->full_run);

        $this->logger->info('Finished');
    }

    private function parseContent($source)
    {
        $this->parsed_sport = new ParsedSport('MMA');

        try 
        {
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
                '--single-process', // <- this one doesn't works in Windows
                '--disable-gpu',
                '--blink-settings=imagesEnabled=false,scriptEnabled=false'
              ], ['port' => intval('95' . BOOKIE_ID)]);
            $client->request('GET', 'https://sportsbook.draftkings.com/sports/mma');

            $matchups = [];

            $crawler = $client->waitFor('.league-link__link');

            $tab_count = $crawler->filter('a.league-link__link')->count();
            if ($tab_count > 10)
            {
                $this->logger->error('Unusual amount of tabs, bailing' . $tab_count);
                return $this->parsed_sport;
            }
            for ($x = 0; $x < $tab_count; $x++)
            {
                $this->logger->debug('Clicking on tab on page');
                $client->executeScript("document.querySelectorAll('a.league-link__link')[" . $x . "].click()");
                $crawler = $client->waitFor('.sportsbook-offer-category-card');
                $crawler->filter('.sportsbook-event-accordion__wrapper')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$client, &$matchups)
                {
                    //Check for live indicator, if so we skip this entry
                    $live_crawler = $event_node->filter('.sportsbook__icon--live');
                    if ($live_crawler->count() > 0)
                    {
                        $this->logger->info('Live event, will skip');
                    }
                    else if ($event_node->filter('.sportsbook-outcome-body-wrapper')->count() == 2)
                    {
                        $matchup = [];
                        $i = 1;
                        $event_node->filter('.sportsbook-outcome-body-wrapper')->each(function (\Symfony\Component\DomCrawler\Crawler $team_node) use (&$matchup, &$i)
                        {
                            $matchup['team' . $i . '_name'] = $team_node->filter('.sportsbook-outcome-cell__label-line-container')->text();
                            $matchup['team' . $i . '_odds'] = $team_node->filter('.sportsbook-odds')->text();
                            $i++;
                        });

                        //Try future date format first
                        $this->logger->debug('Capturing date');
                        $date = DateTime::createFromFormat('D jS M g:ia', (string) $event_node->filter('.sportsbook-event-accordion__date')->text());
                        if ($date == false)
                        {
                            $this->logger->debug('Falling back to secondary date format');
                            $date = new DateTime((string) $event_node->filter('.sportsbook-event-accordion__date')->text());
                        }
                        $matchup['date'] = $date->getTimestamp();
                        $matchups[] = $matchup;
                    }
                });
                $client->back();
            }
        } 
        catch (Exception $e) 
        {
            $this->logger->error('Exception when retrieving page contents: ' . $e->getMessage());
        } 
        finally {
            $client->quit();
        }

        $client->quit();

        foreach ($matchups as $matchup)
        {
            $this->parseMatchup($matchup);
        }

        if (count($this->parsed_sport->getParsedMatchups()) >= 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $this->parsed_sport;
    }

    private function parseMatchup($matchup)
    {
        //Check for metadata
        if (!isset($matchup['date']))
        {
            $this->logger->warning('Missing metadata (date) for matchup');
            return false;
        }

        //Validate matchup before adding
        if (!v::stringVal()->length(5, null)->validate($matchup['team1_name'])
            || !v::stringVal()->length(5, null)->validate($matchup['team2_name'])
            || !v::stringVal()->length(2, null)->validate($matchup['team1_odds'])
            || !v::stringVal()->length(2, null)->validate($matchup['team2_odds'])
            || !OddsTools::checkCorrectOdds((string) $matchup['team1_odds'])
            || !OddsTools::checkCorrectOdds((string) $matchup['team2_odds']))
            {
                $this->logger->warning('Invalid matchup fetched: ' . $matchup['team1_name'] . ' ' . $matchup['team1_odds'] . ' / ' . $matchup['team2_name'] . ' ' . $matchup['team2_odds']);
                return false;
            }

        //Validate format of participants and odds
        $matchup['team1_name'] = ParseTools::formatName($matchup['team1_name']);
        $matchup['team2_name'] = ParseTools::formatName($matchup['team2_name']);

        //All ok, add matchup
        $parsed_matchup = new ParsedMatchup(
            $matchup['team1_name'],
            $matchup['team2_name'],
            $matchup['team1_odds'],
            $matchup['team2_odds']
        );
        $parsed_matchup->setMetaData('gametime', $matchup['date']);
        $this->parsed_sport->addParsedMatchup($parsed_matchup);

        return true;
    }
}

?>