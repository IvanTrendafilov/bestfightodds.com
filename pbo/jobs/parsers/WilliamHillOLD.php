<?php

/**
 * XML Parser
 *
 * Bookie: William Hill
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://pricefeeds.williamhill.com/oxipubserver?action=template&template=getHierarchyByMarketType&classId=10&filterBIR=N
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
        if ($mode == 'mock') {
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "williamhill.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'williamhill.xml');
        } else {
            $matchups_url = 'http://pricefeeds.williamhill.com/oxipubserver?action=template&template=getHierarchyByMarketType&classId=10&filterBIR=N';
            $change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($change_num != -1) {
                $this->logger->info("Using changenum: &cn=" . $change_num);
                $matchups_url .= '&cn=' . $change_num;
            }
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try {
            $op = new OddsProcessor($this->logger, BOOKIE_ID, new Ruleset());
            $op->processParsedSport($parsed_sport, $this->full_run);
        } catch (Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
        }

        $this->logger->info('Finished');
    }

    private function parseContent($source)
    {
        libxml_use_internal_errors(true); //Supress XML errors
        $xml = simplexml_load_string($source);

        if ($xml == false) {
            $this->logger->warning("Warning: XML broke!!");
        }
        $this->parsed_sport = new ParsedSport('Boxing');
        foreach ($xml->response->williamhill->class->type as $type_node) {

            if (!str_starts_with($type_node['name'], 'UFC')) { //Exclude UFC props that WH has added by accident to the Boxing feed
                foreach ($type_node->market as $market_node) {
                    $this->parseMarket($type_node, $market_node);
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($this->parsed_sport->getParsedMatchups()) > 10) {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        //Store the changenum
        $change_num = time();
        if (BookieHandler::saveChangeNum(BOOKIE_ID, $change_num)) {
            $this->logger->info("ChangeNum stored OK: " . $change_num);
        } else {
            $this->logger->warning("Error: ChangeNum was not stored");
        }

        return $this->parsed_sport;
    }

    private function parseMarket($type_node, $market_node)
    {
        $market_name = trim(substr(strrchr($market_node['name'], "-"), 2));
        if (($market_name == 'Bout Betting' && count($market_node->participant) == 3)  //Number of participants needs to be 3 to ensure this is a boxing bout (fighter 1, fighter2, draw)
            || $market_name == 'Bout Betting 2 Way'
        ) {
            //Normal matchup
            //Find draw and ignore it
            $participants = [];
            foreach ($market_node->participant as $participant_node) {
                if ($participant_node['name'] != 'Draw') {
                    $participants[] = $participant_node;
                }
            }

            if (OddsTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($participants[0]['oddsDecimal'])) && OddsTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($participants[1]['oddsDecimal']))) {
                $matchup_obj = new ParsedMatchup(
                    $participants[0]['name'],
                    $participants[1]['name'],
                    OddsTools::convertDecimalToMoneyline($participants[0]['oddsDecimal']),
                    OddsTools::convertDecimalToMoneyline($participants[1]['oddsDecimal'])
                );

                //Add time of matchup as metadata
                $gamedate = null;
                if ($type_node['name'] == 'Potential Fights') {
                    $gamedate = new DateTime('2030-12-31 00:00:00');
                } else {
                    $gamedate = new DateTime($market_node['date'] . ' ' . $market_node['time'], new DateTimeZone('Europe/London'));
                }
                $matchup_obj->setMetaData('gametime', $gamedate->getTimestamp());
                $matchup_obj->setCorrelationID($this->getCorrelationID($market_node));
                $this->parsed_sport->addParsedMatchup($matchup_obj);
            }
        } else {
            //Prop bet
            if (
                $market_name == 'Fight to go the Distance' || $market_name == 'Total Rounds' || $market_name == 'Fight Treble' || $market_name == 'Most Successful Takedowns' || (strpos($market_name, 'Total Rounds') !== false) ||
                (count($market_node->participant) == 2 && in_array($market_node->participant[0]['name'], array('Yes', 'No')) && in_array($market_node->participant[1]['name'], array('Yes', 'No')))
            ) {
                //Two option bet OR Yes/No prop bet (second line check in if)
                $prop_obj = new ParsedProp(
                    $this->getCorrelationID($market_node) . ' - ' . $market_name . ' : ' .  $market_node->participant[0]['name'] . ' ' . $market_node->participant[0]['handicap'],
                    $this->getCorrelationID($market_node) . ' - ' . $market_name . ' : ' .  $market_node->participant[1]['name'] . ' ' . $market_node->participant[1]['handicap'],
                    OddsTools::convertDecimalToMoneyline($market_node->participant[0]['oddsDecimal']),
                    OddsTools::convertDecimalToMoneyline($market_node->participant[1]['oddsDecimal'])
                );

                $prop_obj->setCorrelationID($this->getCorrelationID($market_node));
                $this->parsed_sport->addFetchedProp($prop_obj);
            } else {
                if (strpos($market_name, 'SSBT') === false) { //Exclude SSBT (self service betting terminal)
                    //One line prop bet
                    foreach ($market_node->participant as $participant_node) {
                        $prop_obj = new ParsedProp(
                            $this->getCorrelationID($market_node) . ' - ' . $market_name . ' : ' .  $participant_node['name'] . ' ' . $participant_node['handicap'],
                            '',
                            OddsTools::convertDecimalToMoneyline($participant_node['oddsDecimal']),
                            '-99999'
                        );

                        $prop_obj->setCorrelationID($this->getCorrelationID($market_node));
                        $this->parsed_sport->addFetchedProp($prop_obj);
                    }
                }
            }
        }
    }

    private function getCorrelationID($market_node)
    {
        $correlation = '';
        if ($pos = strpos($market_node['name'], "-")) {
            $correlation = substr($market_node['name'], 0, $pos - 1);
            $correlation = $this->correctMarket($correlation);
        } else {
            $this->logger->warning("Warning: Unable to set correlation ID: " . $market_node['name']);
        }
        return $correlation;
    }

    private function correctMarket($market_node)
    {
        //The following piece of code ensures that the matchup correlation is always in lexigraphical order
        $pieces = explode(' v ', $market_node);
        if (count($pieces) == 2) {
            return $pieces[0] <= $pieces[1] ? $pieces[0] . ' v ' . $pieces[1] : $pieces[1] . ' v ' . $pieces[0];
        }
        return $market_node;
    }
}
