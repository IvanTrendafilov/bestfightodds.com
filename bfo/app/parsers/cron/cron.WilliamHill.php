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
 */

require_once __DIR__ . "/../../../bootstrap.php";
require_once __DIR__ . "/../../../config/class.Ruleset.php";

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
        $oXML = simplexml_load_string($source);

        if ($oXML == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->response->williamhill->class->type as $cType)
        {
            foreach ($cType->market as $cMarket)
            {
                $sType = substr(strrchr($cMarket['name'], "-"), 2);
                if ($sType == 'Bout Betting')
                {
                    //Normal matchup
                    //Find draw and ignore it
                    $aParticipants = [];
                    foreach ($cMarket->participant as $cParticipant)
                    {
                        if ($cParticipant['name'] != 'Draw')
                        {
                            $aParticipants[] = $cParticipant;
                        }
                    }

                    if (ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal'])) && ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal'])))
                    {
                        $oTempMatchup = new ParsedMatchup(
                            $aParticipants[0]['name'],
                            $aParticipants[1]['name'],
                            OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal']),
                            OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal'])
                        );

                        //Add time of matchup as metadata
                        $oGameDate = null;
                        if ($cType['name'] == 'Potential Fights')
                        {
                            $oGameDate = new DateTime('2019-12-31 00:00:00');
                        }
                        else
                        {
                            $oGameDate = new DateTime($cMarket['date'] . ' ' . $cMarket['time']);    
                        }
                        $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        $oTempMatchup->setCorrelationID($this->getCorrelationID($cMarket));
                        $oParsedSport->addParsedMatchup($oTempMatchup);
                    }
                }
                else 
                {
                    //Prop bet
                    if ($sType == 'Fight to go the Distance' || $sType == 'Total Rounds' || $sType == 'Fight Treble' || $sType == 'Most Successful Takedowns' || (strpos($sType, 'Total Rounds') !== false) ||
                        (count($cMarket->participant) == 2 && in_array($cMarket->participant[0]['name'], array('Yes','No')) && in_array($cMarket->participant[1]['name'], array('Yes','No'))))
                    {
                        //Two option bet OR Yes/No prop bet (second line check in if)
                        $oParsedProp = new ParsedProp(
                                        $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cMarket->participant[0]['name'] . ' ' . $cMarket->participant[0]['handicap'],
                                        $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cMarket->participant[1]['name'] . ' ' . $cMarket->participant[1]['handicap'],
                                        OddsTools::convertDecimalToMoneyline($cMarket->participant[0]['oddsDecimal']),
                                        OddsTools::convertDecimalToMoneyline($cMarket->participant[1]['oddsDecimal']));
                        
                        $oParsedProp->setCorrelationID($this->getCorrelationID($cMarket));
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else
                    {   //Exclude SSBT (self service betting terminal)
                        if (strpos($sType, 'SSBT') === false) {
                            //One line prop bet
                            foreach ($cMarket->participant as $cParticipant)
                            {
                                $oParsedProp = new ParsedProp(
                                        $this->getCorrelationID($cMarket) . ' - ' . $sType . ' : ' .  $cParticipant['name'] . ' ' . $cParticipant['handicap'],
                                        '',
                                        OddsTools::convertDecimalToMoneyline($cParticipant['oddsDecimal']),
                                        '-99999');
                        
                                $oParsedProp->setCorrelationID($this->getCorrelationID($cMarket));
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                    }
                }
            }
        }

        //Declare full run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10)
        {
            $this->full_run = true;
            $this->logger->info("Declared full run");
        }

        //Store the changenum
        $sCN = time();
        if (BookieHandler::saveChangeNum(BOOKIE_ID, $sCN))
        {
            $this->logger->info("ChangeNum stored OK: " . $sCN);
        }
        else
        {
            $this->logger->warning("Error: ChangeNum was not stored");
        }

        return $oParsedSport;
    }

    private function getCorrelationID($a_cMarket)
    {
        $sCorrelation = '';
        if ($iPos = strpos($a_cMarket['name'], "-"))
        {
            $sCorrelation = substr($a_cMarket['name'], 0, $iPos - 1);
            $sCorrelation = $this->correctMarket($sCorrelation);
        }
        else
        {
            $this->logger->warning("Warning: Unable to set correlation ID: " . $a_cMarket['name']);
        }
        return $sCorrelation;
    }

    private function correctMarket($a_sMarket)
    {
        //The following piece of code ensures that the matchup correlation is always in lexigraphical order
        $aPieces = explode(' v ', $a_sMarket);
        if (count($aPieces) == 2)
        {
            return $aPieces[0] <= $aPieces[1] ? $aPieces[0] . ' v ' . $aPieces[1] : $aPieces[1] . ' v ' . $aPieces[0]; 
        }
        return $a_sMarket;
    }
}

?>