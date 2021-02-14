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
 * Comment: Dev version
 * 
 * URL: https://sportsbook.fanduel.com/sports/navigation/7287.1
 *
 */

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

use Symfony\Component\Panther\Client;
use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'fanduel');
define('BOOKIE_ID', '21');

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
    }

    private function parseContent($source)
    {
        $this->parsed_sport = new ParsedSport('MMA');

        /*try 
        {*/
            $client = Client::createChromeClient(null, [
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36',
                '--window-size=1200,1100',
                '--headless',
                '--disable-gpu',
            ], ['port' => intval('95' . BOOKIE_ID)]);
            $client->request('GET', 'https://sportsbook.fanduel.com/sports/navigation/7287.1/9886.3');

            $matchups = [];

            $crawler = $client->waitFor('.events_futures');

            $tab_count = $crawler->filter('div.events_futures button.btn')->count();
            if ($tab_count > 10)
            {
                $this->logger->error('Unusual amount of tabs, bailing' . $tab_count);
                return $this->parsed_sport;
            }
            for ($x = 0; $x < $tab_count; $x++)
            {
                $this->logger->debug('Clicking on tab on page');
                $client->executeScript("document.querySelectorAll('.events_futures button')[" . $x . "].click()");
                $crawler = $client->waitFor('.events_futures');
                $crawler->filter('.event')->each(function (\Symfony\Component\DomCrawler\Crawler $event_node) use (&$client, &$matchups)
                {
                    if ($event_node->filter('.MMA')->count())
                    {
                        $matchup = [];
                        $i = 1;
                        $event_node->filter('.selection')->each(function (\Symfony\Component\DomCrawler\Crawler $team_node) use (&$matchup, &$i)
                        {
                            $matchup['team' . $i . '_name'] = $team_node->filter('.selection-name')->text();
                            $matchup['team' . $i . '_odds'] = $team_node->filter('.selectionprice')->text();
                            $i++;
                        });
                        $date = new DateTime($event_node->filter('div.time')->text());
                        $matchup['date'] = $date->getTimestamp();
                        $matchups[] = $matchup;
                    }
                });
            }
        /*} 
        catch (Exception $e) 
        {
            $this->logger->error('Exception when retrieving page contents: ' . $e->getMessage());
        } 
        finally {
            $client->quit();
        }*/

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