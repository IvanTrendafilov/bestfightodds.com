<?php
/**
 * XML Parser
 *
 * Bookie: William Hill
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://pricefeeds.williamhill.com/oxipubserver?action=template&template=getHierarchyByMarketType&classId=402&filterBIR=N
 *
 * Timezone in feed: UTC+1 so assuming Europe/London (during DST, maybe needs to be adjusted once off DST)
 * 
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\General\BookieHandler;
use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'williamhill');
define('BOOKIE_ID', '17');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "williamhill.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'williamhill.xml');
        }
        else
        {
            $matchups_url = 'http://pricefeeds.williamhill.com/oxipubserver?action=template&template=getHierarchyByMarketType&classId=402&filterBIR=N';
            $change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($change_num != -1)
            {
                $this->logger->info("Using changenum: &cn=" . $change_num);
                $matchups_url .= '&cn=' . $change_num;
            }
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

    private function parseContent($source)
    {
        libxml_use_internal_errors(true); //Supress XML errors
        $xml = simplexml_load_string($source);

        if ($xml == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('MMA');

        foreach ($xml->response->williamhill->class->type as $type_node)
        {
            $event_name = $type_node['name'];
            foreach ($type_node->market as $market_node)
            {
                $market_type = substr(strrchr($market_node['name'], "-"), 2);
                if ($market_type == 'Bout Betting')
                {
                    //Normal matchup
                    //Find draw and ignore it
                    $teams = [];
                    foreach ($market_node->participant as $participant_node)
                    {
                        if ($participant_node['name'] != 'Draw')
                        {
                            $teams[] = $participant_node;
                        }
                    }

                    if (OddsTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($teams[0]['oddsDecimal'])) && OddsTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($teams[1]['oddsDecimal'])))
                    {
                        $matchup = new ParsedMatchup(
                            $teams[0]['name'],
                            $teams[1]['name'],
                            OddsTools::convertDecimalToMoneyline($teams[0]['oddsDecimal']),
                            OddsTools::convertDecimalToMoneyline($teams[1]['oddsDecimal'])
                        );

                        //Add time of matchup as metadata
                        $date_obj = null;
                        if (str_starts_with($type_node['name'], 'Potential Fights'))
                        {
                            $event_name = 'Future Events';
                            $date_obj = new DateTime('2030-12-31 00:00:00');
                        }
                        else
                        {
                            $date_obj = new DateTime($market_node['date'] . ' ' . $market_node['time'], new DateTimeZone('Europe/London'));    
                        }
                        $matchup->setMetaData('event_name', $event_name);
                        $matchup->setMetaData('gametime', $date_obj->getTimestamp());
                        $matchup->setCorrelationID($this->getCorrelationID($market_node));
                        $parsed_sport->addParsedMatchup($matchup);
                    }
                }
                else 
                {
                    //Prop bet
                    if ($market_type == 'Fight to go the Distance' || $market_type == 'Total Rounds' || $market_type == 'Fight Treble' || $market_type == 'Most Successful Takedowns' || (strpos($market_type, 'Total Rounds') !== false) ||
                        (count($market_node->participant) == 2 && in_array($market_node->participant[0]['name'], array('Yes','No')) && in_array($market_node->participant[1]['name'], array('Yes','No'))))
                    {
                        //Two option bet OR Yes/No prop bet (second line check in if)
                        $prop = new ParsedProp(
                                        $this->getCorrelationID($market_node) . ' - ' . $market_type . ' : ' .  $market_node->participant[0]['name'] . ' ' . $market_node->participant[0]['handicap'],
                                        $this->getCorrelationID($market_node) . ' - ' . $market_type . ' : ' .  $market_node->participant[1]['name'] . ' ' . $market_node->participant[1]['handicap'],
                                        OddsTools::convertDecimalToMoneyline($market_node->participant[0]['oddsDecimal']),
                                        OddsTools::convertDecimalToMoneyline($market_node->participant[1]['oddsDecimal']));
                        
                        $prop->setCorrelationID($this->getCorrelationID($market_node));
                        $parsed_sport->addFetchedProp($prop);
                    }
                    else
                    {   //Exclude SSBT (self service betting terminal)
                        if (strpos($market_type, 'SSBT') === false) {
                            //One line prop bet
                            foreach ($market_node->participant as $participant_node)
                            {
                                $prop = new ParsedProp(
                                        $this->getCorrelationID($market_node) . ' - ' . $market_type . ' : ' .  $participant_node['name'] . ' ' . $participant_node['handicap'],
                                        '',
                                        OddsTools::convertDecimalToMoneyline($participant_node['oddsDecimal']),
                                        '-99999');
                        
                                $prop->setCorrelationID($this->getCorrelationID($market_node));
                                $parsed_sport->addFetchedProp($prop);
                            }
                        }
                    }
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        //Store the changenum
        $change_num = time();
        if (BookieHandler::saveChangeNum(BOOKIE_ID, $change_num))
        {
            $this->logger->info("ChangeNum stored OK: " . $change_num);
        }
        else
        {
            $this->logger->warning("Error: ChangeNum was not stored");
        }

        return $parsed_sport;
    }

    private function getCorrelationID($market_node)
    {
        $correlation = '';
        if ($pos = strpos($market_node['name'], "-"))
        {
            $correlation = substr($market_node['name'], 0, $pos - 1);
            $correlation = $this->correctMarket($correlation);
        }
        else
        {
            $this->logger->warning("Warning: Unable to set correlation ID: " . $market_node['name']);
        }
        return $correlation;
    }

    private function correctMarket($market_node)
    {
        //The following piece of code ensures that the matchup correlation is always in lexigraphical order
        $pieces = explode(' v ', $market_node);
        if (count($pieces) == 2)
        {
            return $pieces[0] <= $pieces[1] ? $pieces[0] . ' v ' . $pieces[1] : $pieces[1] . ' v ' . $pieces[0]; 
        }
        return $market_node;
    }
}
