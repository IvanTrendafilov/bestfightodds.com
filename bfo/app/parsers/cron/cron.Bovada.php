<?php
/**
 * XML Parser
 *
 * Bookie: Bovada
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 *
 * URL: http://sportsfeeds.bovada.lv/v1/feed?clientId=1953464&categoryCodes=1201&language=en
 *
 */

require_once __DIR__ . "/../../../bootstrap.php";
require_once __DIR__ . "/../../../config/class.Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'bovada');
define('BOOKIE_ID', '5');

$options = getopt("", ["mode::"]);

$logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::INFO, ['filename' => 'cron.' . BOOKIE_NAME . '.' . time() . '.log']);
$parser = new ParserJob($logger);
$parser->run($options['mode'] ?? '');

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
        $this->logger->info('Started parser');

        $content = null;
        if ($mode == 'mock')
        {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "bovada.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'bovada.xml');
        }
        else
        {
            $matchups_url = 'http://sportsfeeds.bovada.lv/v1/feed?clientId=1953464&categoryCodes=1201&language=en';
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try 
        {
            $op = new OddsProcessor($this->logger, BOOKIE_ID, new Ruleset());
            $op->processParsedSport($parsed_sport, $this->full_run);
        }
        catch (Exception $e)
        {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    public function parseContent($json)
    {
        $feed = json_decode($json, true);
        $parsed_sports = array();
        $parsed_sport = new ParsedSport('MMA');

        foreach ($feed['events'] as $event)
        {
            //Store metadata and correlation ID
            $correlation_id = $event['id'];

            $oGameDate = new DateTime($event['startTime']);
            $date = $oGameDate->getTimestamp();

            //Get name from category
            $event_name = $this->getEventFromCategories($event);

            foreach ($event['markets'] as $market)
            {
                if ($market['status'] == 'OPEN' && $event_name != 'Kickboxing K-1')
                {
                    if ($market['description'] == 'Fight Winner')
                    {
                        //Regular matchup
                        $parsed_matchup = new ParsedMatchup(
                                        $market['outcomes'][0]['description'],
                                        $market['outcomes'][1]['description'],
                                        $market['outcomes'][0]['price']['american'],
                                        $market['outcomes'][1]['price']['american']);
                        $parsed_matchup->setCorrelationID($correlation_id);
                        $parsed_matchup->setMetaData('event_name', (string) $event_name);
                        
                        $parsed_matchup->setMetaData('gametime', (string) $date);
                        $parsed_sport->addParsedMatchup($parsed_matchup);
                    }
                    else
                    {
                        //Prop bet
                        if (count($market['outcomes']) > 2)
                        {
                            //Single line prop
                            foreach ($market['outcomes'] as $outcome)
                            {
                                $parsed_prop = new ParsedProp(
                                    $market['description'] . ' :: ' . $outcome['description'],
                                    '',
                                    $outcome['price']['american'],
                                    '-99999');
                                $parsed_prop->setCorrelationID($correlation_id);
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        }
                        else
                        {
                            //Two sided prop
                            $parsed_prop = new ParsedProp(
                                $market['description'] . ' :: ' . $market['outcomes'][0]['description'],
                                $market['description'] . ' :: ' . $market['outcomes'][1]['description'],
                                $market['outcomes'][0]['price']['american'],
                                $market['outcomes'][1]['price']['american']);
                            $parsed_prop->setCorrelationID($correlation_id);
                            $parsed_sport->addFetchedProp($parsed_prop);
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $parsed_sport;
    }

    private function getEventFromCategories($node)
    {
        //Loops through all categories child elements and picks out the one with the longest ID (most specific event)
        $found_desc = '';
        $largest = 0;
        foreach ($node['categories'] as $category)
        {
            if (intval($category['code']) > $largest)
            {
                $largest = intval($category['code']);
                $found_desc = $category['description'];
            }
        }
        return $found_desc;
    }
}

?>