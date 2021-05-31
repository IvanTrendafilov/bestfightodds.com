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
    private $change_num;
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
            $this->change_num = BookieHandler::getChangeNum(BOOKIE_ID);
            if ($this->change_num != -1) {
                $this->logger->info("Using changenum: &cn=" . $this->change_num);
                $matchups_url .= '&cn=' . $this->change_num;
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
        $oXML = simplexml_load_string($source);

        if ($oXML == false) {
            $this->logger->warning("Warning: XML broke!!");
        }

        $parsed_sport = new ParsedSport('Boxing');

        foreach ($oXML->response->williamhill->class->type as $cType) {
            foreach ($cType->market as $cMarket) {
                $sType = substr(strrchr($cMarket['name'], "-"), 2);
                if ($sType == 'Bout Betting' && count($cMarket->participant) == 3) //Number of participants needs to be 3 to ensure this is a boxing bout (fighter 1, fighter2, draw)
                {
                    //Normal matchup
                    //Find draw and ignore it
                    $aParticipants = [];
                    foreach ($cMarket->participant as $cParticipant) {
                        if ($cParticipant['name'] != 'Draw') {
                            $aParticipants[] = $cParticipant;
                        }
                    }

                    if (ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal'])) && ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal']))) {
                        $parsed_matchup = new ParsedMatchup(
                            $aParticipants[0]['name'],
                            $aParticipants[1]['name'],
                            OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal']),
                            OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal'])
                        );

                        //Add time of matchup as metadata
                        $date_obj = null;
                        if ($cType['name'] == 'Potential Fights') {
                            $date_obj = new DateTime('2030-12-31 00:00:00');
                        } else {
                            $date_obj = new DateTime($cMarket['date'] . ' ' . $cMarket['time'], new DateTimeZone('Europe/London'));
                        }
                        $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());
                        $parsed_matchup->setCorrelationID($this->getCorrelationID($cMarket));
                        $parsed_sport->addParsedMatchup($parsed_matchup);
                    }
                } else {
                    //Prop bet
                    if (
                        $sType == 'Fight to go the Distance' || $sType == 'Total Rounds' || $sType == 'Fight Treble' || $sType == 'Most Successful Takedowns' || (strpos($sType, 'Total Rounds') !== false) ||
                        (count($cMarket->participant) == 2 && in_array($cMarket->participant[0]['name'], array('Yes', 'No')) && in_array($cMarket->participant[1]['name'], array('Yes', 'No')))
                    ) {
                        //Two option bet OR Yes/No prop bet (second line check in if)
                        $parsed_prop = new ParsedProp(
                            $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cMarket->participant[0]['name'] . ' ' . $cMarket->participant[0]['handicap'],
                            $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cMarket->participant[1]['name'] . ' ' . $cMarket->participant[1]['handicap'],
                            OddsTools::convertDecimalToMoneyline($cMarket->participant[0]['oddsDecimal']),
                            OddsTools::convertDecimalToMoneyline($cMarket->participant[1]['oddsDecimal'])
                        );

                        $parsed_prop->setCorrelationID($this->getCorrelationID($cMarket));
                        $parsed_sport->addFetchedProp($parsed_prop);
                    } else {   //Exclude SSBT (self service betting terminal)
                        if (strpos($sType, 'SSBT') === false) {
                            //One line prop bet
                            foreach ($cMarket->participant as $cParticipant) {
                                $parsed_prop = new ParsedProp(
                                    $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cParticipant['name'] . ' ' . $cParticipant['handicap'],
                                    '',
                                    OddsTools::convertDecimalToMoneyline($cParticipant['oddsDecimal']),
                                    '-99999'
                                );

                                $parsed_prop->setCorrelationID($this->getCorrelationID($cMarket));
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        }
                    }
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10 && $this->change_num = '') {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        //Store the changenum
        $sCN = time();
        if (BookieHandler::saveChangeNum(BOOKIE_ID, $sCN)) {
            $this->logger->info("ChangeNum stored OK: " . $sCN);
        } else {
            $this->logger->warning("Error: ChangeNum was not stored");
        }

        return $parsed_sport;
    }

    private function getCorrelationID($a_cMarket)
    {
        $sCorrelation = '';
        if ($iPos = strpos($a_cMarket['name'], "-")) {
            $sCorrelation = substr($a_cMarket['name'], 0, $iPos - 1);
            $sCorrelation = $this->correctMarket($sCorrelation);
        } else {
            $this->logger->warning("Warning: Unable to set correlation ID: " . $a_cMarket['name']);
        }
        return $sCorrelation;
    }

    private function correctMarket($a_sMarket)
    {
        //The following piece of code ensures that the matchup correlation is always in lexigraphical order
        $aPieces = explode(' v ', $a_sMarket);
        if (count($aPieces) == 2) {
            return $aPieces[0] <= $aPieces[1] ? $aPieces[0] . ' v ' . $aPieces[1] : $aPieces[1] . ' v ' . $aPieces[0];
        }
        return $a_sMarket;
    }
}
