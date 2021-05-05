<?php
/**
 * XML Parser
 *
 * Bookie: SportsInteraction
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 * Authoritative run: Yes
 *
 * Comment: Dev version
 * 
 * URL: https://www.sportsinteraction.com/odds_feeds/30/?consumer_name=bfodds&password=bfodds3145&format_id=4
 *
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../config/Ruleset.php";

use BFO\Parser\Utils\ParseTools;
use BFO\Utils\OddsTools;
use BFO\Parser\OddsProcessor;
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;

define('BOOKIE_NAME', 'sportsinteraction');
define('BOOKIE_ID', '8');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "sportsint.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'sportsint.xml');
        }
        else
        {
            $matchups_url = 'https://www.sportsinteraction.com/odds_feeds/30/?consumer_name=bfodds&password=bfodds3145&format_id=4';
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

    public function parseContent($a_sXML)
    {
        //Store as latest feed available for ProBoxingOdds.com
        $rStoreFile = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/sportsint-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);

        $a_sXML = preg_replace("<SportsInteractionLines>", "<SportsInteractionLines>\n", $a_sXML);
        $a_sXML = preg_replace("</SportsInteractionLines>", "\n</SportsInteractionLines>", $a_sXML);

        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }
        if (isset($oXML['reason']))
        {
            $this->logger->error("Error: " . $oXML['reason']);
        }
        if ($oXML->getName() == 'feed-unchanged')
        {
            $this->logger->info("Feed reported no changes");
        }

        $oParsedSport = new ParsedSport('MMA');

        if (isset($oXML->EventType))
        {
            foreach ($oXML->EventType as $cEventType)
            {
                if (trim((string) $cEventType['NAME']) == 'MMA')
                {
                    foreach ($cEventType->Event as $cEvent)
                    {
                        if (strpos(strtoupper($cEvent->Name), 'FIGHT OF THE NIGHT') !== false)
                        {
                            //Fight of the night prop
                            foreach ($this->parseFOTN($cEvent) as $oParsedProp)
                            {
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] == "" && !(strpos($cEvent->Name, 'Total Event') !== false))
                        {
                            //Regular matchup
                            $oParsedMatchup = null;
                            if (isset($cEvent->Bet[2]))
                            {
                                if (OddsTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                        && OddsTools::checkCorrectOdds((string) $cEvent->Bet[2]->Price)
                                        && !isset($cEvent->Bet[3]) //Temporary fix to remove props such as FOTN
                                        && !isset($cEvent->Bet[4]) //Temporary fix to remove props such as FOTN
				                        && !((string) $cEvent->Bet[0]->Price == '-10000' && (string) $cEvent->Bet[2]->Price == '-10000'))
                                {
                                    $oParsedMatchup = new ParsedMatchup(
                                                    (string) $cEvent->Bet[0]->Runner,
                                                    (string) $cEvent->Bet[2]->Runner,
                                                    (string) $cEvent->Bet[0]->Price,
                                                    (string) $cEvent->Bet[2]->Price);
                                }
                            }
                            else
                            {
                                if (OddsTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                        && OddsTools::checkCorrectOdds((string) $cEvent->Bet[1]->Price)
                                        && !isset($cEvent->Bet[3]) //Temporary fix to remove props such as FOTN
                                        && !isset($cEvent->Bet[4]) //Temporary fix to remove props such as FOTN
				                        && !((string) $cEvent->Bet[0]->Price == '-10000' && (string) $cEvent->Bet[1]->Price == '-10000'))
                                {
                                    $oParsedMatchup = new ParsedMatchup(
                                                    (string) $cEvent->Bet[0]->Runner,
                                                    (string) $cEvent->Bet[1]->Runner,
                                                    (string) $cEvent->Bet[0]->Price,
                                                    (string) $cEvent->Bet[1]->Price);
                                }
                            }
                            if ($oParsedMatchup != null)
                            {
                                //Add time of matchup as metadata
                                if (isset($cEvent->Date))
                                {
                                    $oGameDate = new DateTime($cEvent->Date);
                                    $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                                }

                                $oParsedMatchup->setCorrelationID(trim($cEvent->Name));
                                $oParsedSport->addParsedMatchup($oParsedMatchup);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] != "" && (count(array_intersect(['yes','no','over','under'], 
                                                                   [strtolower($cEvent->Bet[0]->Runner), strtolower($cEvent->Bet[1]->Runner)])) > 0))
                        {
                            //Two side prop bet since bet 1 or 2 contains the words yes,no,over or under
                            $oParsedProp = $this->parseTwoSideProp($cEvent);
                            if ($oParsedProp != null)
                            {
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                        else
                        {
                            //Prop - All other
                            foreach ($cEvent->Bet as $cBet)
                            {
                                if (ParseTools::checkCorrectOdds((string) $cBet->Price) 
                                    && !(intval($cBet->Price) < -9000))
                                {
                                    $oTempProp = new ParsedProp(
                                                    (string) $cEvent->Name . ' ::: ' . $cBet['TYPE'] . ' :: ' . $cBet->BetTypeExtraInfo . ' : ' . $cBet->Runner,
                                                    '',
                                                    (string) $cBet->Price,
                                                    '-99999');
                                    $oTempProp->setCorrelationID(trim($cEvent->Name));
                                    $oParsedSport->addFetchedProp($oTempProp);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 10 && $oXML->getName() != 'feed-unchanged')
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $oParsedSport;
    }

    private function parseFOTN($a_cEvent)
    {
        $aRet = [];
        foreach ($a_cEvent->Bet as $cBet)
        {
            $oTempProp = new ParsedProp(
                (string) $a_cEvent->Name . ' - ' . $cBet->Runner,
                '',
                (string) $cBet->Price,
                '-99999');

            $oTempProp->setCorrelationID(trim($a_cEvent->Name));
            $aRet[] = $oTempProp;
        }
        return $aRet;
    }

    private function parseTwoSideProp($a_cEvent)
    {
        //Find tie or draw and exclude it
        $aBets = [];
        foreach ($a_cEvent->Bet as $key => $cBet)
        {
            if ($cBet->Runner != 'Tie' && $cBet->Runner != 'Draw')
            {
                $aBets[] = $cBet;
            }
        }

        if (count($aBets) == 2)
        {
            $oTempProp = new ParsedProp(
                            (string) $a_cEvent->Name . ' ::: ' . $aBets[0]['TYPE'] . ' :: ' . $aBets[0]->Handicap . ' ' . $aBets[0]->BetTypeExtraInfo . ' : ' . $aBets[0]->Runner,
                            (string) $a_cEvent->Name . ' ::: ' . $aBets[1]['TYPE'] . ' :: ' . $aBets[1]->Handicap . ' ' . $aBets[1]->BetTypeExtraInfo . ' : ' . $aBets[1]->Runner,
                            (string) $aBets[0]->Price,
                            (string) $aBets[1]->Price);
            $oTempProp->setCorrelationID(trim($a_cEvent->Name));
            return $oTempProp;
        }
        $this->logger->warning("Invalid special two side prop: " . $a_cEvent->Name . ' ::: ' . $aBets[0]['TYPE'] . ' :: ' .  $aBets[0]->Handicap . ' ' . $aBets[0]->BetTypeExtraInfo . ' : ' . $aBets[0]->Runner);
        return null;
    }
}

?>