<?php
/**
 * XML Parser
 *
 * Bookie: Bet365
 * Sport: MMA
 *
 * Moneylines: Yes
 * Props: Yes
 * 
 * URL: http://oddsfeed3.bet365.com/Boxing_v2.asp
 *
 */

require_once 'config/inc.config.php';
require_once 'vendor/autoload.php';
require_once 'lib/bfocore/parser/general/inc.ParserMain.php';

use Respect\Validation\Validator as v;

define('BOOKIE_NAME', 'bet365');
define('BOOKIE_ID', '19');

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
            $this->logger->info("Note: Using matchup mock file at " . PARSE_MOCKFEEDS_DIR . "bet365.xml");
            $content = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . 'bet365.xml');
        }
        else
        {
            $matchups_url = 'http://oddsfeed3.bet365.com/Boxing_v2.asp';
            $this->logger->info("Fetching matchups through URL: " . $matchups_url);
            $content = ParseTools::retrievePageFromURL($matchups_url);
        }

        $parsed_sport = $this->parseContent($content);

        try 
        {
            $op = new OddsProcessor($this->logger, BOOKIE_ID);
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
        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            $this->logger->warning("Warning: XML broke!!");
        }

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->EventGroup as $cEventGroup)
        {
            if (substr((string) $cEventGroup['name'],0,6) != 'Boxing' && substr((string) $cEventGroup['name'],0,18) != 'World Super Series')
            {
                foreach ($cEventGroup->Event as $cEvent)
                {
                    foreach ($cEvent->Market as $cMarket)
                    {
                        if (strtolower((string) $cMarket['Name']) == 'to win fight' || strtolower((string) $cMarket['Name']) == 'to win match')
                        {
                            //Regular matchup
                            $oParsedMatchup = new ParsedMatchup(
                                            (string) $cMarket->Participant[0]['Name'],
                                            (string) $cMarket->Participant[1]['Name'],
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal'])
                            );
                            //Add correlation ID to match matchups to props
                            $oParsedMatchup->setCorrelationID((string) $cEvent['ID']);

                            //Add time of matchup as metadata
                            $oGameDate = DateTime::createFromFormat('d/m/y H:i:s', (string) $cMarket['StartTime']);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                            
                            $oParsedSport->addParsedMatchup($oParsedMatchup);

                        }
                        
                        else if (strtolower((string) $cMarket['Name']) == 'total rounds' && count($cMarket->Participant) == 2)
                        {
                            $oParsedProp = new ParsedProp(
                                            (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[0]['Name'] . ' rounds' ,
                                            (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[1]['Name'] . ' rounds' ,
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal']));

                            //Add correlation ID if available
                            $oParsedProp->setCorrelationID((string) $cEvent['ID']);

                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                        else if (strtolower((string) $cMarket['Name']) == 'total rounds' && count($cMarket->Participant) > 2)
                        {
                            //TODO: Currently no way to handle multiple over/unders on total rounds. Needs a fix
                        }
                        else if (count($cMarket->Participant) == 2 && in_array($cMarket->Participant[0]['Name'], array('Yes','No')) && in_array($cMarket->Participant[1]['Name'], array('Yes','No')))
                        {
                            //Two side prop (Yes/No)
                            $oParsedProp = new ParsedProp(
                                (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[0]['Name'],
                                (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[1]['Name'],
                                OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal']));

                            //Add correlation ID if available
                            $oParsedProp->setCorrelationID((string) $cEvent['ID']);

                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                        else 
                        {
                            //Probably prop, parse as such. Treat all as one-liners
                            foreach ($cMarket->Participant as $cParticipant)
                            {
                               $oParsedProp = new ParsedProp(
                                  (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cParticipant['Name'],
                                  '',
                                  OddsTools::convertDecimalToMoneyline($cParticipant['OddsDecimal']),
                                  '-99999');
                         
                                $oParsedProp->setCorrelationID((string) $cEvent['ID']);
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 8)
        {
            $this->full_run = true;
            $this->logger->info("Declared authoritive run");
        }

        return $oParsedSport;
    }
}

?>