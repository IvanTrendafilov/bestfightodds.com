<?php
/**
 * XML Parser
 *
 * Bookie: Pinnacle
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes (only totals)
 *
 * URL: https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/6/197047
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'pinnacle');
define('BOOKIE_ID', '9');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "pinnacle.xml");
            $content['mock'] = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'pinnacle.xml');
        }
        else
        {
            //1. Boxing
            $urls = ['https://www.pinnacle.com/webapi/1.17/api/v1/GuestLines/Deadball/6/197047'];
            ParseTools::retrieveMultiplePagesFromURLs($urls);
            foreach ($urls as $url)
            {
                $this->logger->info("Fetching matchups through URL: " . $url);
                $content[$url] = ParseTools::getStoredContentForURL($url);
            }
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

    public function parseContent($content)
    {
        $parsed_sport = new ParsedSport('Boxing');
        $failed_once = false;
        foreach ($content as $url => $part)
        {
            $counter = 0;
            $json = json_decode($part, true);
            if (!$json || $part == '') 
            {
                $this->logger->error('Content fail for ' . $url);
                $failed_once = true;
            }

            foreach ($json['Leagues'] as $league);
            {
                foreach ($league['Events'] as $event)
                {
                    if (count($event['Participants']) == 2)
                    {
                        //Regular matchup

                        //Replace anylocation indicator with blank
                        $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][1]['Name']);
                        $event['Participants'][0]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][1]['Name']);
                        $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][0]['Name']);
                        $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][1]['Name']);

                        $oParsedMatchup = new ParsedMatchup(
                            $event['Participants'][0]['Name'],
                            $event['Participants'][1]['Name'],
                            round($event['Participants'][0]['MoneyLine']),
                            round($event['Participants'][1]['MoneyLine'])
                        );
                        $oParsedMatchup->setCorrelationID((string) $event['EventId']);

                        //Add time of matchup as metadata
                        if (isset($event['Cutoff']))
                        {
                            $oGameDate = new DateTime((string) $event['Cutoff']);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        $parsed_sport->addParsedMatchup($oParsedMatchup);
                        $counter++;

                        //Adds over/under if available
                        if (isset($event['Totals']))
                        {
                            $oParsedProp = new ParsedProp(
                                (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Over ' . $event['Totals']['Min'] . ' rounds',
                                (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Under ' . $event['Totals']['Min'] . ' rounds',
                                round($event['Totals']['OverPrice']),
                                round($event['Totals']['UnderPrice'])
                            );
                            $oParsedProp->setCorrelationID((string) $event['EventId']);
                            $parsed_sport->addFetchedProp($oParsedProp);
                        }
                    }
                }
            }

            $this->logger->info('URL ' . $url . ' provided ' . $counter . ' matchups');

        }

        //Declare authorative run if we fill the criteria
        if (!$failed_once && count($parsed_sport->getParsedMatchups()) >= 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        return $parsed_sport;
    }
}

?>